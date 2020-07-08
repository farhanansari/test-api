<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\TableRegistry;

session_start();

class BullhornConnectionComponent extends Component {

    private $bh_dev = true;
    private $bh_auth_url = 'https://auth.bullhornstaffing.com/oauth/';
    private $bh_client_id = '2afbbf9e-6da3-4396-8319-961e805dd7e1'; //you need to send ticket to get this id
    private $bh_clinet_secret = 'OzI6BPiM7XGNw6BOzW/QcOD/RaHIZONe';
    private $user;
    private $pass;

    private function setCredentials() {

        // If test environment is set
        if ($this->bh_dev === true) {

            $this->user = 'ivan.ivek.npe'; // This is the developer login id

            $this->pass = 'PeopleCaddie2017!';
        } else {

            $this->user = 'ivan.ivek.npe'; // this is direct client to Bullhorn

            $this->pass = 'PeopleCaddie2017!';
        }
    }

    public function makeHttpRequest($baseURL = '', $method, $options = array(), $format = 'json') {

        $url = $baseURL . $method;

        if (empty($options)) {

            $options = array(CURLOPT_RETURNTRANSFER => 1);
        }

        ob_start();  
        $out = fopen('php://output', 'w');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, true);  
        curl_setopt($ch, CURLOPT_STDERR, $out); 
        curl_setopt_array($ch, $options);
        
        $content = curl_exec($ch);
        curl_close($ch);
        fclose($out);  
        $debug = ob_get_clean();
        
        if ($format == 'json') {

            $content = json_decode($content);
        }
        return [$content,$debug];
        //return $content;
    }

    private function getBHAuthCode() {

       $method = 'authorize?client_id=' . $this->bh_client_id . '&response_type=code';

        $data = 'action=Login&username=' . $this->user . '&password=' . $this->pass;
       
        $options = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 150,
            CURLOPT_TIMEOUT => 150,
        );
        
        list($response,$debug) = $this->makeHttpRequest($this->bh_auth_url, $method, $options, 'string');

        if ((preg_match('#Location: (.*)#', $response, $r)) || ((empty($response) && (preg_match('#Location: (.*)#', $debug, $r))))) {

            $l = trim($r[1]);

            $temp = preg_split("/code=/", $l);

            $authcode = $temp[1];
            $_SESSION['BH']['auth_code'] = $authcode;
        }
        return $authcode;
    }

    private function getBHTokens($authCode) {

        $method = 'token?grant_type=authorization_code&code=' . $authCode . '&client_id='.
                $this->bh_client_id . '&client_secret=' . $this->bh_clinet_secret;

        $options = array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => true, CURLOPT_POSTFIELDS => array());

        list($response,$debug) = $this->makeHttpRequest($this->bh_auth_url, $method, $options);
        
        $_SESSION['BH']['refreshToken'] = $response->refresh_token;
        return $response;
    }

    private function getBHSession($token) {

        $baseURL = 'https://rest.bullhornstaffing.com/rest-services/';

        $method = 'login?version=*&access_token=' . $token;

        $options = array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => true, CURLOPT_POSTFIELDS => array());

        list($response,$debug) = $this->makeHttpRequest($baseURL, $method, $options);

        return $response;
    }

    private function setBHSession($sessionToken) {

        $_SESSION['BH']['restToken'] = $sessionToken->BhRestToken;

        $_SESSION['BH']['restURL'] = $sessionToken->restUrl;

        $_SESSION['BH']['tokenCreated'] = time();

        $crop_token = explode('/', trim($_SESSION['BH']['restURL'], '/'));

        $_SESSION['BH']['cropToken'] = $crop_token[4];

        $this->setGetSession(true);
    }

    private function getRefreshAccess($refreshtoken) {
        $method = 'token?grant_type=refresh_token&refresh_token=' . $refreshtoken . '&client_id=' .
                $this->bh_client_id . '&client_secret=' . $this->bh_clinet_secret;

        $options = array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => true, CURLOPT_POSTFIELDS => array());

        $response = $this->makeHttpRequest($this->bh_auth_url, $method, $options);

        return $response;
    }

    public function BHConnect() {
        try {
            $token = $this->setGetSession();

            if (isset($token->code) && !empty($token->code) && strtotime('+6 minutes',strtotime($token->created)) > time()) {
                $_SESSION['BH']['restToken'] = $token->code;
                $_SESSION['BH']['restURL'] = $token->url;
            } else {
                $this->setCredentials();

                $authCode = $this->getBHAuthCode();

                $token = $this->getBHTokens($authCode);

                $sessionToken = $this->getBHSession($token->access_token);

                $this->setBHSession($sessionToken);
            }
        } catch (Exception $e) {
            return $e;
        }
    }

    public function setGetSession($set = false) {

        $tokenTable = TableRegistry::get('Token');
        $token = $tokenTable->get(1);
        
        if ($set == false) {
            return $token;
        } else {
            $token->code = $_SESSION['BH']['restToken'];
            $token->url = $_SESSION['BH']['restURL'];
            $token->created = date('Y-m-d H:i:s');
            return $tokenTable->save($token);
        }
    }

}

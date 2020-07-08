<?php

/* * ************************************************************************************
 * Class name      : CompanyAdministratorController
 * Description     : CRUD process for company administrator
 * Created Date    : 19-08-2015 * 
 * Created By      : Akilan 
 * ************************************************************************************* */

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Network\Session\DatabaseSession;
use Cake\Validation\Validator;
use Cake\Network\Http\Client;
use Cake\Datasource\EntityInterface;

class ClientCorporationController extends AppController {
     /**
     * Initialize components for bullhorn connection
     */
    
    public function index(){
        $this->autoRender=false;
    }
    
    
    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
        $this->BullhornConnection->BHConnect();
    }
    
    public function add($params=null) {         
        $this->autoRender=false;            
        $params=$this->request->data;
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['ClientCorporation']['dateAdded'] = time();
        $post_params = json_encode($params['ClientCorporation']);
        $req_method = 'PUT';
        $response1 =  $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//        echo $response['changedEntityId'];
        $response2 = $this->contact_create($response1['changedEntityId'], $params['ClientContact']);
        echo json_encode(array($response1, $response2));
       
    }

    public function contact_create($company_id, $client_params) {        
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact?BhRestToken=' . $_SESSION['BH']['restToken'];
        $client_params['clientCorporation']['id'] = $company_id;
        $client_params['name'] = $client_params['firstName'] . ' ' . $client_params['lastName'];
        $client_params['dateAdded'] = time();
        $post_params = json_encode($client_params);
        $req_method = 'PUT';
        $response =  $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        return $response;
    }
}
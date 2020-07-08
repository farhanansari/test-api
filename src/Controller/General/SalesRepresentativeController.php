<?php

/* * ************************************************************************************
 * Class name      : SalesRepresentativeController
 * Description     : SalesRepresentative CRUD process
 * Created Date    : 24-08-2015 
 * Created By      : Akilan 
 * ************************************************************************************* */

namespace App\Controller\General;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Network\Session\DatabaseSession;
use Cake\Validation\Validator;
use Cake\Network\Http\Client;
use Cake\Datasource\EntityInterface;

class SalesRepresentativeController extends AppController {

    /**
     * Initialize components for bullhorn connection
     */
    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * ****************************************************************************   
     * Action Name   : add
     * Description   : To add the SalesRepresentative
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/general/SalesRepresentative/?  
     * ***************************************************************************** */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;       
        $user_id = $this->user_save($params, SALESREP_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

        if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
            $params['bullhorn_entity_id'] = $response['changedEntityId'];
            $access_token = $this->user_save($params, SALESREP_ROLE, 'save');
            $response['access_token'] = $access_token;
            $response['bullhorn_entity_id'] = strval($response['changedEntityId']);
            $response['result'] = 1;
        } else {
            $response = array('result' => 0, 'error' => 'Sorry, We faced failure in sales representative signup process');
        }
        echo json_encode($response);
    }

    /*     * ****************************************************************************   
     * Action Name   : view
     * Description   : To view the SalesRepresentative
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/general/SalesRepresentative/?  
     * ***************************************************************************** */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * ****************************************************************************   
     * Action Name   : update
     * Description   : To update the sales representative
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/salesRepresentative/?  
     * ***************************************************************************** */

    public function update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        if (isset($params['id'])) {
            $status = $this->user_update($params['id'], $params, 'bullhorn_id');
            if(isset($params['updatedByUserId'])){
               unset($params['updatedByUserId']);
            } 
            if ($status != 1 && is_array($status)) {
                $error_msg = $this->format_validation_message($status);
                echo json_encode(array('result' => 0, 'error' => $error_msg));
                exit;
            }
        }
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'POST';
        $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $response['result'] = 1;
        $response['message'] = 'Updated successfully!';
        echo json_encode($response);
    }

}

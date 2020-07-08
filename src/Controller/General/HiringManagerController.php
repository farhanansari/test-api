<?php

/* * ************************************************************************************
 * Class name      : HiringManagerController
 * Description     : HiringManager CRUD process
 * Created Date    : 24-08-2016 
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

class HiringManagerController extends AppController {

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
     * Description   : To create the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/HiringManager/?  
     * ***************************************************************************** */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $user_id = $this->user_save($params, HIRINGMANAGER_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        $this->BullhornConnection->BHConnect();
        $createdByUserId  = 0;
        if(isset($params['createdByUserId'])){
           $createdByUserId = $params['createdByUserId'];      
           unset($params['createdByUserId']);
        }       
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['clientCorporation']['id'] = $params['id']; //client corporation id
        $params['dateAdded'] = time();
        if(isset($params['id'])) unset($params['id']);
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
            $params['bullhorn_entity_id'] = $response['changedEntityId'];
            $params['company_id'] = $params['clientCorporation']['id'];
            if($createdByUserId != 0){
                $params['createdByUserId'] = $createdByUserId;
            }
            $access_token = $this->user_save($params, HIRINGMANAGER_ROLE, 'save');
            $response['access_token'] = $access_token;
            $response['bullhorn_entity_id'] = strval($response['changedEntityId']);
            $response['result'] = 1;
            echo json_encode($response);
        } else {
            echo array('result' => 0, 'error' => 'Sorry, We faced failure in contractor signup procss');
        }
    }

    /*     * ******************************************************************* 
     * Action Name   : view
     * Description   : To create the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/HiringManager/?  
     * ********************************************************************** */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,firstName,lastName,email,customText1,phone,clientCorporation(id,name,address)';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * ******************************************************************* 
     * Action Name   : update
     * Description   : To update the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/HiringManager/?  
     * ********************************************************************** */

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
        $post_params = json_encode($params);
        $req_method = 'POST';        
        $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $response['result']=1;
        $response['message']='Updated successfully!';
        echo json_encode($response);
    }

    /*     * ******************************************************************* 
     * Action Name   : delete
     * Description   : To delete the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/HiringManager/?  
     * ********************************************************************** */

    public function delete($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['isDeleted'] = 'true';
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

}

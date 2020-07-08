<?php

/* * ************************************************************************************
 * Class name      : CompanyAdministratorController
 * Description     : CompanyAdministrator CRUD process
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

class CompanyAdministratorController extends AppController {

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
     * URL           : /peoplecaddie-api/general/CompanyAdministrator/?  
     * ***************************************************************************** */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        //$companyDomainTable = TableRegistry::get('CompanyDomain');
        
//        if(isset($params['ClientContact']['email']) && !empty($params['ClientContact']['email'])) {
//            $emailDomain = substr(strrchr($params['ClientContact']['email'], "@"), 1);
//            $companyDomainInfo = $companyDomainTable->find()->select(['company_id'])->where(['email_domain' => $emailDomain])->first();
//            if(!empty($companyDomainInfo)) {
//                $companyDomainInfo = $companyDomainInfo->toArray();
//                $result = $this->hm_add($params,$companyDomainInfo);
//            }
//        }
        $user_id = $this->user_save($params['ClientContact'], COMPANY_ADMIN_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        $this->BullhornConnection->BHConnect();
        if (isset($params['ClientCorporation']['name'])) {
            $url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation?BhRestToken=' . $_SESSION['BH']['restToken'];
            $params['ClientCorporation']['dateAdded'] = time();
            $post_params = json_encode($params['ClientCorporation']);
            $req_method = 'PUT';
            $response1 = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $response2 = $this->contact_create($response1['changedEntityId'], $params['ClientContact']);
            //$emailData = $this->email_domain($response1['changedEntityId'],$emailDomain);
            echo json_encode(array($response1, $response2));
        } else {
            if (isset($params['ClientCorporation']) && isset($params['ClientContact'])) {              
                $response = $this->contact_create($params['ClientCorporation']['id'], $params['ClientContact']);
                echo json_encode(array($response));
            } else {
                 echo json_encode(array('result' => 0, 'error' => 'The client contact values are required'));
            }
        }
    }

    public function contact_create($company_id, $client_params) {
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact?BhRestToken=' . $_SESSION['BH']['restToken'];
        $client_params['clientCorporation'] =  array('id' =>$company_id);       
        $client_params['dateAdded'] = time();
        $owner_id=0;  
        $ispcwebsignup = 0;
        //remove owner id before sending to bullhorn
        if(isset($client_params['owner_id'])){
           $owner_id=$client_params['owner_id'];
           unset($client_params['owner_id']);
        }
        if(isset($client_params['pcwebsignup'])){
           $ispcwebsignup = $client_params['pcwebsignup'];
           unset($client_params['pcwebsignup']);
        }
        $post_params = json_encode($client_params);
        $req_method = 'PUT';                 
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);        
        if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
            $client_params['owner_id']=$owner_id;
            $client_params['bullhorn_entity_id'] = $response['changedEntityId'];
            $client_params['company_id'] = $company_id;
            $client_params['pcwebsignup'] = $ispcwebsignup;
            $access_token = $this->user_save($client_params, COMPANY_ADMIN_ROLE, 'save');
            $response['access_token'] = $access_token;
            $response['bullhorn_entity_id'] = strval($response['changedEntityId']);
            $response['result'] = 1;
            return $response;
        } else {
            return array('result' => 0, 'error' => 'Sorry, We faced failure in company admin create procss');
        }
    }
    
    /* *****************************************************************************   
     * Action Name   : email_domain
     * Description   : To save the email domain details
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 16-05-2017 
     * Updated Date  : 
     * ******************************************************************************/
    public function email_domain($company_id, $email_domain) {
        $companyDomainTable = TableRegistry::get('CompanyDomain');
        if(!empty($company_id) && !empty($email_domain)) {
            $params = ['company_id' => $company_id,'email_domain' => $email_domain];
            $companyDomain = $companyDomainTable->newEntity($params);
            $companyDomainTable->save($companyDomain);
        }
        return ;
    }
    
    /* *****************************************************************************   
     * Action Name   : add
     * Description   : To create the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/HiringManager/?  
     * ******************************************************************************/
    public function hm_add($params = [], $companyDomain = []) {
        
        $data = ['id' => $companyDomain['company_id'],'email' => $params['ClientContact']['email'],
                'firstName' => $params['ClientContact']['firstName'],'lastName' => $params['ClientContact']['lastName'],
                'phone' => $params['ClientContact']['phone'],'password' => $params['ClientContact']['password'],
                'notify' => 1];
        $user_id = $this->user_save($data, HIRINGMANAGER_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        $this->BullhornConnection->BHConnect();
        $createdByUserId  = 0;
        if(isset($data['createdByUserId'])){
           $createdByUserId = $data['createdByUserId'];      
           unset($data['createdByUserId']);
        }       
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact?BhRestToken=' . $_SESSION['BH']['restToken'];
        $data['clientCorporation']['id'] = $data['id']; //client corporation id
        $data['dateAdded'] = time();
        if(isset($data['id'])) unset($data['id']);
        $post_params = json_encode($data);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
            $data['bullhorn_entity_id'] = $response['changedEntityId'];
            $data['company_id'] = $data['clientCorporation']['id'];
            if($createdByUserId != 0){
                $data['createdByUserId'] = $createdByUserId;
            }
            $access_token = $this->user_save($data, HIRINGMANAGER_ROLE, 'save');
            $response1['access_token'] = $access_token;
            $response1['bullhorn_entity_id'] = strval($response['changedEntityId']);
            $response1['result'] = 1;
            echo json_encode(array($response, $response1));exit;
        } else {
            echo array('result' => 0, 'error' => 'Sorry, We faced failure in contractor signup procss');exit;
        }
        
    }
    
    /* *****************************************************************************   
     * Action Name   : hm_add_temp
     * Description   : To create the hiring manageer
     * Created by    : Balasuresh
     * Updated by    : 
     * Created Date  : 03-06-2017 
     * Updated Date  : __-__-____ 
     * ******************************************************************************/
    
    public function add_temp_hm($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        
        $user_id = $this->user_save($params['ClientContact'], HIRINGMANAGER_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        $this->BullhornConnection->BHConnect();
        if (isset($params['ClientCorporation']['name'])) {
            $url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation?BhRestToken=' . $_SESSION['BH']['restToken'];
            $params['ClientCorporation']['dateAdded'] = time();
            $post_params = json_encode($params['ClientCorporation']);
            $req_method = 'PUT';
            $response1 = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $response2 = $this->hm_create($response1['changedEntityId'], $params['ClientContact']);
            echo json_encode(array($response1, $response2));
        } else {
            if (isset($params['ClientCorporation']) && isset($params['ClientContact'])) {              
                $response = $this->hm_create($params['ClientCorporation']['id'], $params['ClientContact']);
                echo json_encode(array($response));
            } else {
                 echo json_encode(array('result' => 0, 'error' => 'The client contact values are required'));
            }
        }
    }
    
    public function hm_create($company_id, $client_params) {
        
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact?BhRestToken=' . $_SESSION['BH']['restToken'];
        $client_params['clientCorporation'] =  array('id' =>$company_id);       
        $client_params['dateAdded'] = time();
        $owner_id=0;  
        $ispcwebsignup = 0;
        //remove owner id before sending to bullhorn
        if(isset($client_params['owner_id'])){
           $owner_id=$client_params['owner_id'];
           unset($client_params['owner_id']);
        }
        if(isset($client_params['pcwebsignup'])){
           $ispcwebsignup = $client_params['pcwebsignup'];
           unset($client_params['pcwebsignup']);
        }
        $post_params = json_encode($client_params);
        $req_method = 'PUT';                 
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);        
        if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
            $client_params['owner_id']=$owner_id;
            $client_params['bullhorn_entity_id'] = $response['changedEntityId'];
            $client_params['company_id'] = $company_id;
            $client_params['pcwebsignup'] = $ispcwebsignup;
            $access_token = $this->user_save($client_params, HIRINGMANAGER_ROLE, 'save');
            $response['access_token'] = $access_token;
            $response['bullhorn_entity_id'] = strval($response['changedEntityId']);
            $response['result'] = 1;
            return $response;
        } else {
            return array('result' => 0, 'error' => 'Sorry, We faced failure in hiring manager create procss');
        }
    }

    /*     * ****************************************************************************   
     * Action Name   : view
     * Description   : To create the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 22-09-2016 
     * URL           : /peoplecaddie-api/general/CompanyAdministrator/?  
     * paramater     : id =>company administator bullhorn entity id
     * ***************************************************************************** */

    public function view($params=null) {
        $this->autoRender = false;
        $params = $this->request->data;        
        $this->BullhornConnection->BHConnect();
        $userTable = TableRegistry::get('Users');
        //retrieve owner id
        $user_det = $userTable->find()->select(['id','owner_id','isActive'])->where(['bullhorn_entity_id' => $params['id']])->toArray();
       
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,firstName,lastName,email,customText1,phone,clientCorporation(id,name,address)';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        //for showing sales repentative of company
        if(isset($response['data'])){
            $response['data']['owner_id']=isset($user_det[0]["owner_id"]) ? $user_det[0]["owner_id"] : "";
            $response['data']['isActive']=isset($user_det[0]["isActive"]) ? $user_det[0]["isActive"] : "";
        }
        echo json_encode($response);
    }

    /*     * ****************************************************************************   
     * Action Name   : update
     * Description   : To create the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/CompanyAdministrator/?  
     * ***************************************************************************** */

    public function update($params=null) {
        $this->autoRender = false;
        $params = $this->request->data;   
        $client_params=array();
        $client_contact=array();
        if(isset($params['ClientContact']) && isset($params['ClientCorporation'])){
            $client_params=$params['ClientCorporation'];
            $client_contact=$params['ClientContact'];
        }
        if (isset($params['id'])) {
            $status = $this->user_update($client_contact['id'], $client_contact, 'bullhorn_id');
            if(isset($params['ClientContact']['updatedByUserId'])){
               unset($params['ClientContact']['updatedByUserId']);
            } 
            if ($status != 1 && is_array($status)) {
                $error_msg = $this->format_validation_message($status);
                echo json_encode(array('result' => 0, 'error' => $error_msg));
                exit;
            }
        }    
        $this->company_admin_update($params);
        $response['result']=1;
        $response['message']='Updated successfully!';
        echo json_encode($response);
    }
    
     /*******************************************************************************  
     * function      : company_admin_update
     * Description   : Create array data for updating client corporation and client contact detail     * 
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 22-09-2016 
     * Updated Date  : 22-09-2016      *
     * ***************************************************************************** */    
    function company_admin_update($params){
        if(!empty($params)){     
            $this->BullhornConnection->BHConnect();     
            $curl_data=array();
            $i=0;
            $curl_data[$i]['url']=$url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation/' . $params['ClientCorporation']['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $curl_data[$i]['post_data']=json_encode($params['ClientCorporation']);
            $curl_data[$i]['req_method'] = 'POST';   
            $j=1;
            $curl_data[$j]['url']=$url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $params['ClientContact']['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $curl_data[$j]['post_data']=json_encode($params['ClientContact']);
            $curl_data[$j]['req_method'] = 'POST';           
            return $response = $this->BullhornCurl->multiRequest($curl_data);           
        }
    }

    /*     * ****************************************************************************   
     * Action Name   : delete
     * Description   : To delete the hiring manageer
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016 
     * Updated Date  : 24-08-2016 
     * URL           : /peoplecaddie-api/general/CompanyAdministrator/?  
     * ***************************************************************************** */

    public function delete($params) {
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

<?php

/* * ************************************************************************************
 * Class name      : UsersController
 * Description     : To register the company administrator,hiring manager and contractor & login process
 * Created Date    : 19-08-2016 *
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
use Cake\Datasource\EntityInterface;
use Cake\Mailer\Email;
use Cake\Core\Configure;
use Cake\Routing\Router;

class UsersController extends AppController {

    /**
     * Initialize components for bullhorn connection
     */
    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
        $this->loadComponent('Notification');
        $this->loadComponent('Privacy');
    }

    /*     * ************************************************************************************
     * Function name   : login
     * Description     : For login process for company administrator ,hiring manager and contractor
     * Created Date    : 19-08-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function login() {
        $this->viewBuilder()->layout(false);
        $this->autoRender = false;
        $params = apache_request_headers();
        //For extra checking if we set login page as index
        if (isset($params['Authorization']) && PUBLIC_SECRET_KEY == $params['Authorization']) {
            
        } else {
            exit;
        }
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $posted_data = $this->request->data;
                $device_id = isset($posted_data['device_id']) ? $posted_data['device_id'] : "";
                $device_type = isset($posted_data['device_type']) ? $posted_data['device_type'] : "";
                $userTable = TableRegistry::get('Users');
                $user_det = $userTable->get($user['id']);
                $bullhronId = $user_det->bullhorn_entity_id;
                
                /* to get the last login of the users */
                $session = $this->request->session();
                $session->write('LastLogin', $user_det->last_login);
                $updateUser = $this->getCandidateInfo($bullhronId);
                if (!empty($updateUser)) {
                    $user_det->firstName = $updateUser['firstName'];
                    $user_det->lastName = $updateUser['lastName'];
                    $user_det->phone = $updateUser['phone'];
                }

                if ($device_id != "" && $device_type != "") {
                    $user_det->id = $user['id'];
                    $user_det->device_id = $device_id;
                    $user_det->device_type = $device_type == "iOS" ? 1 : 2; // 1 =>iOS, 2 => Android
                    $user_det = $userTable->save($user_det);
                    $this->checkDeviceId($user_det);
                }
                if (!empty($updateUser)) {
                    $user_det->employmentPreference = $updateUser['employmentPreference'];
                    $user_det->customTextBlock4 = $updateUser['customTextBlock4'];
                }
//                $user_det->access_token = md5(uniqid(mt_rand() . $user['username'], true));
//                if ($userTable->save($user_det)) {
                if ($user_det->isActive) {
                    /*
                    * Save last login detail in user table
                    * Modified by : AnanthJP
                    * Modified Date : 17/11/2016
                    */
                   $user_det->last_login = strtotime(date('Y/m/d h:i', time()));
                   $userTable->save($user_det);
                    echo $this->getuserdata($user_det);
                } else {
                    echo json_encode(array('result' => 0, 'error' => 'Unauthorized access!'));
                    exit;
                }
//                }
            } else {
                echo json_encode(array('result' => 0, 'error' => 'Invalid username or password, Please try again'));
            }
        }
    }

    /*     * ************************************************************************************
     * Function name   : getCandidateInfo
     * Description     : get details of a candidate from bullhorn and update into pc.com server
     * Created Date    : 13-12-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function getCandidateInfo($id = null) {
        $this->BullhornConnection->BHConnect();
        $fields = 'firstName,lastName,phone,phone2,employmentPreference,customTextBlock4';
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data'])) {
            if (count($response['data']['employmentPreference']) == 1 && isset($response['data']['employmentPreference'][0]) && strtolower($response['data']['employmentPreference'][0]) == 'permanent') { // Permanent
                $response['data']['employmentPreference'] = "";
            } else {
                $response['data']['employmentPreference'] = (!empty($response['data']['employmentPreference'])) ? implode(',', $response['data']['employmentPreference']) : "";
            }
            return $response['data'];
        } else {
            return [];
        }
    }

    /*     * ************************************************************************************
     * Function name   : options
     * Description     : For retrieving available text options
     * Created Date    : 07-09-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function options($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $option_text = isset($params['option']) ? $params['option'] : "Skill";
        list($url, $req_method, $post_params) = $this->options_new($option_text, $params);


//        $url = $_SESSION['BH']['restURL'] . '/options/' . $option_text . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=300&fields=*&rand=' . rand(1, 1111111111);
//        $post_params = json_encode($params);
//        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        //pr($response);
        echo json_encode($response);
    }

    public function options_new($option_text, $params = array()) {
        $url = $_SESSION['BH']['restURL'] . '/options/' . $option_text . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=300&fields=*&rand=' . rand(1, 1111111111);
        $post_params = json_encode($params);
        $req_method = 'GET';
        return array($url, $req_method, $post_params);
    }

    /*     * ************************************************************************************
     * Function name   : metaData
     * Description     : For retrieving available meta data
     * Created Date    : 20-12-2016
     * Created By      : Balasuresh A
     * ************************************************************************************* */

    public function metaData($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        //$params = array("option" => 'JobOrder', 'fields' => 'title');
        //       $params = array("option" => 'Candidate', 'fields' => '*');
        $this->BullhornConnection->BHConnect();
        $option_text = isset($params['option']) ? $params['option'] : "JobOrder";
        $fields = isset($params['fields']) ? $params['fields'] : "title";
        list($url, $req_method, $post_params) = $this->metadata_new($option_text, $fields);

//        $url = $_SESSION['BH']['restURL'] . '/meta/' . $option_text . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=300&fields=' . $fields;
//        $post_params = json_encode($params);
//        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    public function metadata_new($option_text, $fields) {
        $url = $_SESSION['BH']['restURL'] . '/meta/' . $option_text . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=300&fields=' . $fields;
        $post_params = json_encode([]);
        $req_method = 'GET';
        return array($url, $req_method, $post_params);
    }

    public function fetchOptions($na = array(), $na1 = array()) {
        $curl_data = [];
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        list($curl_data[0]['url'], $curl_data[0]['req_method'], $curl_data[0]['post_data']) = $this->options_new($params['option_text']);
        list($curl_data[1]['url'], $curl_data[1]['req_method'], $curl_data[1]['post_data']) = $this->metadata_new($params['entity'], $params['field']);
        $response = $this->BullhornCurl->multiRequest($curl_data);
        $option_response = json_decode($response[0], True);
        $meta_response= json_decode($response[1], True);
        $new_array=array_merge($option_response,$meta_response);
        echo json_encode($new_array);
    }

    /*     * ************************************************************************************
     * Function name   : select_appointment
     * Description     : For retrieving available appointments
     * Created Date    : 08-09-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function selectAppointment($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=interviews(*)';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /**
     * For sample signup process
     */
    public function add() {
        $user = $this->Users->newEntity();
        if ($this->request->is('post')) {
            $this->request->data = array_map('trim', $this->request->data);

            $user->access_token = md5(uniqid(mt_rand(), true));
            $user = $this->Users->patchEntity($user, $this->request->data);
            if ($this->Users->save($user)) {
                
            }
        }
    }

    /*     * ********************************************************************
     * Action Name   : lists
     * Description   : To retrieve detail of hiring manager,company admininstrator and sales rep
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 15-09-2016
     * Updated Date  : 15-09-2016
     * URL           : /peoplecaddie-api/users/lists
     * id =>user_id
     * needed_role => for fetching user role list & fetching particular role for corresponding company id
     * salesrep role with type paramater for fetching exact company.
     * ********************************************************************** */

    public function lists() {
        $this->loadModel('User');
        $userTable = TableRegistry::get('User');
        $this->autoRender = false;
        $params = $this->request->data;
        $user_list = array();
        $role = (isset($params['role'])) ? $params['role'] : "";
        switch ($role) {
            case SUPER_ADMIN_ROLE:
                $role1 = array(SUPER_ADMIN_ROLE, COMPANY_ADMIN_ROLE, HIRINGMANAGER_ROLE, SALESREP_ROLE);
                if (isset($params['needed_role']) && !empty($params['needed_role'])) {
                    $role1 = array($params['needed_role']);
                    $params['id'] = 0;
                }
                if (isset($params['company_id'])) {
                    $cond = ['role IN' => $role1, 'id <>' => $params['id'], 'company_id' => $params['company_id']];
                    $company_id_ar = array($params['company_id']);
                } else {
                    $cond = ['role IN' => $role1, 'id <>' => $params['id']];
                    $company_id_ar = $this->get_owned_company(SUPER_ADMIN_ROLE);
                    $company_det = $this->get_company_list($company_id_ar);
                }

                $user_list = $this->User->find('all')->select(['bullhorn_entity_id', 'company_id', 'role', 'id', 'firstName', 'lastName', 'email'])
                        ->where($cond)
                        ->order(['id' => 'DESC']);
                break;
            case COMPANY_ADMIN_ROLE || SALESREP_ROLE || HIRINGMANAGER_ROLE:
                //For fetching particular user list
                $role1 = array(COMPANY_ADMIN_ROLE, HIRINGMANAGER_ROLE);
                if (isset($params['needed_role']) && !empty($params['needed_role'])) {
                    $role1 = array($params['needed_role']);
                    $params['id'] = 0;
                }
                switch ($role) {
                    case in_array($role, array(COMPANY_ADMIN_ROLE, HIRINGMANAGER_ROLE)):
                        $user_list = $this->company_user_list(array($params['company_id']), $params['id'], $role1);
                        break;
                    case SALESREP_ROLE :
                        if(isset($params['type']) && !empty($params['type']) && isset($params['company_id'])){
                            $control_company_ids[]= $params['company_id'];
                        } else {
                            $control_company_ids = $this->get_owned_company(SALESREP_ROLE, $params);
                        }
                        
                        $user_list = $this->company_user_list($control_company_ids, $params['id'], $role1);
                        break;
                }
                break;
        }

        $fields = 'id,firstName,lastName,address,email,clientCorporation(id,name)';
        $user_list_ar = array();
        $user_role_ar = array();
        $super_admin_det = array();
        $user_list_str = 0;
        //Get company users for corresponding for company id
        if (!empty($user_list)) {
            foreach ($user_list as $user_list_sgl) {
                if ($user_list_sgl['bullhorn_entity_id'] != 0) {
                    $user_list_ar[] = $user_list_sgl['bullhorn_entity_id'];
                } else {
                    if ($user_list_sgl['role'] == SUPER_ADMIN_ROLE) {
                        $company_name = isset($company_det[$user_list_sgl['company_id']]) ? $company_det[$user_list_sgl['company_id']] : "";
                        $company_id = !empty($user_list_sgl['company_id']) ? $user_list_sgl['company_id'] : "";
                        $super_admin_det[] = array(
                            'id' => $user_list_sgl['id'],
                            'firstName' => $user_list_sgl['firstName'],
                            'lastName' => $user_list_sgl['lastName'],
                            'email' => $user_list_sgl['email'],
                            'clientCorporation' => array(
                                'id' => $company_id,
                                'name' => $company_name
                            ),
                            'role' => SUPER_ADMIN_ROLE,
                        );
                    }
                }

                $user_role_ar['role'][$user_list_sgl['bullhorn_entity_id']] = $user_list_sgl['role'];
            }
            if (!empty($user_list_ar)) {
                $user_list_str = implode(",", $user_list_ar);
                $this->BullhornConnection->BHConnect();
                $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $user_list_str . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                $post_params = json_encode($params);
                $req_method = 'GET';
                $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                $response = $this->check_zero_index($response);
            } else {
                $response = [];
            }
            echo json_encode(array($response, $user_role_ar, $super_admin_det));
        } else {
            echo json_encode(array('result' => 1, 'message' => 'No user records found'));
        }
    }

    /**
     * Retrieved users and roles based on company id
     */
    function company_user_list($company_id, $user_id, $role) {
        $this->loadModel('User');
        $user_list = $this->User->find('all')->select(['bullhorn_entity_id', 'role'])
                ->where([
                    'company_id IN' => $company_id,
                    'role IN' => $role,
                    'id <>' => $user_id
                ])
                ->toArray();
        return $user_list;
    }

    /**
     * Retrieved sales representative owned company ids
     * @param type $bullhorn_entity_id
     * @return array
     */
    function get_owned_company($role, $params = array()) {
        switch ($role) {
            case SUPER_ADMIN_ROLE:
                $company_det = $this->User->find('all')->select(['company_id'])->group(['company_id'])->toArray();
                break;
            case SALESREP_ROLE:
                $company_det = $this->User->find('all')->select(['company_id'])
                                ->where(['OR' => ['owner_id' => $params['bullhorn_entity_id'], ['bullhorn_entity_id' => $params['bullhorn_entity_id']]]])->group(['company_id'])->toArray();
                break;
            case COMPANY_ADMIN_ROLE:
                $company_det = array('company_id' => $params['company_id']);
                break;
            case HIRINGMANAGER_ROLE:
                $company_det = array('company_id' => $params['company_id']);
                break;
        }

        $company_ids = array();
        if (!empty($company_det)) {
            foreach ($company_det as $company_sgl) {
                if ($company_sgl['company_id'] != 0)
                    array_push($company_ids, $company_sgl['company_id']);
            }
        }
        if (isset($params['company_id']))
            array_push($company_ids, $params['company_id']);
        return array_unique($company_ids);
    }

    /*     * ********************************************************************
     * Action Name   : lists
     * Description   : To retrieve detail of job applied contractors for hiring mananager & sales rep
      and all for super admin
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 23-09-2016
     * Updated Date  : 23-09-2016
     * URL           : /peoplecaddie-api/contractors
     * ********************************************************************** */

    public function contractors() {
        $this->loadModel('User');
        $this->autoRender = false;
        $params = $this->request->data;
        $role = (isset($params['role'])) ? $params['role'] : "";
        $user_list = array();
        $control_company_ids = array();
        $response = [];
        switch ($role) {
            case SUPER_ADMIN_ROLE:
                $candidate_list = $this->User->find('all')->select(['bullhorn_entity_id'])
                                ->where(['role IN' => [CANDIDATE_ROLE], 'isActive' => 1])->toArray();
                $response = $this->get_candidate_list($candidate_list);
                break;
            case SALESREP_ROLE:
                $control_company_ids = $this->get_owned_company(SALESREP_ROLE, $params);
                $response = $this->sendout_based_candidate($control_company_ids);
                break;
            case HIRINGMANAGER_ROLE && isset($params['bullhorn_entity_id']):
                $control_company_ids = array($params['company_id']);
                $response = $this->sendout_based_candidate($control_company_ids, [], $params['bullhorn_entity_id']);
                break;
            case COMPANY_ADMIN_ROLE:
                $control_company_ids = array($params['company_id']);
                $response = $this->sendout_based_candidate($control_company_ids);
                break;
        }
        echo json_encode(array($response));
    }

    /*     * **********************************************************************
     * Function      : sendout_based_candidate
     * Description   : Retrieve canidate list based on company id
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 23-09-2016
     * Updated Date  : 23-09-2016
     * ********************************************************************** */

    function sendout_based_candidate($control_company_ids, $params = array(), $candidate_id = null) {
        $company_id_str = implode(",", array_filter(array_unique($control_company_ids)));
        if (!empty($control_company_ids)) {
            $this->BullhornConnection->BHConnect();
            if ($candidate_id != null) {
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=jobOrder.clientContact.id+IN(' . $candidate_id . ')+AND+clientCorporation.id+IN(' . $company_id_str . ')+AND+'
                        . 'jobOrder.isOpen=true+AND+jobOrder.isDeleted=false'
                        . '&fields=candidate(id,firstName,lastName,address,email,phone)&BhRestToken=' . $_SESSION['BH']['restToken'];
            } else {
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=clientCorporation.id+IN(' . $company_id_str . ')+AND+'
                        . 'jobOrder.isOpen=true+AND+jobOrder.isDeleted=false'
                        . '&fields=candidate(id,firstName,lastName,address,email,phone)&BhRestToken=' . $_SESSION['BH']['restToken'];
            }
            $post_params = json_encode($params);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $contractor_data = array();
            $temp = array();
            if (isset($response['data']) && !empty($response['data'])) {
                if (!isset($response['data'][0])) {
                    $databkp = $response['data'];
                    unset($response['data']);
                    $response['data'][] = $databkp;
                }
                foreach ($response['data'] as $res) {
                    if (!isset($temp[$res['candidate']['id']])) {
                        $temp[$res['candidate']['id']] = $res['candidate']['id'];
                        $userTable = TableRegistry::get('Users');
                        $user_det = $userTable->find()->select(['headshot'])->where(['bullhorn_entity_id' => $res['candidate']['id']])->first();
                        $res['candidate']['headshot'] = isset($user_det->headshot) ? $user_det->headshot : '';
                        $contractor_data[] = $res;
                    }
                }
                $response['data'] = $contractor_data;
            }
        } else {
            $response = array("result" => 1, "message" => "No records found");
        }
        return $response;
    }

    function get_candidate_list($candidate_list) {
        if (!empty($candidate_list)) {
            $candidate_id_ar = array();
            $params = array();
            foreach ($candidate_list as $cat_sgl) {
                if ($cat_sgl['bullhorn_entity_id'] != 0)
                    array_push($candidate_id_ar, $cat_sgl['bullhorn_entity_id']);
            }
            $candidate_str = implode(",", $candidate_id_ar);

            $userTable = TableRegistry::get('Users');
            $user_det = $userTable->find('list', ['keyField' => "bullhorn_entity_id", 'valueField' => 'headshot'])->where(['bullhorn_entity_id IN' => $candidate_id_ar])->toArray();

            $fields = 'id,firstName,lastName,address,email,phone';
            $this->BullhornConnection->BHConnect();
            $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $candidate_str . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
            $post_params = json_encode($params);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            foreach ($response['data'] as $key => $candidate) {
                $response['data'][$key]['headshot'] = $user_det[$candidate['id']];
            }
            return $response;
        } else {
            return $response = array("result" => 1, "message" => "No records found");
        }
    }

    /*     * **********************************************************************
     * Function      : get_company_list
     * Description   : Retrieve company list
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 27-09-2016
     * Updated Date  : 27-09-2016
     * ********************************************************************** */

    function get_company_list($company_lst, $type = null, $sort = null) {
        $company_det = array();
        $response = array();
        if (!empty($company_lst)) {
            $company_lst_str = implode(",", $company_lst);
            $fields = 'id,name,clientContacts';
            $this->BullhornConnection->BHConnect();
            $url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation/' . $company_lst_str . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params = json_encode(array()), $req_method);
            if (isset($response['data']) && !isset($response['data'][0])) {
                $databkp = $response['data'];
                unset($response['data']);
                $response['data'][] = $databkp;
            }
            if ($type != 'detail') {
                foreach ($response['data'] as $res) {
                    $company_det[$res['id']] = $res['name'];
                }
            }
            if ($sort == 'clientContactSorted' && isset($response['data'])) {
                $clientContactSorted = $this->getClientContacts($company_lst);
                if (!empty($clientContactSorted)) {
                    foreach ($response['data'] as $key => $company) {
                        $response['data'][$key]['clientContacts'] = [
                            'total' => isset($clientContactSorted[$company['id']]) ? count($clientContactSorted[$company['id']]) : 0,
                            'data' => isset($clientContactSorted[$company['id']]) ? $clientContactSorted[$company['id']] : []
                        ];
                    }
                }
            }
        }
        return ($type == 'detail') ? $response : $company_det;
    }

    /*
     * Description: Get all client contacts (company admins) using company ids
     *
     */

    function getClientContacts($company_ids = []) {
        if (empty($company_ids)) {
            return [];
        }
        $contacts = [];
        $companies = [];
        $company_lst_str = implode(",", $company_ids);
        $this->BullhornConnection->BHConnect();
        $fields = 'id,clientCorporation,firstName,lastName';
        $url = $_SESSION['BH']['restURL'] . '/query/ClientContact?where=clientCorporation.id+IN+(' . $company_lst_str . ')&BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=1000&fields=' . $fields;
        $response = $this->BullhornCurl->curlFunction($url, json_encode([]), 'GET');
        if (isset($response['data'])) {
            if (!isset($response['data'][0])) {
                $databkp = $response['data'];
                unset($response['data']);
                $response['data'][] = $databkp;
            }
            $contacts = array_reverse($response['data']);
            foreach ($contacts as $contact) {
                $companies[$contact['clientCorporation']['id']][] = [
                    'id' => $contact['id'],
                    'firstName' => $contact['firstName'],
                    'lastName' => $contact['lastName']
                ];
            }
            return $companies;
        }
    }

    /*     * ************************************************************************
     * Function      : companies
     * Description   : Retrieved company list based on login
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 29-09-2016
     * Updated Date  : 29-09-2016
     * ********************************************************************** */

    public function companies() {
        $this->loadModel('User');
        $this->autoRender = false;
        $params = $this->request->data;
        $role = (isset($params['role'])) ? $params['role'] : "";
        $company_id_ar = $this->get_owned_company($role, $params);
        $owner_det = $this->User->find('all')
                        ->where(['company_id IN' => $company_id_ar, 'owner_id <>' => 0])
                        ->select(['owner_id', 'company_id'])->toArray();
        $owner_det_ar = array();
        $owner_detail_ar = array();
        $company_owner_ar = array();
        $cmpy_owner_det = array();
        if (!empty($owner_det)) {
            foreach ($owner_det as $owner_det_sgl) {
                $owner_det_ar[$owner_det_sgl['company_id']] = $owner_det_sgl['owner_id'];
                $company_owner_ar[$owner_det_sgl['owner_id']] = $owner_det_sgl['company_id'];
            }
            $owner_user_det = $this->User->find('all')->where(['bullhorn_entity_id IN' => $owner_det_ar])
                            ->select(['bullhorn_entity_id', 'firstName', 'lastName', 'email'])->toArray();
            if (!empty($owner_user_det)) {
                foreach ($owner_user_det as $owner_user_sgl) {
                    /* $company_id = 0;
                      if (isset($company_owner_ar[$owner_user_sgl['bullhorn_entity_id']]))
                      $company_id = $company_owner_ar[$owner_user_sgl['bullhorn_entity_id']];
                      $cmpy_owner_det[$company_id] = array(
                      'bullhorn_entity_id' => $owner_user_sgl['bullhorn_entity_id'],
                      'firstName' => $owner_user_sgl['firstName'],
                      'lastName' => $owner_user_sgl['lastName'], 'email' => $owner_user_sgl['email']
                      ); */
                    foreach ($owner_det_ar as $company_id => $owner_id) {
                        if ($owner_user_sgl['bullhorn_entity_id'] == $owner_id) {
                            $cmpy_owner_det[$company_id] = array(
                                'bullhorn_entity_id' => $owner_user_sgl['bullhorn_entity_id'],
                                'firstName' => $owner_user_sgl['firstName'],
                                'lastName' => $owner_user_sgl['lastName'], 'email' => $owner_user_sgl['email']
                            );
                        }
                    }
                }
            }
        }
        $company_det = $this->get_company_list($company_id_ar, $type = 'detail', 'clientContactSorted');
        $openJobs = $this->getOpenJobsCount($company_id_ar);

        foreach ($company_det['data'] as $key => $company) {
            $company_det['data'][$key]['nofOpenPositions'] = isset($openJobs[$company_det['data'][$key]['id']]) ? count($openJobs[$company_det['data'][$key]['id']]) : 0;
        }
        echo json_encode(array($company_det, $cmpy_owner_det));
        //echo "<pre>";print_r($openJobs); echo "</pre>";
        //echo "<pre>";print_r($company_det); echo "</pre>";
    }

    /*
     * Action       : forgotPassword
     * Description  : send email with reset link to reset password
     * Request Input: email
     */

    public function forgotPassword() {
        $this->autoRender = false;
        $params = $this->request->data;
        $userTable = TableRegistry::get('User');
        //echo "<pre>";print_r($params); echo "</pre>"; exit;
        if (!isset($params['email'])) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No email address is given!"
                    ]
            );
            exit;
        }
        $getUser = $userTable->find()->select(['id', 'email', 'firstName', 'lastName'])->where(['email' => $params['email']])->toArray();
        if (!empty($getUser)) {
            $resetTable = TableRegistry::get('EmailVerification');
            $getResetLink = $resetTable->find()->select()->where(['user_id' => $getUser[0]['id'], 'is_user_registr' => 0])->toArray();
            if (!empty($getResetLink)) {
                $token = $getResetLink[0]['token'];
            } else {
                $token = md5(uniqid(mt_rand(), true));
                $reset = $resetTable->newEntity();
                $reset->user_id = $getUser[0]['id'];
                $reset->token = $token;
                $reset->is_verified = 0;
                $reset->is_user_registr = 0;
                $reset->created_at = strtotime(date('d-m-Y h:i a', time()));
                $resetTable->save($reset);
            }
            if (isset($params['site']) && $params['site'] == "marketing") {
                $restLink = WEB_SERVER_ADDR_MARKETING . 'forgot_reset.php?token=' . $token;
            } else {
                $restLink = WEB_SERVER_ADDR . 'users/confirmation/' . $token;
            }
            $email = new Email();
            $email->template('forgot_password', 'user')
                    ->emailFormat('html')
                    ->viewVars(['var' => ['firstName' => $getUser[0]['firstName'], 'lastName' => $getUser[0]['lastName'], 'resetLink' => $restLink]])
                    ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                    ->to($params['email'])
                    ->subject('Reset your password in People Caddie!')
                    ->send();
            echo json_encode(
                    [
                        'status' => 1,
                        'message' => "Please check your inbox to verify your email address for resetting the password!"
                    ]
            );
            exit;
        } else {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No email available!"
                    ]
            );
            exit;
        }
    }

    /*
     * Action       : confirmation
     * Description  : to verify the token passed to reset the password and verify email
     * Request Input: token
     */

    public function confirmation() {
        $this->autoRender = false;
        $params = $this->request->data;
        if (!isset($params['token'])) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No reset token is given!"
                    ]
            );
            exit;
        }
        $resetTable = TableRegistry::get('EmailVerification');
        $getResetLink = $resetTable->find()->select()->where(['token' => $params['token']])->toArray();
        if (!empty($getResetLink)) {
            if ($getResetLink[0]['is_verified']) {
                if ($getResetLink[0]['is_user_registr']) {
                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => "Your email address is already verified. Please login!",
                                'data' => [
                                    'user_id' => $getResetLink[0]['user_id']
                                ]
                            ]
                    );
                } else {
                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Your email address is already verified!",
                                'data' => [
                                    'user_id' => $getResetLink[0]['user_id']
                                ]
                            ]
                    );
                }
                exit;
            } else {
                $reset = $resetTable->get($getResetLink[0]['id']);
                $reset->id = $getResetLink[0]['id'];
                $reset->is_verified = 1;
                if ($getResetLink[0]['is_user_registr'] && $resetTable->save($reset)) {
                    //echo  TableRegistry::get('Notifications')->send_client_admin_request_received($getResetLink[0]['user_id'],$getResetLink[0]['id']); // sends email to sales rep or super admin
                    $getUser = TableRegistry::get('Users')->find()->select(['role'])->where(['id' => $getResetLink[0]['user_id']])->toArray();
                    if (!empty($getUser) && $getUser[0]['role'] == COMPANY_ADMIN_ROLE) {
                        $notify_infos = TableRegistry::get('NotificationType');
                        $notify_info = $notify_infos->get_notification_type();
                        $sale_rep_or_super_admin = TableRegistry::get('Users')->get_owner_info($getResetLink[0]['user_id']);
                        if (!empty($sale_rep_or_super_admin)) {
                            $email_text[] = [
                                'to' => $sale_rep_or_super_admin[0]['email'],
                                'subject' => $notify_info[CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED]['type'],
                                'message' => [
                                    'clientAdmin' => $sale_rep_or_super_admin[0],
                                    'message' => $notify_info[CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED]['message_text']
                                ]
                            ];
                            $email_text = $this->Notification->email($email_text);
                        }
                    }
                    //if ($email_text['data'][0]['isSent']) {
                    if (!empty($getUser) && $getUser[0]['role'] != COMPANY_ADMIN_ROLE) {
                        TableRegistry::get('Users')->query()->update()->set(['isActive' => 1])
                                ->where(['id' => $getResetLink[0]['user_id']])
                                ->execute(); // Activate user once email is verified
                    }
                    if (!is_null($getResetLink[0]['id']) && !empty($getResetLink[0]['id']) && $getResetLink[0]['id'] != 0) {
                        TableRegistry::get('Notifications')->query()->update()->set(['status' => 0])
                                ->where(['email_verify_id' => $getResetLink[0]['id']])
                                ->execute(); // Disable further notification once email is verified
                    }
                    echo json_encode(
                            [
                                'status' => 1,
                                'is_admin_verified' => 1,
                                'message' => "Email address is verified successfully!",
                                'data' => [
                                    'user_id' => $getResetLink[0]['user_id']
                                ]
                            ]
                    );
                    //}
                    exit;
                } else
                if ($resetTable->save($reset)) {
                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Your email address is verified successfully!",
                                'data' => [
                                    'user_id' => $getResetLink[0]['user_id']
                                ]
                            ]
                    );
                    exit;
                } else {
                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => $reset->errors()
                            ]
                    );
                    exit;
                }
            }
        } else {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No reset token is available or may be expired, Please try again!"
                    ]
            );
            exit;
        }
    }

    /*
     * Action       : resetPassword
     * Description  : to reset the password
     * Request Input: password, user_id
     */

    public function resetPassword() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $userTable = TableRegistry::get('Users');
        $resetTable = TableRegistry::get('EmailVerification');
        $getResetLink = $resetTable->find()->select()->where(['user_id' => $params['user_id'], 'is_user_registr' => 0])->toArray();
        if (!empty($getResetLink)) {
            if ($getResetLink[0]['is_verified']) {
                $reset = $userTable->get($getResetLink[0]['user_id']);
                $user_id = $getResetLink[0]['user_id'];
                $getUserBullhornId = $reset->bullhorn_entity_id;
                $firstName = $reset->firstName;
                $lastName = $reset->lastName;
                $user_email = $reset->email;
                $reset->id = $user_id;
                $reset->password = $params['password'];
                $userTable->patchEntity($reset, $params);
                if ($userTable->save($reset)) {
                    $response = [];
                    if ($getUserBullhornId != 0) {
                        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $getUserBullhornId . '?BhRestToken=' . $_SESSION['BH']['restToken'];
                        $bhArr = [
                            'id' => $getUserBullhornId,
                            'password' => $params['password']
                        ];
                        $post_params = json_encode($bhArr);
                        $req_method = 'POST';
                        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                    }
                    $email = new Email();
                    $email->template('password_reset_success', 'user')
                            ->emailFormat('html')
                            ->viewVars(['var' => ['firstName' => $firstName, 'lastName' => $lastName, 'email' => $user_email, 'password' => $params['password']]])
                            ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                            ->to($user_email)
                            ->subject('Password reset success!')
                            ->send();
                    //$resetTable->delete($resetTable->get($getResetLink[0]['id']));
                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Password reset done successfully!",
                                'data' => [
                                    'user_id' => $user_id,
                                    'bhData' => isset($response['data']) ? $response['data'] : null
                                ]
                            ]
                    );
                    exit;
                } else {
                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => $reset->errors()
                            ]
                    );
                    exit;
                }
            } else {
                echo json_encode(
                        [
                            'status' => 0,
                            'message' => "Please check your inbox to verify your email address for ressetting the password!",
                            'data' => [
                                'user_id' => $getResetLink[0]['user_id']
                            ]
                        ]
                );
                exit;
            }
        } else {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No reset token is available or may be expired, Please try again!"
                    ]
            );
            exit;
        }
    }

    /*
     * Description: Get open jobs count for companies
     */

    public function getOpenJobsCount($company_ids = null) {
        if (!empty($company_ids) || $company_ids == null) {
            $this->BullhornConnection->BHConnect();
            $control_company_ids = implode(',', $company_ids);
            $url = $_SESSION['BH']['restURL'] . '/query/JobOrder?where=clientCorporation.id+IN+(' . $control_company_ids . ')+AND+'
                    . 'isOpen=true+AND+isDeleted=false&count=5000'
                    . '&fields=id,title,clientCorporation&BhRestToken=' . $_SESSION['BH']['restToken'];

            $post_params = json_encode([]);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $companyOpenJobs = [];
            if (isset($response['data'])) {
                foreach ($response['data'] as $companyjobs) {
                    $companyOpenJobs[$companyjobs['clientCorporation']['id']][] = ['jobOrderId' => $companyjobs['id'], 'title' => $companyjobs['title']];
                }
                //echo "<pre>";print_r($companyOpenJobs); echo "</pre>";
                return $companyOpenJobs;
            } else {
                return [];
            }
        }
    }

    /*
     * Action       : adminDashboard
     * Description  : to get the company administrator for approval
     * Request Input:
     */

    public function adminDashboard() {
        $this->autoRender = false;
        $userTable = TableRegistry::get('User');
        $getUsers = $userTable->find()->select(['bullhorn_entity_id'])->where(['role' => COMPANY_ADMIN_ROLE, 'isActive' => 0])->toArray();
        $companyAdmins = [];
        if (!empty($getUsers)) {
            foreach ($getUsers as $cadmins) {
                $companyAdmins[] = $cadmins['bullhorn_entity_id'];
            }
            $this->BullhornConnection->BHConnect();
            $admin_ids = implode(',', $companyAdmins);
            $url = $_SESSION['BH']['restURL'] . '/entity/ClientContact/' . $admin_ids . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,name,firstName,lastName,email,clientCorporation';
            $post_params = json_encode([]);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            if (isset($response['data'])) {
                echo json_encode([
                    'status' => 1,
                    'data' => $response['data']
                ]);
            } else {
                echo json_encode([
                    'status' => 0,
                    'data' => 'Unable to fetch records from Bullhorn'
                ]);
            }
            exit;
        } else {
            echo json_encode([
                'status' => 0,
                'message' => 'There is no users to approve'
            ]);
        }
    }

    /*
     * Action       : adminApproval
     * Description  : to approve company admin
     * Request Input: bullhorn_entity_id, role
     */

    public function adminApproval() {
        $this->autoRender = false;
        $params = $this->request->data;
        if (!isset($params['bullhorn_entity_id'])) {
            echo json_encode([
                'status' => 0,
                'message' => 'Company admin bullhorn id is required'
            ]);
            exit;
        }
        $userTable = TableRegistry::get('User');
        $getUsers = $userTable->find()->select(['id'])->where(['bullhorn_entity_id' => $params['bullhorn_entity_id'], 'isActive' => 0])->toArray();
        if (!empty($getUsers)) {
            $user = $userTable->get($getUsers[0]['id']);
            $user->id = $getUsers[0]['id'];
            $user->isActive = 1;
            if (isset($params['role'])) {
                $user->role = $params['role'];
            }
            if ($userTable->save($user)) {
                echo json_encode([
                    'status' => 1,
                    'message' => 'Approved!'
                ]);
            } else {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Unable to approve!'
                ]);
            }
            exit;
        } else {
            echo json_encode([
                'status' => 0,
                'message' => 'There is no users to approve'
            ]);
        }
    }

    /*
     * Action       : helpContact
     * Description  : to contact people caddie
     * Request Input: subject,message,to (for testing), phone, platform = PC-Marketing, email, name
     */

    public function helpContact() {
        $this->autoRender = false;
        $params = $this->request->data;
        $to = "";
        if (isset($params['to'])) {
            $to = trim($params['to']); // this is for developer testing who can give any email to check
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Please provide a valid email address'
                ]);
                exit;
            }
        } else {
            //$to = 'peoplecaddie@mailinator.com';
            $to = 'support@peoplecaddie.com';
        }

        $name = isset($params['name']) ? $params['name'] : "People Caddie";
        $emailAdd = isset($params['email']) ? $params['email'] : "peoplecaddie@mailinator.com";
        $emailAdd = trim($emailAdd); // this is for developer testing who can give any email to check
        if (!filter_var($emailAdd, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 0,
                'message' => 'Please provide a valid email address'
            ]);
            exit;
        }
        $phone = isset($params['phone']) ? $params['phone'] : "";
        $platform = isset($params['platform']) ? $params['platform'] == 'iOS' ? 1 : ($params['platform'] == 'Android' ? 2 : ($params['platform'] == 'PC-Marketing' ? 3 : 0)) : -1;
        $subject = isset($params['subject']) ? $params['subject'] : "New Contact form message";
        $contactfrom = "";
        if ($platform != 0 || $platform != -1) {
            $contactfrom = $params['platform'];
        }
        if (isset($params['message']) && !empty($params['message'])) {
            try {
                $var = ['name' => $name, 'email' => $emailAdd, 'phone' => $phone, 'message' => $params['message'], 'contact_via' => $contactfrom];
                $email = new Email();
                $email->template('contactus', 'user')
                        ->emailFormat('html')
                        ->from([$emailAdd => $name])
                        ->to([$to => 'People Caddie'])
                        ->viewVars(['var' => $var])
                        ->subject($subject)
                        ->send();
                $contactusTable = TableRegistry::get('Contactus');
                $contact = $contactusTable->newEntity();
                $contact->name = $name;
                $contact->email = $emailAdd;
                $contact->phone = $phone;
                $contact->device_type = $platform;
                $contact->message = $params['message'];
                $contactusTable->save($contact);
                echo json_encode([
                    'status' => 1,
                    'message' => 'Your message was sent successfully!!!'
                ]);
                exit;
            } catch (Exception $e) {
                echo json_encode([
                    'status' => 0,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
        } else {
            echo json_encode([
                'status' => 0,
                'message' => 'Please give your comments'
            ]);
            exit;
        }
    }

    /*
     * Action       : inviteFriends
     * Description  : to invite friends from people caddie app
     * Request Input: emails in array format
     */

    public function inviteFriends() {
        $this->autoRender = false;
        $params = $this->request->data;
        $name = isset($params['name']) ? ucwords($params['name']) : "";
        //echo json_encode($params); exit;
        if (!isset($params['emails']) && !is_array($params['emails'])) {
            echo json_encode([
                'status' => 0,
                'message' => 'Please provide email addresses'
            ]);
            exit;
        }
        $validateEmails = [];
        foreach ($params['emails'] as $emailAdd) {
            $emailAdd = trim($emailAdd);
            if (!filter_var($emailAdd, FILTER_VALIDATE_EMAIL)) {
                $validateEmails['invalid'][] = $emailAdd;
            } else {
                $validateEmails['valid'][] = $emailAdd;
            }
        }
        //echo json_encode($validateEmails); exit;
        try {
            if (isset($validateEmails['valid']) && count($validateEmails['valid']) > 0) {
                $email = new Email();
                //$check = [];
                foreach ($validateEmails['valid'] as $e) {
                    $email->to($e);
                    //$check[] = $e;
                
                /*$email->from([EMAIL_FROM_ADDRESS => 'People Caddie - Apps'])
                        ->subject('Invite from People Caddie!')
                        ->send('Hi, This is ' . $name . ' and I would like to share about People caddie app. It is such a great app that helps you to go next level of your life. Experience the progress!!!'); */
                $email->template('invite_friends', 'user')
                               ->emailFormat('html')
                               ->viewVars(['var' => ['name' => $name]])
                               ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                               ->subject($name.' has invited you to join PeopleCaddie')
                               ->send();
                }
                if (isset($validateEmails['invalid']) && count($validateEmails['invalid']) > 0) {
                    echo json_encode([
                        'status' => 1,
                        'data' => [
                            'valid' => $validateEmails['valid'],
                            'invalid' => $validateEmails['invalid'],
                        ],
                        'message' => 'Your message was sent successfully to valid email addresses.!!!'
                    ]);
                    exit;
                } else {
                    echo json_encode([
                        'status' => 1,
                        'message' => 'Your message was sent successfully!!!'
                    ]);
                    exit;
                }
            } else {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Please provide us atleast one valid email address'
                ]);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 0,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /*     * ************************************************************************
     * Function name   : appRedirect
     * Description     : To redirect the URL for linked in app
     * Created Date    : 03-11-2016
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * ************************************************************************ */

    public function appRedirect() {
        $this->viewBuilder()->layout(false);
        $this->autoRender = false;
//        $obj = new stdClass();
//        $obj->code=$_GET["code"];
//        $obj->state=$_GET["state"];
        if (isset($_GET["code"]) && isset($_GET["state"])) {
            header('Location: peoplecaddie://com.peoplecaddie.app?code=' . $_GET["code"] . '&state=' . $_GET["state"]);
        }
        exit;
    }

    /*
     * *************************************************************************************
     * Function name   : checkuser
     * Description     : For checking user by given email
     * Created Date    : 07-11-2016
     * Created By      : Siva.G
     * *************************************************************************************
     */

    public function checkuser() {
        $this->viewBuilder()->layout(false);
        $this->autoRender = false;
        $params = $this->request->data;
        $userTable = TableRegistry::get('Users');
        $getUser = $userTable->find()->where(['email' => $params['email']])->first();
        if (!empty($getUser)) {
            if ($getUser->isActive) {
                echo $this->getuserdata($getUser);
            } else {
                echo json_encode(array('result' => 0, 'error' => 'Unauthorized access!'));
                exit;
            }
//                }
        } else {
            echo json_encode(array('result' => 0, 'error' => 'Email not exists!'));
        }
    }

    public function getuserdata($getUser) {
        $session = $this->request->session();
        $last_login = $session->read('LastLogin');
        return json_encode(
                array(
                    'result' => 1,
                    'username' => $getUser->username,
                    'firstName' => $getUser->firstName,
                    'lastName' => $getUser->lastName,
                    'profile_picture' => "",
                    'email' => $getUser->email,
                    'access_token' => $getUser->access_token,
                    'bullhorn_entity_id' => $getUser->bullhorn_entity_id,
                    'role' => $getUser->role,
                    'user_role' => $getUser->role,
                    'company_id' => $getUser->company_id,
                    'user_id' => $getUser->id,
                    'headshot' => $getUser->headshot,
                    'phone' => $getUser->phone,
                    'ratings' => $getUser->rating,
                    'employmentPreference' => $getUser->employmentPreference,
                    'last_login' => $last_login,
                    'customTextBlock4' => $getUser->customTextBlock4
        ));
    }

    public function checkDeviceId($user_det) {
        return TableRegistry::get('Users')->query()->update()->set(['device_id' => null])
                        ->where(['id <>' => $user_det->id, 'device_id' => $user_det->device_id])
                        ->execute(); // remove other user with same device id
    }
    /*
     * Fetch the category list values 
     */
    public function fetchCategory($params = null) {
        $this->autoRender = false;
        $catTable = TableRegistry::get('Category');
        $response = [];
        $response['data'] = $catTable->find('all',['order' => ['category_name' => 'ASC']])->select(['bullhorn_entity_id','category_name'])->hydrate(false)->toArray();
        echo json_encode($response);
    }
    
    /*
     * Fetch the titles values only
     */
    public function fetchTitlesAll($params = null) {
        $this->autoRender = false;
        $titleTable = TableRegistry::get('Titles');
        $response = [];
        $params = $this->request->data();
        if(isset($params['category_id']) && !empty($params['category_id'])) {
            $titleValues = $titleTable->find('all',['order' => ['title_name' => 'ASC']])->select(['bullhorn_title_id','title_name'])->where(['bullhorn_category_id' => $params['category_id']])->hydrate(false);
            if(!empty($titleValues)) {
                $response = $titleValues->toArray();
                echo json_encode($response);
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please send the category id')); 
        }
    }
    
    /*
     * Fetch all the titles values based on the category
     */
    public function fetchAllTitles($params = null) {
        $this->autoRender = false;
        $titleTable = TableRegistry::get('Titles');
        $response = [];
        $params = $this->request->data();
        if(isset($params['category_id']) && !empty($params['category_id'])) {
            $titleValues = $titleTable->find('all',['order' => ['title_name' => 'ASC']])->select(['bullhorn_title_id','title_name'])->hydrate(false);
            if(!empty($titleValues)) {
                $response = $titleValues->toArray();
                echo json_encode($response);
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please send the category id')); 
        }
    }
    
    
    /*
     * Fetch the titles values only
     */
    public function fetchSkills($params = null) {
        $this->autoRender = false;
        $skillTable = TableRegistry::get('SkillList');
        $response = [];
        $params = $this->request->data();
        //$params['category_id'] = 2000010;
        if(isset($params['category_id']) && !empty($params['category_id'])) {
            $skillValues = $skillTable->find('all',['order' => ['skill_name' => 'ASC']])->select(['bullhorn_skill_id','skill_name'])->where(['bullhorn_category_id' => $params['category_id']])->hydrate(false);
            if(!empty($skillValues)) {
                $response['status'] = 1;
                $response['data'][0]['id'] = $params['category_id'];
                $response['data'][0]['skills']['data'] =  $skillValues->toArray();
                echo json_encode($response);
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please send the category id')); 
        }
    }
    
    /*
     * Fetch all the skills values based on the category
     */
    public function fetchAllSkills($params = null) {
        $this->autoRender = false;
        $skillTable = TableRegistry::get('SkillList');
        $response = [];
        $params = $this->request->data();
        //$params['category_id'] = 2000010;
        if(isset($params['category_id']) && !empty($params['category_id'])) {
            $skillValues = $skillTable->find('all',['order' => ['skill_name' => 'ASC']])->select(['bullhorn_skill_id','skill_name'])->hydrate(false);
            if(!empty($skillValues)) {
                $response['status'] = 1;
                $response['data'][0]['id'] = $params['category_id'];
                $response['data'][0]['skills']['data'] =  $skillValues->toArray();
                echo json_encode($response);
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please send the category id')); 
        }
    }
    /*
     * *************************************************************************************
     * Function name   : fetchTitle
     * Description     : For fetching the title and skill values based on the category values
     * Created Date    : 17-03-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function fetchTitle($params = null) {
        $this->autoRender = false;
        $titleTable = TableRegistry::get('Titles');
        $skillTable = TableRegistry::get('SkillList');
        $response = $data = []; 
        $params = $this->request->data();
        //$params['category_id'] = 2000010;
        //$params['category_id'] = 2000009;
        if(isset($params['category_id']) && !empty($params['category_id'])) {
            $titleValues = $titleTable->find('all',['order' => ['title_name' => 'ASC']])->select(['bullhorn_title_id','title_name'])->where(['bullhorn_category_id' => $params['category_id']])->hydrate(false);
            if(!empty($titleValues)) {
                $response['titles'] = $titleValues->toArray();
                $skillValues = $skillTable->find('all',['order' => ['skill_name' => 'ASC']])->select(['bullhorn_skill_id','skill_name'])->where(['bullhorn_category_id' => $params['category_id']])->hydrate(false);
                if(!empty($skillValues)){
                $response['skills'] = $skillValues->toArray();
                
                echo (!empty($response['titles']) && !empty($response['skills'])) ? json_encode($response) : json_encode(array('result' => 0,'message' => 'There is no title and skills available for the selected category'));
                }
            } else {
                echo json_encode(array('status' => 0, 'message' => 'There is no title and skills available for the selected category')); 
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please send the category id')); 
        }
    }
    /*
     *  Redirect to the marketing site with shorten URL  
     */

    public function k2() {
        $this->autoRender = false;
        //Detect special conditions devices
        
        $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        $Android = stripos($_SERVER['HTTP_USER_AGENT'],"Android");
        
        if( $iPad || $iPhone ){
            $this->redirect();
        }else if($Android){
            $this->redirect();
        } else {
            $this->redirect();
        }
    }
    
        /*
     * *************************************************************************************
     * Function name   : fetchTitle
     * Description     : For fetching the title and skill values based on the category values
     * Created Date    : 17-03-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function fetchTitleSkill($params = null) {
        $this->autoRender = false;
        $catTable = TableRegistry::get('Category');
        $titleTable = TableRegistry::get('Titles');
        $skillTable = TableRegistry::get('SkillList');
        $response = $data = []; 
        $params = $this->request->data();
        if(isset($params['category_id']) && !empty($params['category_id'])) {
            
            $catValues = $catTable->find()->select(['bullhorn_entity_id','category_name'])->where(['bullhorn_entity_id' => $params['category_id']])->first();
            if(!empty($catValues)) {
                $data = $catValues->toArray();
                $response['data']['id'] = $data['bullhorn_entity_id'];
                $response['data']['name'] = $data['category_name'];
                    $skillValues = $skillTable->find('all',['order' => ['skill_name' => 'ASC']])->select(['bullhorn_skill_id','skill_name'])->where(['bullhorn_category_id' => $params['category_id']])->hydrate(false);
                        if(!empty($skillValues)){
                            $response['data']['skills']['data'] = $skillValues->toArray();
                            foreach($response['data']['skills']['data'] as $key => $value) {
                                $response['data']['skills']['data'][$key] ['id'] = $response['data']['skills']['data'][$key] ['bullhorn_skill_id'];
                                $response['data']['skills']['data'][$key] ['name'] = $response['data']['skills']['data'][$key] ['skill_name'];
                                unset($response['data']['skills']['data'][$key] ['bullhorn_skill_id']);
                                unset($response['data']['skills']['data'][$key] ['skill_name']);
                            }
                            echo (!empty($response) && !empty($response)) ? json_encode($response) : json_encode($response);
                        }
            } else {
                echo json_encode(array('status' => 0, 'message' => 'There is no skills available for the selected category')); 
            }
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Please send the category id')); 
        }
    }
    
    /*
     * *************************************************************************************
     * Function name   : privacyPolicy
     * Description     : For fetching the policy content
     * Created Date    : 23-02-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function privacyPolicy() {
        $this->viewBuilder()->layout('static_content');
    }
    
    /*
     * *************************************************************************************
     * Function name   : termsConditions
     * Description     : For fetching the terms content
     * Created Date    : 23-02-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function termsConditions() {
        $this->viewBuilder()->layout('static_content');
    }
    
        /*
     *  To download and upload the drive file
     */
    public function driveFileUpload($params = null) {
        $this->viewBuilder()->layout(false);
        $this->autoRender = false;
        $params = $this->request->data();
//        if (!isset($_GET['code'])) {
//        $params['file_id'] = '0B3IyBUAo_RyFOFQ4ZVkwc3hZOE0';
//        $params['id'] = 2883;
//         }
        $CLIENT_ID = '618188695745-fq5srn59sagmba6bqbb7ut632ltdg4f8.apps.googleusercontent.com';
        $CLIENT_SECRET = 'jDGeav52wKCHUo27SHRgLCs9';
        $YOUR_API_KEY = 'AIzaSyBq7G3G2YWynuOoClQ67cRKuN-rReSEs1Q';
        if (isset($_GET['code'])) {
            if(isset($_SESSION['current_user_id'])){
                $user_id=$_SESSION['current_user_id'];
                if(isset($_SESSION['file_id_'.$user_id])){
                    $params['file_id']=$_SESSION['file_id_'.$user_id];
                    $params['id']=$user_id;
                }
            }
            $code = $_GET['code'];
            $url = 'https://accounts.google.com/o/oauth2/token';
            $params1 = array(
                "code" => $code,
                "client_id" => '618188695745-fq5srn59sagmba6bqbb7ut632ltdg4f8.apps.googleusercontent.com',
                "client_secret" => 'jDGeav52wKCHUo27SHRgLCs9',
                "redirect_uri" => DRIVE_REDIRECT_URL_DEV,
                "grant_type" => "authorization_code"
            );
            $ch = curl_init();
            curl_setopt($ch, constant("CURLOPT_" . 'URL'), $url);
            curl_setopt($ch, constant("CURLOPT_" . 'POST'), true);
            curl_setopt($ch, constant("CURLOPT_" . 'POSTFIELDS'), $params1);
            $output = curl_exec($ch);
            curl_close($ch);
            if(!empty($params['file_id'])) {
                $FILE_ID = $params['file_id'];
                $fileURL  = "https://www.googleapis.com/drive/v2/files/$FILE_ID?alt=media&key=" . $YOUR_API_KEY;
                $headers = get_headers($fileURL, 1);
                $type = $headers["Content-Type"];
                if(!empty($type)){
                     if(($type === 'application/msword') || ($type === 'application/pdf')) {
                            $this->BullhornConnection->BHConnect();
                            $attachmentTable = TableRegistry::get('Attachment');
                            $target_dir = WWW_ROOT . "uploads" . DS . "peoplecaddie" . DS . "files" . DS;
                            $uploadedFileIds = [];
                            $fileContent = [];
                            $timestamp = time();
                            
                            $params['fileContent'] = base64_encode(file_get_contents($fileURL));
                            $params['contentType'] = $type;
                            $params['name'] = $FILE_ID;
                            $params['fileType'] = 'SAMPLE'; // always use SAMPLE
                            $params['externalID'] = 'portpolio';
                            $url = $_SESSION['BH']['restURL'] . '/file/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
                            $post_params = json_encode($params);
                            $req_method = 'PUT';
                            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                            if(!empty($response['fileId'])) {
                                if($type === 'application/msword') {
                                    $storeLocation = $target_dir.'/'.$params['id'] . "_" . $timestamp . "_" . $FILE_ID;
                                } elseif($type === 'application/pdf') {
                                    $storeLocation = $target_dir.'/'.$params['id'] . "_" . $timestamp . "_" . $FILE_ID.'.pdf';
                                } else {
                                    echo 'There is error while uploading your file'; exit;
                                }
                                $PDF_CONTENTS = file_get_contents($fileURL);
                                $upload = file_put_contents($storeLocation, $PDF_CONTENTS);
                                    if($upload){
                                        $attachment = $attachmentTable->newEntity();
                                        $pcFiles = [
                                        'candidate_id' => $params['id'],
                                        'file_id' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                                        'filename' => $FILE_ID,
                                        'filepath' => "uploads/peoplecaddie/files/" . $params['id'] . "_" . $timestamp . "_" . $FILE_ID
                                        ];
                                        $attachmentTable->patchEntity($attachment, $pcFiles);
                                        if ($result = $attachmentTable->save($attachment)) {

                                            $uploadedFileIds['result'][] = [
                                                'fileId' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                                                'status' => (isset($response['fileId']) && !is_null($response['fileId'])) ? 1 : 0,
                                                'pcData' => [
                                                    'id' => $result->id,
                                                    'filename' => $result->filename,
                                                    'filepath' => $result->filepath
                                                ]
                                            ];
                                        }
                                        
                                    } else {
                                        $uploadedFileIds['result'][] = [
                                            'fileId' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                                             'status' => 0
                                        ];
                                    }
                            }
                            echo json_encode($uploadedFileIds);
                     }
                } else {
                    echo json_encode(array('status' => 0,'message' => 'Invalid File Format'));
                }
            } else {
                echo json_encode(array('status' => 0,'message' => 'Please send the File ID'));
            }
        } else {
            $url = "https://accounts.google.com/o/oauth2/auth";
            $_SESSION['file_id_'.$params['id']] = $params['file_id'];
            $_SESSION['current_user_id'] = $params['id'];
            $params1 = array(
                "response_type" => "code",
                "client_id" => '618188695745-fq5srn59sagmba6bqbb7ut632ltdg4f8.apps.googleusercontent.com',
                "redirect_uri" => DRIVE_REDIRECT_URL_DEV,
                "scope" => "https://www.googleapis.com/auth/plus.me"
            );

           $request_to = $url . '?' . http_build_query($params1);

            header("Location: " . $request_to); exit;
        }
    }
    
    /*
     * *************************************************************************************
     * Function name   : driveFileDownload
     * Description     : For downloading the file from drive
     * Created Date    : 08-05-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function driveFileDownload($params = null) {
        $this->viewBuilder()->layout(false);
        $this->autoRender = false;
        $tempFileName = '';
        $params = $this->request->data();
        if (isset($params['auth_token']) && !empty($params['auth_token'])) {
            if (!isset($params['file_id']) || empty($params['file_id'])) {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Please send the File ID'
                ]);
                exit;
            }
            if (!isset($params['id']) || empty($params['id'])) {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Please send the Contractor ID'
                ]);
                exit;
            }
            $token = $params['auth_token'];
            $CLIENT_ID = '618188695745-fq5srn59sagmba6bqbb7ut632ltdg4f8.apps.googleusercontent.com';
            $CLIENT_SECRET = 'jDGeav52wKCHUo27SHRgLCs9';
            $YOUR_API_KEY = 'AIzaSyD7kZnUERMjYqcQwBQMptBh5Dglhz1JZVo';
            if (!empty($token)) {
                $code = $token;
                $url = 'https://accounts.google.com/o/oauth2/token';
                $params1 = array(
                    "code" => $code,
                    "client_id" => '618188695745-fq5srn59sagmba6bqbb7ut632ltdg4f8.apps.googleusercontent.com',
                    "client_secret" => 'jDGeav52wKCHUo27SHRgLCs9',
                    "redirect_uri" => DRIVE_REDIRECT_URL_DEV,
                    "grant_type" => "authorization_code"
                );
                $ch = curl_init();
                curl_setopt($ch, constant("CURLOPT_" . 'URL'), $url);
                curl_setopt($ch, constant("CURLOPT_" . 'POST'), true);
                curl_setopt($ch, constant("CURLOPT_" . 'POSTFIELDS'), $params1);
                curl_setopt($ch, constant('CURLOPT_'. 'RETURNTRANSFER'),true);
                $output = curl_exec($ch);
                curl_close($ch);
                if (!empty($params['file_id'])) {
                    $FILE_ID = $params['file_id'];
                    $fileURL = "https://www.googleapis.com/drive/v3/files/$FILE_ID?alt=media&key=" . $YOUR_API_KEY;
                    $headers = get_headers($fileURL, 1);
                    if ($headers[0] == 'HTTP/1.1 404 Not Found') { //if the file not have public permission to edit/view
                        echo json_encode([
                            'status' => 0,
                            'message' => 'Please give permission to access/read the file'
                        ]);
                        exit;
                    }
                    $type = $headers["Content-Type"];
                    if (!empty($type)) {
                        if (($type === 'application/msword') || ($type === 'application/pdf')) {
                            $this->BullhornConnection->BHConnect();
                            $attachmentTable = TableRegistry::get('Attachment');
                            $target_dir = WWW_ROOT . "uploads" . DS . "peoplecaddie" . DS . "files" . DS;
                            $uploadedFileIds = [];
                            $fileContent = [];
                            $timestamp = time();

                            $params['fileContent'] = base64_encode(file_get_contents($fileURL));
                            $params['contentType'] = $type;
                            $params['name'] = $FILE_ID;
                            $params['fileType'] = 'SAMPLE'; // always use SAMPLE
                            $params['externalID'] = 'portpolio';
                            $url = $_SESSION['BH']['restURL'] . '/file/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
                            $post_params = json_encode($params);
                            $req_method = 'PUT';
                            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                            if (!empty($response['fileId'])) {
                                if ($type === 'application/msword') {
                                    $storeLocation = $target_dir . '/' . $params['id'] . "_" . $timestamp . "_" . $FILE_ID. '.doc';
                                    $tempFileName = $FILE_ID . '.doc';
                                } elseif ($type === 'application/pdf') {
                                    $storeLocation = $target_dir . '/' . $params['id'] . "_" . $timestamp . "_" . $FILE_ID . '.pdf';
                                    $tempFileName = $FILE_ID . '.pdf';
                                } else {
                                    echo json_encode([
                                        'status' => 0,
                                        'message' => 'Please select the valid file to import'
                                    ]);
                                    exit;
                                }
                                $PDF_CONTENTS = file_get_contents($fileURL);
                                $upload = file_put_contents($storeLocation, $PDF_CONTENTS);
                                if ($upload) {
                                    $attachment = $attachmentTable->newEntity();
                                    $pcFiles = [
                                        'candidate_id' => $params['id'],
                                        'file_id' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                                        'filename' => $tempFileName,
                                        'filepath' => "uploads/peoplecaddie/files/" . $params['id'] . "_" . $timestamp . "_" . $tempFileName
                                    ];
                                    $attachmentTable->patchEntity($attachment, $pcFiles);
                                    if ($result = $attachmentTable->save($attachment)) {

                                        $uploadedFileIds['result'][] = [
                                            'fileId' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                                            'status' => (isset($response['fileId']) && !is_null($response['fileId'])) ? 1 : 0,
                                            'pcData' => [
                                                'id' => $result->id,
                                                'filename' => $result->filename,
                                                'filepath' => $result->filepath
                                            ]
                                        ];
                                    }
                                } else {
                                    $uploadedFileIds['result'][] = [
                                        'fileId' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                                        'status' => 0
                                    ];
                                }
                            }
                            echo json_encode($uploadedFileIds);
                        } elseif ($type == 'application/json; charset=UTF-8') {
                            echo json_encode([
                                'status' => 0,
                                'message' => 'Please give permission to access/read the file'
                            ]);
                            exit;
                        } else {
                            echo json_encode([
                                'status' => 0,
                                'message' => 'Please select the valid file to upload'
                            ]);
                            exit;
                        }
                    } else {
                        echo json_encode(array('status' => 0, 'message' => 'Please select the valid file to upload'));
                    }
                } else {
                    echo json_encode(array('status' => 0, 'message' => 'Please send the File ID'));
                }
            } else {
                $url = "https://accounts.google.com/o/oauth2/auth";
                $params1 = array(
                    "response_type" => "code",
                    "client_id" => '618188695745-fq5srn59sagmba6bqbb7ut632ltdg4f8.apps.googleusercontent.com',
                    "redirect_uri" => DRIVE_REDIRECT_URL_DEV,
                    "scope" => "https://www.googleapis.com/auth/plus.me"
                );

                $request_to = $url . '?' . http_build_query($params1);

                header("Location: " . $request_to);
                exit;
            }
        } else {
            echo json_encode([
                'status' => 0,
                'message' => 'Please send the Auth token'
            ]);
        }
    }

}

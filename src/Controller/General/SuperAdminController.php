<?php

/* * ************************************************************************************
 * Class name      : SuperAdminController
 * Description     : SuperAdmin CRUD process
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

class SuperAdminController extends AppController {

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
     * Description   : To add the superadmin
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/general/SuperAdmin/?  
     * ***************************************************************************** */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $user_id = $this->user_save($params, SUPER_ADMIN_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        $access_token = $this->user_save($params, SUPER_ADMIN_ROLE, 'save');
        if ($access_token) {
            $response['access_token'] = $access_token;
            $response['result'] = 1;
        } else {
            $response = array('result' => 0, 'error' => 'Sorry, We faced failure in super admin create process');
        }
        echo json_encode($response);
    }

    /*     * ******************************************************************* 
     * Action Name   : view
     * Description   : To view the super admin details
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 26-08-2016 
     * Updated Date  : 26-08-2016 
     * Request Param : id =>user id
     * URL           : /general/superAdmin/view?  
     * ********************************************************************** */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        if (isset($params['id'])) {
            $user_id = $params['id'];
            $userTable = TableRegistry::get('Users');
            $user_det = $userTable->find()->select(['id', 'firstName', 'lastName', 'email', 'phone', 'bullhorn_entity_id', 'company_id', 'owner_id'])->where(['id' => $user_id])->toArray();
            if (!empty($user_det)) {
                $url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation/' . $user_det[0]['company_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,name,address';
                $post_params = json_encode([]); // Empty array
                $req_method = 'GET';
                $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                $user_det[0]['clientCorporation'] = isset($response['data']) ? $response['data'] : "";
                echo json_encode(
                        ['status' => 1,
                            'message' => 'Success!',
                            'data' => $user_det[0]
                        ]
                );
                exit;
            } else {
                echo json_encode(['status' => 0, 'message' => 'Please make sure you are passing valid user id']);
                exit;
            }
        } else {
            echo json_encode(['status' => 0, 'message' => 'Please make sure you are passing user id']);
            exit;
        }
    }

    /*     * ******************************************************************* 
     * Action Name   : update
     * Description   : To update the super admin details
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 26-08-2016 
     * Updated Date  : 26-08-2016 
     * Request Param : id => user id
     * URL           : /general/superAdmin/update?  
     * ********************************************************************** */

    public function update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        if (isset($params['id'])) {
            $status = $this->superAdminUpdate($params['id'], $params);
            if ($status != 1 && is_array($status)) {
                $error_msg = $this->format_validation_message($status);
                echo json_encode(array('result' => 0, 'error' => $error_msg));
                exit;
            } else {
                echo json_encode(['result' => 1, 'message' => 'Updated successfully!']);
                exit;
            }
        } else {
            echo json_encode(['result' => 0, 'message' => 'Please make sure you are passing user id']);
            exit;
        }
    }

    /*
     * Description: Update super addmin
     */

    public function superAdminUpdate($user_id = null, $params = null) {
        $userTable = TableRegistry::get('Users');
        $user_det = $userTable->get($user_id);
        $userTable->patchEntity($user_det, $params);
        $user_det->id = $user_id;
        if (!isset($params['username']) && !empty($params['username'])) {
            if (isset($params['email']) && !empty($params['email']))
                $user_det->username = $params['email'];
        }
        else {
            if (isset($params['username']) && !empty($params['username']))
                $user_det->username = $params['username'];
        }
        if (isset($params['password']) && !empty($params['password']))
            $user_det->password = $params['password'];
        if (isset($params['firstName']) && !empty($params['firstName']))
            $user_det->firstName = $params['firstName'];
        if (isset($params['lastName']) && !empty($params['lastName']))
            $user_det->lastName = $params['lastName'];
        if (isset($params['email']) && !empty($params['email']))
            $user_det->email = $params['email'];
        if (isset($params['phone']) && !empty($params['phone']))
            $user_det->phone = $params['phone'];
        
        $notify = (isset($params['notify']) && $params['notify'] == 1) ? 1 : 0;
        if ($user = $userTable->save($user_det)) {
            if ($notify == 1 && isset($params['password']) && !empty($params['password'])){
                        $updatedBy = '';
                        if (isset($params['updatedByUserId'])) {
                           $updatedByUser = $userTable->get($params['updatedByUserId']);
                           $updatedBy = ' '.$updatedByUser->firstName.' '.$updatedByUser->lastName.' a '.$updatedByUser->role.'.';
                          }else{ 
                            $updatedBy = ' yourself.';  
                          }
                           $email->template('password_updated_by', 'user')
                               ->emailFormat('html')
                               ->viewVars(['var' => ['firstName' => $user->firstName, 'lastName' => $user->lastName, 'password' => $params['password'],'updatedBy' => $updatedBy ]])
                               ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                               ->to($user->email)
                               ->subject('Your password has been updated!')
                               ->send();
                    }else if($notify == 1){
                         $email->template('profile_updated_by', 'user')
                               ->emailFormat('html')
                               ->viewVars(['var' => ['firstName' => $user->firstName, 'lastName' => $user->lastName]])
                               ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                               ->to($user->email)
                               ->subject('Your profile has been updated!')
                               ->send();
                    }
            return 1;
        } else {
            return $user_det->errors();
        }
    }

}

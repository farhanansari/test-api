<?php

/* * ************************************************************************************
 * Class name      : Applications Controller
 * Description     : Fetch applications submitted for a job order based on role
 * Created Date    : 22-09-2016
 * Created By      : Sivaraj Venkat
 * ************************************************************************************* */

namespace App\Controller\Entities;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Network\Session\DatabaseSession;
use Cake\Validation\Validator;
use Cake\Network\Http\Client;
use Cake\Datasource\EntityInterface;

class ApplicationsController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : index
     * Description   : To get applicant details by a company
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 22-09-2016
     * Updated Date  : 22-09-2016
     * URL           : /entities/applications/?
     * Request input : id => user id
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function index($params = null) {
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $role = "";
        $cond = "";
        $candidate_id = 0;
        $company_id = 0;
        $selected_client_det = array();
        $send_out_id_str = 0;
        if (isset($params['id'])) {
            $user_id = $params['id'];
            $userTable = TableRegistry::get('Users');
            $sendoutTable = TableRegistry::get('Sendout');
            $user_det = $userTable->find()->select()->where(['id' => $user_id])->toArray();
            if (!empty($user_det)) {
                $role = $user_det[0]['role'];
                $candidate_id = $user_det[0]['bullhorn_entity_id'];
            } else {
                echo json_encode(['status' => 0, 'message' => 'Please make sure you are passing valid user id']);
                exit;
            }
            $cond = (($role == COMPANY_ADMIN_ROLE) || ($role == HIRINGMANAGER_ROLE)) ? $sendoutTable->match_lists() : $sendoutTable->lists();
            $selected_client_det = (isset($params['type']) && !empty($params['type']) && ($params['type'] == 'dashboard')) ? $this->sendout_lists_grade() : $cond;
            $send_out_id_ar = array_keys($selected_client_det);
            $send_out_id_str = implode(",", $send_out_id_ar);
        } else {
            echo json_encode(['status' => 0, 'message' => 'Please make sure you are passing user id']);
            exit;
        }

        switch ($role) {
            case SUPER_ADMIN_ROLE:
                $control_company_ids = implode(',', $this->get_owned_company(SUPER_ADMIN_ROLE, $candidate_id));
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=clientCorporation.id+IN+(' . $control_company_ids . ')+AND+id+IN+(' . $send_out_id_str . ')&count=500'
                        . '&fields=id,dateAdded,candidate(id,firstName,lastName,email),clientCorporation(id,name),jobOrder(id,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'];
                break;
            case SALESREP_ROLE:
                $control_company_ids = implode(',', $this->get_owned_company(SALESREP_ROLE, $candidate_id));
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=clientCorporation.id+IN+(' . $control_company_ids . ')+AND+id+IN+(' . $send_out_id_str . ')&count=500'
                        . '&fields=id,dateAdded,candidate(id,firstName,lastName,email),clientCorporation(id,name),jobOrder(id,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'];
                break;
            case COMPANY_ADMIN_ROLE:
                $control_company_ids = implode(',', $this->get_owned_company(COMPANY_ADMIN_ROLE, $candidate_id));
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=clientCorporation.id+IN+(' . $control_company_ids . ')+AND+id+IN+(' . $send_out_id_str . ')&count=500'
                        . '&fields=id,dateAdded,candidate(id,firstName,lastName,email),clientCorporation(id,name),jobOrder(id,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'];
                break;
            case HIRINGMANAGER_ROLE:
                $control_company_ids = implode(',', $this->get_owned_company(HIRINGMANAGER_ROLE, $candidate_id));
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=jobOrder.clientContact.id+IN(' . $candidate_id . ')+AND+clientCorporation.id+IN+(' . $control_company_ids . ')+AND+id+IN+(' . $send_out_id_str . ')&count=500'
                        . '&fields=id,dateAdded,candidate(id,firstName,lastName,email),clientCorporation(id,name),jobOrder(id,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'];
                break;
            case CANDIDATE_ROLE:
                $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=candidate.id+IN+(' . $candidate_id . ')+AND+id+IN+(' . $send_out_id_str . ')&count=100'
                        . '&fields=id,dateAdded,candidate(id,firstName,lastName,email),clientCorporation(id,name),jobOrder(id,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'];
                break;
            default:
                break;
        }

        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $response = $this->check_zero_index($response);
        if (isset($response['data']) && !empty($response['data'])) {
//            if (!isset($response['data']['id'])) {
            $i = 0;
            foreach ($response['data'] as $sgl_data) {
                if (isset($selected_client_det[$sgl_data['id']]))
                    $response['data'][$i]['selected_for_client'] = $selected_client_det[$sgl_data['id']];

                if (isset($selected_client_det[$sgl_data['id']])) {
                    if ($selected_client_det[$sgl_data['id']] == 0) { // if appointment is not set, then allow admin to edit application
                        $response['data'][$i]['can_edit_by_admin'] = 1;
                    } else {
                        $response['data'][$i]['can_edit_by_admin'] = 0;    // if appointment time already setup, dont need to list the application
                    }
                } else {
                    $response['data'][$i]['can_edit_by_admin'] = 1;   // If there is no custom sendout in people caddie server, allow to edit
                }
                $i++;
            }
            $response['data'] = array_values($response['data']);
//            } else {
//                if (isset($selected_client_det[$response['data']['id']]))
//                    $response['data']['selected_for_client'] = $response['data']['id'];
//
//                if (isset($selected_client_det[$response['data']['id']])) {
//                    if ($selected_client_det[$response['data']['id']] == 0) { // if appointment is not set, then allow admin to edit application
//                        $response['data']['can_edit_by_admin'] = 1;
//                    } else {
//                        $response['data']['can_edit_by_admin'] = 0; // if appointment time already setup, dont need to list the application
//                    }
//                } else {
//                    $response['data']['can_edit_by_admin'] = 1;      // If there is no custom sendout in people caddie server, allow to edit
//                }
//
//                $changed_ar = $response['data'];
//                unset($response['data']);
//                $response['data'][] = $changed_ar;
//            }
        }
        echo json_encode(array($response, $selected_client_det));
    }
    
    /***************************************************************************
     * Function name   : sendout_lists_grade
     * Description     : Function for fetching the sendout details
     * Created Date    : 10-02-2017
     * Created By      : Balasuresh A
     **************************************************************************/
    public function sendout_lists_grade() {
        $performanceTable = TableRegistry::get('Performance');
        $sendoutTable = TableRegistry::get('Sendout');
        $placement_ids = $performanceTable->find('all')->select(['placement_id'])->where(['placement_id IS NOT NULL']);
        if(!empty($placement_ids)) {
            $placement_ids = $placement_ids->toArray();
            foreach($placement_ids as $placement_id) {
                $placement_list[] = $placement_id->placement_id;
            }
            $selected_client_det = $sendoutTable->find('list', array(
            'keyField' => 'sendout_id','valueField' => 'selected_for_client',
            'conditions' => array('selected_for_client !=' => 2, 'placement_id NOT IN' => $placement_list,'failed_to_show IS NULL')
            ));
            if(!empty($selected_client_det)) {
                $selected_client_det = $selected_client_det->toArray();
                return $selected_client_det;
            }
        }

    }

    /*     * ************************************************************************
     * Function      : client_dashboard
     * Description   : Get all open jobs and applications based on user role
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Request Input : id => user id
     * Created Date  : 06-10-2016
     * Updated Date  : 11-10-2016
     * ********************************************************************** */

    public function client_dashboard($params = null) {
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $role = "";
        $url = "";
        $candidate_id = 0;
        $company_id = 0;
        $sendoutTable = TableRegistry::get('Sendout');
        $selected_client_det = $sendoutTable->lists();      
        if (isset($params['id'])) {
            $user_id = $params['id'];
            $userTable = TableRegistry::get('Users');
            $user_det = $userTable->find()->select()->where(['id' => $user_id])->toArray();
            if (!empty($user_det)) {
                $role = $user_det[0]['role'];
                $candidate_id = $user_det[0]['bullhorn_entity_id'];
            } else {
                echo json_encode(['status' => 0, 'message' => 'Please make sure you are passing valid user id']);
                exit;
            }
        } else {
            echo json_encode(['status' => 0, 'message' => 'Please make sure you are passing user id']);
            exit;
        }

        if (in_array($role, [SUPER_ADMIN_ROLE, SALESREP_ROLE, COMPANY_ADMIN_ROLE, CANDIDATE_ROLE])) {
            $control_company_ids = implode('+OR+', $this->get_owned_company($role, $candidate_id));
            $url = $_SESSION['BH']['restURL'] . '/search/JobOrder?query=clientCorporation.id:(' . $control_company_ids . ')+AND+isOpen:true+AND+isDeleted:false&fields=id,'
                    . 'clientContact,clientCorporation,sendouts[500](id,candidate(id,firstName,lastName,email)),status,isDeleted,payRate,salary,salaryUnit,'
                    . 'startDate,dateAdded,dateClosed,dateEnd,title,customFloat1,customFloat2&count=1000&BhRestToken=' . $_SESSION['BH']['restToken'];
        } else if ($role == HIRINGMANAGER_ROLE) {
            $control_company_ids = implode('+OR+', $this->get_owned_company($role, $candidate_id));
            $url = $_SESSION['BH']['restURL'] . '/search/JobOrder?query=clientContact.id:' . $candidate_id . '+AND+clientCorporation.id:(' . $control_company_ids . ')+AND+isOpen:true+AND+isDeleted:false&fields=id,'
                    . 'clientContact,clientCorporation,sendouts[500](id,candidate(id,firstName,lastName,email)),status,isDeleted,payRate,salary,salaryUnit,'
                    . 'startDate,dateAdded,dateClosed,dateEnd,title,customFloat1,customFloat2&count=1000&BhRestToken=' . $_SESSION['BH']['restToken'];
        }

        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $senout_ids = [];
        $joborder_ids = [];
        $response=$this->check_zero_index($response);
        if (isset($response['data'])) {
            foreach ($response['data'] as $key1 => $sendout) {
                $joborder_ids[] = $sendout['id'];
                if (!empty($sendout['sendouts']['data'])) {
                    foreach ($sendout['sendouts']['data'] as $key2 => $sendout2) {
                        $senout_ids[] = $sendout2['id'];
                    }
                }
            }
          
            $result = (($role == COMPANY_ADMIN_ROLE) || ($role == HIRINGMANAGER_ROLE)) ? $this->getCustomCandidateSendout($senout_ids) : $this->getCustomCandidateSendoutInfo($senout_ids);
            
            $getSendouts = $this->getSendouts($joborder_ids);
            $i = 0;
            foreach ($response['data'] as $key1 => $sendout) {
                /* $getSendouts = $this->getSendouts($sendout['id']);
                  //pr($sendouts);
                  if (isset($getSendouts['data'])) {
                  $response['data'][$key1]['sendouts']['data'] = $getSendouts['data'];
                  } */
                if (!empty($getSendouts) && isset($getSendouts[$sendout['id']])) {
                    $response['data'][$key1]['sendouts']['data'] = $getSendouts[$sendout['id']];
                }
                if (!empty($response['data'][$key1]['sendouts']['data'])) {
                    foreach ($response['data'][$key1]['sendouts']['data'] as $key2 => $sendout2) {
                        $response['data'][$key1]['sendouts']['data'][$key2]['candidate']['pcData'] = isset($result[$sendout2['id']]) ? $result[$sendout2['id']] : "";

                        if (isset($selected_client_det[$sendout2['id']])) {
                            if ($selected_client_det[$sendout2['id']] == 0) { // if selected for client is not set, then allow admin to edit application
                                $response['data'][$key1]['sendouts']['data'][$key2]['can_edit_by_admin'] = 1;
                            } else {
                                $response['data'][$key1]['sendouts']['data'][$key2]['can_edit_by_admin'] = 0;
                            }
                        } else {                     
                             $response['data'][$key1]['sendouts']['data'][$key2]['can_edit_by_admin'] = 1;   // If there is no custom sendout in people caddie server, allow to edit
                        }
                        
                        if(isset($result[$sendout2['id']])){
                            $response['data'][$key1]['sendouts']['data'][$key2]['candidate']['pcData']=$result[$sendout2['id']];
                            if((int) $result[$sendout2['id']]['selected_for_client']>2){
                                unset($response['data'][$key1]['sendouts']['data'][$key2]);
                            }
                        } else {
                            unset($response['data'][$key1]['sendouts']['data'][$key2]);
                            //$response['data'][$key1]['sendouts']['data'][$key2]['candidate']['pcData']="";
                        }
                    }
                    $response['data'][$key1]['sendouts']['data']=array_values($response['data'][$key1]['sendouts']['data']);
                }

                if (isset($response['data'][$i])) {
                    $response['data'][$i]['dateEndFormatted'] = date("M d,Y", $response['data'][$i]['dateEnd']);
                    $response['data'][$i]['dateTodayTimeFormatted'] = date("M d,Y", time());
                    $response['data'][$i]['StartDateFormatted'] = date("M d,Y", $response['data'][$i]['startDate']);
                    if (strtotime($response['data'][$i]['dateEndFormatted']) < strtotime($response['data'][$i]['dateTodayTimeFormatted'])) {
                        //if ((strtotime($response['data'][$i]['StartDateFormatted']) > strtotime($response['data'][$i]['dateTodayTimeFormatted']) && $role==CANDIDATE_ROLE) || (strtotime($response['data'][$i]['dateEndFormatted']) < strtotime($response['data'][$i]['dateTodayTimeFormatted']))) {
                        unset($response['data'][$i]); // remove expired jobs
                        $response['total'] = $response['total'] - 1;
                        $response['count'] = $response['count'] - 1;
                    }
                }


                $i++;
                continue;
            }
        }
        $response['data'] = array_values($response['data']); // Reindex
        echo json_encode($response);
    }

    /*     * ************************************************************************
     * Function      : getSendouts
     * Description   : Get all sendouts using joborder id
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Request Input : joborder_ids => joborder id in array format
     * Created Date  : 12-12-2016
     * Updated Date  : 12-12-2016
     * ********************************************************************** */

    public function getSendouts($joborder_ids = null) {
        // pr($joborder_ids);
        //$this->BullhornConnection->BHConnect();
        $joborder_ids = implode(',', $joborder_ids);
        $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=jobOrder.id+IN(' . $joborder_ids . ')&fields=id,jobOrder,candidate(id,firstName,lastName,email)&count=200&BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $result = [];
        if (isset($response['data']) && !empty($response['data'])) {
            foreach ($response['data'] as $joborder) {
                $result[$joborder['jobOrder']['id']][] = $joborder;
            }
        }
        return $result;
    }

    public function getCustomCandidateSendoutInfo($sendout_ids = []) {
        if (empty($sendout_ids)) {
            return [];
        }
        $sendoutTable = TableRegistry::get('Sendout');
        $getCSendout = $sendoutTable->find('all')->select(['sendout_id', 'selected_for_client', 'joborder_id', 'candidate_id', 'candidate_match', 'desired_hourly_rate_from', 'desired_hourly_rate_to'])->where(['sendout_id IN' => $sendout_ids])->toArray();
        $reformatArray = [];
        if (!empty($getCSendout)) {
            foreach ($getCSendout as $get) {
                $reformatArray[$get['sendout_id']] = $get;
            }

            return $reformatArray;
        } else {
            return [];
        }
    }
    
    /* *************************************************************************
     * Function      : getCustomCandidateSendout
     * Description   : Get all sendouts using sendout id
     * Created by    : Balasuresh A
     * Updated by    : 
     * Request Input : sendout_ids => sendout_id in array format
     * Created Date  : 12-06-2016
     * Updated Date  : 
     * ************************************************************************/
    public function getCustomCandidateSendout($sendout_ids = []) {
        if (empty($sendout_ids)) {
            return [];
        }
        $sendoutTable = TableRegistry::get('Sendout');
        $getCSendout = $sendoutTable->find('all')->select(['sendout_id', 'selected_for_client', 'joborder_id', 'candidate_id', 'candidate_match', 'desired_hourly_rate_from', 'desired_hourly_rate_to'])->where(['sendout_id IN' => $sendout_ids,'candidate_match >' => '0%'])->toArray();
        $reformatArray = [];
        if (!empty($getCSendout)) {
            foreach ($getCSendout as $get) {
                $reformatArray[$get['sendout_id']] = $get;
            }
            return $reformatArray;
        } else {
            return [];
        }
    }

}

?>

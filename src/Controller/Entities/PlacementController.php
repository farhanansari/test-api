<?php

/* * ************************************************************************************
 * Class name      : PlacementController
 * Description     : Placement CRUD process
 * Created Date    : 06-09-2016
 * Created By      : Akilan
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

class PlacementController extends AppController {
    /*     * * * * * * * * *  * * * *
     * Action Name   : put
     * Description   : To create the placement status(Approved, Completed, Terminated,etc) of the candidate for the submitted job.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 06-09-2016
     * Updated Date  : 06-09-2016
     * URL           : /peoplecaddie-api/entities/placement/?
     * Request input : candidate[id], jobOrder[id], employmentType,sendout id
     * Request method: PUT
     * candidate_id
      joborder_id
      employmentType
      sendout_id
      status
     * Responses:
      1. success:
      2.fail:
     */

    public function add($params = null) {
        $this->BullhornConnection->BHConnect();
        $this->autoRender = false;
        $response = array();
        $params = $this->request->data;
        $sendoutTable = TableRegistry::get('Sendout');
        $notifyTable = TableRegistry::get('Notifications');
        $sendout_id = "";
        $candidate_id = "";
        if (isset($params['sendout_id'])) {
            $sendout_id = $params['sendout_id'];
            unset($params['sendout_id']);
        }
        $sendout_data = $sendoutTable->get_candidate_sendout($params['candidate_id'], $sendout_id);
        if (isset($params['status']) && isset($params['candidate_id']) && isset($params['joborder_id']) && !empty($sendout_data)) {
            $update_fields['job_submission_status'] = $params['status'];
            
            switch ($params['status']) {
                case $params['status'] == APPROVE_STATUS :
                    $params_data = array(
                        "candidate" => array('id' => $params['candidate_id']),
                        "jobOrder" => array("id" => $params['joborder_id']),
                        'employmentType' => $params['employmentType']
                    );
                    $update_fields['selected_for_client'] = 3;
                    $update_fields['application_progress'] = ASSIGNMENT_CONFIRMED;
                    $response = $this->add_placement_entity($params_data);
                    $response['result'] = 1;
                    $response['message'] = "Job was confirmed successfully";
                    if (isset($response['changedEntityId'])) {
                        $params1 = $this->notification_data($sendout_data, $params,$response['changedEntityId']);                       
                        $params1['type'] = 'final_assignment_confirmation'; //for triigering confirmation notification
                        $notifyTable->notification_data($params1);
                        $params1['type'] = 'performance_rating_request';
                        $params1['dateEnd'] = $this->getdateEnd($params['joborder_id']);
                        $notifyTable->notification_data($params1);  //for triggering performance request
                        $update_fields['placement_id'] = $response['changedEntityId'];
                        /*  To update the job order status */                    
                        if(isset($response['data']['jobOrder']['id']) && !empty($params['joborder_id'])) {
                            if($response['data']['jobOrder']['id'] == $params['joborder_id']) {
                                $job_params = array(
                                    'id' => $params['joborder_id'],
                                    'status' => 'Placed'
                                );
                                $data = $this->joborder_status_update($job_params);
                            }
                        }
                        /* To send notifications for the rejected contractors */
                        if(isset($params['joborder_id']) && isset($params['candidate_id'])) {
                            $this->unsuccessful_notify($params['joborder_id'],$params['candidate_id']);
                        }
                    } break;
                case $params['status'] == PLACEMENT_REJECT_STATUS :                   
                    $response['result'] = 2;
                    $update_fields['selected_for_client'] = 4;
                    $response['message'] = "Job was rejected as per your decision";
                    $update_fields['placement_id'] = NULL;
                    $update_fields['application_progress'] = OFFER_REJECTED;
                    break;
            }
            $this->stop_notification_alert_data($sendout_data); //for stop further notification alert
            $sendoutTable->sendout_update_fields($update_fields, $sendout_id);
        }
        echo json_encode($response);
    }

    function stop_notification_alert_data($sendout_data) {
        $notifyTable = TableRegistry::get('Notifications');
        $where_condition = array(
            'sendout_id' => $sendout_data[0]['sendout_id'],
            'job_submission_id' => $sendout_data[0]['job_submission_id'],
            'placement_id IS' => NULL
        );
        $notifyTable->stop_further_sendout_alert($where_condition);
    }
    
    /***************************************************************************
     * Function Name : unsuccessful_notify
     * Description   : To send the notifications for rejected candidates
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 18-05-2017
     * Updated Date  : 
     **************************************************************************/
    public function unsuccessful_notify($job_order_id,$candidate_id) {
        $sendoutTable = TableRegistry::get('Sendout');
        $notifyTable = TableRegistry::get('Notifications');
        if(!empty($job_order_id) && !empty($candidate_id)) {
            $rejectedUsers = $sendoutTable->find()->select(['candidate_id','sendout_id'])->where(['joborder_id' => $job_order_id, 'candidate_id !=' => $candidate_id])->hydrate(false);
            if(!empty($rejectedUsers)) {
                $rejectedUsers = $rejectedUsers->toArray();
                $candidate_ids = array_column($rejectedUsers, 'candidate_id');
                if(!empty($candidate_ids)) {
                    $sendoutTable->query()->update()->set(['application_progress' => APPLICATION_UNSUCCESSFUL])
                                ->where(['candidate_id IN ' => $candidate_ids])->execute();
                    $notifyTable->unsuccessful_applicants($rejectedUsers);
                }
            }
        }
        return ;
    }
    /***************************************************************************
     * Function Name : joborder_status_update
     * Description   : To update the job order status
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 04-01-2017
     * Updated Date  : 
     **************************************************************************/
    public function joborder_status_update($job_params) {
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $job_params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode($job_params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        return $response;
    }
    /*     * ********************************************************************
     * Function Name : final_confirmation_data
     * Description   : To arrange final confirmation notification data
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 23-11-2016
     * Updated Date  : 23-11-2016
     * Request input : Sendout query data
     * ****************************************************************** */

    protected function notification_data($sendout_data, $params, $placement_id) {
        $params1 = array(
            'type' => 'final_assignment_confirmation',
            'job_submission_id' => $sendout_data[0]['job_submission_id'],
            'candidate_id' => $sendout_data[0]['candidate_id'],
            'company_id' => $sendout_data[0]['hiring_company'],
            'placement_coordinator_id' => $sendout_data[0]['placement_coordinator_id'],
            'sendout_id' => $sendout_data[0]['sendout_id'],
            'placement_id' => $placement_id,
            'interviewer_id' => $sendout_data[0]['interviewer_id']
        );
        return $params1;
    }

    /*     * * * * * * * * *  * * * *
     * Function Name : put
     * Description   : To create the placement status(Approved, Completed, Terminated,etc) of the candidate for the submitted job.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 17-11-2016
     * Updated Date  : 17-11-2016     *
     * Request input : candidate[id], jobOrder[id], employmentType, daysGuaranteed, daysProRated, hoursPerDay, salary, salaryUnit
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function add_placement_entity($params) {
        $url = $_SESSION['BH']['restURL'] . '/entity/Placement?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $params['dateBegin'] = time();
        $post_params = json_encode($params);
        $req_method = 'PUT';
        return $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : get
     * Description   : To retrieve the placement status of the candidate for the particular job.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 06-09-2016
     * Updated Date  : 06-09-2016
     * URL           : /peoplecaddie-api/entities/placement/?id=7
     * Request input : id => ID of the placement entity.
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function get($params) {
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Placement/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->curl_fun->curlFunction($url, $post_params, $req_method);
        print_r(json_encode($response));
        return $response;
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : post
     * Description   : To update the placement status of the candidate for the particular job.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 06-09-2016
     * Updated Date  : 06-09-2016
     * URL           : /peoplecaddie-api/entities/placement/?id=7
     * Request input : id => ID of the placement entity.
      Params to be updated such as daysGuaranteed, daysProRated, hoursPerDay, salary, salaryUnit
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function post($params) {
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Placement/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->curl_fun->curlFunction($url, $post_params, $req_method);
        print_r(json_encode($response));
        return $response;
    }

    public function getdateEnd($joborder_id) {        
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $joborder_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=dateEnd';
        $post_params = json_encode(array($joborder_id));
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        return $response['data']['dateEnd'];
    }
    
    /*
     * *************************************************************************************
     * Function name   : delete
     * Description     : To delete the placement contractor info, while click the 'Failed to Select'
     * Created Date    : 17-04-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */
    public function delete($params = null) {
        $this->BullhornConnection->BHConnect();
        $this->autoRender = false;
        $jobParams = $placementParams = [];
        $params = $this->request->data;
        $sendoutTable = TableRegistry::get('Sendout');
        if(!empty($params) && isset($params['id'])) {
            
            if (isset($params['startDate']) && !empty($params['startDate'])) {
                $params['startDate'] = strtotime($params['startDate']);
                if (isset($params['durationWeeks']) && !empty($params['durationWeeks'])) {
                    $durationArr = [1 => "1 week", 2 => "2 weeks", 3 => "3 weeks", 4 => "1 month", 5 => "2 months", 6 => "3 months", 7 => "4 months", 8 => "5 months", 9 => "6 months", 10 => "7 months", 11 => "8 months", 12 => "9 months", 13 => "10 months", 14 => "11 months", 15 => "1 year", 16 => "2 years", 17 => "3 years"];
                    $params['dateEnd'] = strtotime('+' . $durationArr[$params['durationWeeks']], $params['startDate']);
                }
            }

            $placementParams = ['id' => $params['id']];
            $jobParams = $params;
            
            $curl_data[0]['url'] = $_SESSION['BH']['restURL'] . '/entity/Placement/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $curl_data[0]['post_data'] = json_encode($placementParams);
            $curl_data[0]['req_method'] = 'DELETE';
            $curl_data[1]['url'] = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['joborder_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $curl_data[1]['post_data'] = json_encode($jobParams);
            $curl_data[1]['req_method'] = 'POST';
            $multiResponse = $this->BullhornCurl->multiRequest($curl_data);
            if(!empty($multiResponse)) {
                $sendoutTable->query()->update()->set(['application_progress' => FAILED_TO_SHOW,'failed_to_show' => 1])
                    ->where(['candidate_id' => $params['contractor_id'],'joborder_id' => $params['joborder_id']])->execute();
                echo json_encode(array('status' => 1, 'message' => 'Updated Successfully'));
            }
        }
    }

}

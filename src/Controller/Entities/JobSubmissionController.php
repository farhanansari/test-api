<?php

/* * ************************************************************************************
 * Class name      : JobSubmissionController
 * Description     : JobSubmission CRUD process
 * Created Date    : 24-08-2015 
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

class JobsubmissionController extends AppController {
    /*     * * * * * * * * *  * * * *    
     * Action Name   : add
     * Description   : To add the job submission details such as jobOrder,etc.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/entities/Jobsubmission   
     * Request method: PUT
     * Responses:
      1.Success
      2.fail:
     */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $sendout_id = "";
        $appointment_id = "";
        if (isset($params['sendout_id'])) {
            $sendout_id = $params['sendout_id'];
            unset($params['sendout_id']);
            $appointment_id = $params['appointment_id'];
            unset($params['appointment_id']);
        }
        $notifications = TableRegistry::get('Notifications');
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobSubmission?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['changedEntityId'])) {
            $where_condition = array("sendout_id" => $sendout_id, "job_submission_id IS" => NULL, "placement_id IS" => NULL);
            $notifications->stop_further_sendout_alert($where_condition);
            $sendout_data = TableRegistry::get('Sendout')->get_candidate_sendout($params['candidate']['id'], $sendout_id);
            if (!empty($sendout_data)) {
                $where_condition = array("appointment_id" => $sendout_data[0]['appointment_id']);
                $notifications->stop_further_sendout_alert($where_condition);
            }
            $params1 = array('type' => 'assignment_confirm_process', 'sendout_id' => $sendout_id, 'job_submission_id' => $response['changedEntityId'],
                'candidate_id' => $params['candidate']['id'], 'action_user_id' => $params['action_user_id'], 'company_id' => $params['company_id']);
            $notifications->notification_data($params1);
            $update_fields = array('job_submission_id' => $response['changedEntityId'],
                'placement_coordinator_id' => $params['action_user_id'], 'job_submission_status' => ACCEPT_STATUS,'selected_for_client'=>ACCEPT_STATUS,'application_progress' => OFFER_RECEIVED);
            TableRegistry::get('Sendout')->sendout_update_fields($update_fields, $sendout_id);
        }
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : view
     * Description   : To retrieve the jobsubmission status for the particular job.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/entities/Jobsubmission/?id=86
     * Request input : id => ID of the jobsubmission.
     * Request method: GET
     * Responses:
      1.Success
      2.fail:
     */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobSubmission/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : delete
     * Description   : To update the job submission details such as jobOrder,etc,.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/entities/Jobsubmission/?id=86
     * Request input : id => ID of the jobsubmission. Params to be updated such as jobOrder[id].
     * Request method: POST
     * Responses:
      1.Success
      2.fail:
     */

    public function update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobSubmission/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : delete
     * Description   : To delete the job submission entity.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/api/Jobsubmission/?id=87
     * Request input : id => ID of the jobsubmission. isDeleted = true
     * Request method: POST
     * Responses:
      1.Success
      2.fail:
     */

    public function delete($params) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobSubmission/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['isDeleted'] = 'true';
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Function Name   : related_appointment
     * Description   : To related job submission and appointment
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 16-11-2016
     * Updated Date  : 16-11-2016     
     * Request input : appointment id, job submission id
     * Request method: POST
     * Responses:
      1.Success
      2.fail:
     */

    public function related_appointment($job_submission_id, $appointment_id) {
        $url = $_SESSION['BH']['restURL'] . '/entity/JobSubmission/' . $job_submission_id . '/appointments/' . $appointment_id . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params1 = json_encode(array());
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $params1, $req_method);
        echo json_encode($response);
    }

    /*     * *************************************************************************************
     * Function name   : sendout_update_fields
     * Description     : Function for updating sendout fields
     * Created Date    : 15-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function sendout_update_fields($tablename, $field, $value, $sendout_id) {
        $tablename = TableRegistry::get("Sendout");
        $tablename->query()->update()->set(['job_submission_id' => $value])
                ->where(['sendout_id' => $sendout_id])
                ->execute();
    }

}

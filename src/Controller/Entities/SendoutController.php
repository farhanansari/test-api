<?php

/* * ************************************************************************************
 * Class name      : SendoutController
 * Description     : Sendout CRUD process
 * Created Date    : 23-08-2016 
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

class SendoutController extends AppController {
    /*     * * * * * * * * *  * * * *    
     * Action Name   : add
     * Description   : To create the sendout once the candidate information is sent to the clientCorporation to be evaluated for particular job.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/sendout/?
     * Request input : candidate[id], user[id], isRead=true
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $invisibleTable = TableRegistry::get('InvisibleJob');
        $cSendout = [
            'candidate_suitability' => $params['contractorSuitability'],
            'candidate_match' => $params['contractorMatch'],
            'attachments' => implode(',', $params['attachments']),
            'desired_hourly_rate_from' => $params['desiredHourlyRateFrom'],
            'desired_hourly_rate_to' => $params['desiredHourlyRateTo'],
            'is_interview_finished' => $params['accountRepInterviewFinished'],
            'application_progress' => APPLICATION_PENDING
        ];

        unset($params['contractorSuitability']);
        unset($params['contractorMatch']);
        unset($params['attachments']);
        unset($params['desiredHourlyRateFrom']);
        unset($params['desiredHourlyRateTo']);
        unset($params['accountRepInterviewFinished']);

        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Sendout?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

        $cSendout['sendout_id'] = $response['changedEntityId'];
        $cSendout['hiring_company'] = $params['clientCorporation']['id'];
        $cSendout['candidate_id'] = $params['candidate']['id'];
        $cSendout['joborder_id'] = $params['jobOrder']['id'];
        
        $sendoutTable = TableRegistry::get('Sendout');
        $sendout = $sendoutTable->newEntity($cSendout);
        if ($sendoutTable->save($sendout)) {
            $response['customSendouttoPCDB'] = "Success!";
            /**
             * hide job in candidate dashboard because he is applied for this job
             */
            $invisibleTable->make_invisible(array('candidate_id'=>$cSendout['candidate_id'],'joborder_id'=>$cSendout['joborder_id']));
        }
       $this->contractor_slate_review_notify($cSendout);
        
        echo json_encode($response);
    }
    
    /*
     * Description: notification for hiring manager and sales rep about contractor slate
     * 
     */
    
    public function contractor_slate_review_notify($cSendout){
        $this->BullhornConnection->BHConnect();
        $fields = 'id,clientCorporation,clientContact';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $cSendout['joborder_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if(isset($response['data'])){
            $clientContactBHId = $response['data']['clientContact']['id']; 
            $getSalesRepId = TableRegistry::get('Users')->get_sales_rep_id($response['data']['clientCorporation']['id']);
                //$getSalesRep = TableRegistry::get('Users')->get_email($getSalesRepId, 'bullhorn_entity_id');
            $notify = [
                'sender' => $cSendout['candidate_id'],
                'receiver_HM' => $clientContactBHId,
                'sendout_id' => $cSendout['sendout_id'],
                'receiver_PC_SR' => $getSalesRepId,
            ];
            $notify2 = [
                'sender_HM' => $clientContactBHId,
                'sendout_id' => $cSendout['sendout_id'],
                'receiver_contractor' => $cSendout['candidate_id'],
            ];
            TableRegistry::get('Notifications')->review_contractors($notify);
            TableRegistry::get('Notifications')->application_received($notify2);
            //return 1;
        }
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : view
     * Description   : To retrieve the sendout sent for the particular job.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/sendout/?id=14
     * Request input : id => ID of the sendout.
     * Request method: GET
     * Responses:
      1.success:
      2.fail:
     */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Sendout/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        /* // enable if need to send rating
          $this->loadModel('Sendout');
          $this->Sendout->addAssociations(
          array('hasOne' => array(
          'Performance' => array(
          'className' => 'Performance',
          'foreignKey' => false,
          'conditions' => [
          'Performance.job_order_id=Sendout.joborder_id'
          ],
          'order' => ['Performance.id' => 'DESC'],
          'limit' => 1
          )
          )
          )
          );
          $performance = $this->Sendout->find('all', ['conditions' => ['Sendout.sendout_id' => $params['id']], 'contain' => ['Performance']])->first();
          $response['data']['rating'] = (isset($performance->performance->grade)) ? $performance->performance->grade : '';
         */
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : update
     * Description   : To update the sendout which had been sent to the clientCorporation.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/sendout/?id=14
     * Request input : id => ID of the sendout. Params to be updated such as isRead=false
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $notifyTable = TableRegistry::get('Notifications');
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        if (!isset($params['id'])) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "Please make sure you are passing sendout id"
                    ]
            );
            exit;
        }
        $sendoutTable = TableRegistry::get('Sendout');
        $getCSendout = $sendoutTable->find()->select(['id','sendout_id','candidate_id','hiring_company'])->where(['sendout_id' => $params['id']])->toArray();
        if (!empty($getCSendout)) {
            $sId = $getCSendout[0]['id'];
            $sendoutUpdate = $sendoutTable->get($sId);
            $sendoutUpdate->id = $sId;
            if (isset($params['contractorSuitability']) && !empty($params['contractorSuitability'])) {
                $sendoutUpdate->candidate_suitability = $params['contractorSuitability'];
                unset($params['contractorSuitability']);
            }
            if (isset($params['contractorMatch']) && !empty($params['contractorMatch'])) {
                $sendoutUpdate->candidate_match = $params['contractorMatch'];
//                $params['type'] = "candidate_match_update";
//                $notificationsTable->notification_data($params)
                /**
                 * For triggering notification based on updating candidate match in slate view
                 */
                if(!empty($params['id']) && !empty($getCSendout[0]['hiring_company']) && !empty($getCSendout[0]['candidate_id']) && $params['role'] === SALESREP_ROLE) {
                    $slateNotify['sendout_id'] = $params['id'];
                    $slateNotify['sales_rep_id'] = $params['bullhorn_id'];
                    $slateNotify['candidate_id'] = $getCSendout[0]['candidate_id'];
                    $slateNotify['company_id'] = $getCSendout[0]['hiring_company'];
                    $this->hm_slate_update_nofity($slateNotify);                    
                }
                unset($params['contractorMatch']);
            }
            if (isset($params['attachments']) && !empty($params['attachments'])) {
                $sendoutUpdate->attachments = implode(',', $params['attachments']);
                unset($params['attachments']);
            }
            if (isset($params['desiredHourlyRateFrom']) && !empty($params['desiredHourlyRateFrom'])) {
                $sendoutUpdate->desired_hourly_rate_from = $params['desiredHourlyRateFrom'];
                unset($params['desiredHourlyRateFrom']);
            }
            if (isset($params['desiredHourlyRateTo']) && !empty($params['desiredHourlyRateTo'])) {
                $sendoutUpdate->desired_hourly_rate_to = $params['desiredHourlyRateTo'];
                unset($params['desiredHourlyRateTo']);
            }
            if (isset($params['accountRepInterviewFinished'])) {
                $sendoutUpdate->is_interview_finished = $params['accountRepInterviewFinished'];
                unset($params['accountRepInterviewFinished']);
            }
            if (isset($params['receivedDrugScreeningTest'])) {
                $sendoutUpdate->received_drug_screening_test = $params['receivedDrugScreeningTest'];
                unset($params['receivedDrugScreeningTest']);
            }
            if (isset($params['drugScreeningTestPassed'])) {
                $sendoutUpdate->drug_screening_test_passed = $params['drugScreeningTestPassed'];
                unset($params['drugScreeningTestPassed']);
            }
            if (isset($params['receivedBackgroundCheck'])) {
                $sendoutUpdate->received_background_check = $params['receivedBackgroundCheck'];
                unset($params['receivedBackgroundCheck']);
            }
            if (isset($params['selectedForClient'])) {
                $sendoutUpdate->selected_for_client = $params['selectedForClient'];
                $params['selectedForClient']==REJECT_STATUS ? $this->reject_notification_data($getCSendout) : "";
                if($params['selectedForClient'] == REJECT_STATUS) {
                    $sendoutUpdate->application_progress = APPLICATION_UNSUCCESSFUL;
                }
                unset($params['selectedForClient']);
            }
            if (isset($params['candidate_brief_desc'])) {
                $sendoutUpdate->candidate_brief_desc = $params['candidate_brief_desc'];
                unset($params['candidate_brief_desc']);
            }
            if (isset($params['candidate']['id']) && !empty($params['candidate']['id'])) {
                $sendoutUpdate->candidate_id = $params['candidate']['id'];
            }
            if (isset($params['jobOrder']['id']) && !empty($params['jobOrder']['id'])) {
                $sendoutUpdate->joborder_id = $params['jobOrder']['id'];
            }
            if (isset($params['clientCorporation']['id']) && !empty($params['clientCorporation']['id'])) {
                $sendoutUpdate->hiring_company = $params['clientCorporation']['id'];
            }  
        }
        $url = $_SESSION['BH']['restURL'] . '/entity/Sendout/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data'])) {
            if (!empty($getCSendout) && $sendoutTable->save($sendoutUpdate)) {
                $response['data']['pcData'] = [
                    'status' => 1,
                    'message' => 'Successfull Updated'
                ];
            } else {
                $response['data']['pcData'] = [
                    'status' => 0,
                    'message' => $sendoutUpdate->errors()
                ];
            }
        }
        echo json_encode($response);
    }
    
    
    /*
     * *************************************************************************************
     * Function name   : hm_slate_update_nofity
     * Description     : To notify the hiring manager when sales rep updates the slate page
     * Created Date    : 24-11-2016
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    
    public function hm_slate_update_nofity($slateNotify = null) {
        
        $this->autoRender = false;
        //$slateNotify['company_id'] = 6;
        $usersTable = TableRegistry::get('Users');
        $getUsers = $usersTable->find('all')->select(['bullhorn_entity_id'])->where(['company_id' => $slateNotify['company_id'],'role' => HIRINGMANAGER_ROLE ])->first()->toArray();
        
        if(!empty($getUsers)) {
            $data = [
            'sender' => $slateNotify['candidate_id'],
            'receiver_PC_SR' => $slateNotify['sales_rep_id'],
            'receiver_HM' => $getUsers['bullhorn_entity_id'],
            'sendout_id' => $slateNotify['sendout_id']
            ];
          $slateChange = ['receiver_PC_SR' => $slateNotify['sales_rep_id'],'receiver_HM' => $getUsers['bullhorn_entity_id']];
          TableRegistry::get('Notifications')->slate_status_update($slateChange);  
          TableRegistry::get('Notifications')->slate_update_notify($data);  
            
        }
    }
    
     /*
     * *************************************************************************************
     * Function name   : reject_notification_data
     * Description     : To arrange notification data when application was rejected
     * Created Date    : 07-12-2016
     * Created By      : Akilan
     * *************************************************************************************
     */
    function reject_notification_data($getCSendout){
        $notifyTable = TableRegistry::get('Notifications');
        $data= array(
            'type'=>'contractor_sendout_reject',            
            'receipients'=>$getCSendout[0]['candidate_id'],
            'sendout_id'=>$getCSendout[0]['sendout_id']
        );
        $notifyTable->notification_data($data);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : delete
     * Description   : To update the sendout which had been sent to the clientCorporation.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016 
     * URL           : /peoplecaddie-api/entities/sendout/?id=14
     * Request input : id => ID of the sendout. Params to be updated such as isRead=false
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function delete($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Sendout/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'DELETE';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $sendoutTable = TableRegistry::get('Sendout');
        $getCSendout = $sendoutTable->find()->select(['id'])->where(['sendout_id' => $params['id']])->toArray();
        if (!empty($getCSendout) && isset($response['data'])) {
            $sId = $getCSendout[0]['id'];
            $sendoutTable->delete($sendoutTable->get($sId));
        }
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : placement_change_request
     * Description   : To create the sendout once the candidate information is sent to the clientCorporation to be evaluated for placement change request and update the details in performance table.
     * Created by    : Sathyakrishnan
     * Updated by    : Sathyakrishnan
     * Created Date  : 12-11-2016
     * Updated Date  : 12-11-2016
     * URL           : /peoplecaddie-api/entities/sendout/?
     * Request input : candidate[id], user[id], isRead=true
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function placement_change_request($params = null) {
        $this->autoRender = false;
        $cSendout = $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/PlacementChangeRequest?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

        $cSendout['pcr_status'] = $response['status'];

        $sendoutTable = TableRegistry::get('Performance');
        $getprimaryid = $sendoutTable->find('list')->where(['Performance.placement_id' => $cSendout['placement_id']])->first();
        $cSendout['id'] = $getprimaryid;
        $sendout = $sendoutTable->newEntity($cSendout);
        if ($sendoutTable->save($sendout)) {
            $response['customSendouttoPCDB'] = "Success!";
        }

        echo json_encode($response);
    }
    
    /* ************************************************************************************
     * Function name   : remove_sendout
     * Description     : to remove the notifications, from mobile applications
     * Created Date    : 06-05-2017
     * Modified Date   : 
     * Created By      : Balasuresh A
     * Modified By     : 
     * ************************************************************************************/
    public function remove_sendout($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        if(isset($params['sendout_id']) && !empty($params['sendout_id'])) {
            $sendoutTable = TableRegistry::get('Sendout');
            $sendoutTable->query()->update()->set(['history_status' => 0])->
                    where(['sendout_id' => $params['sendout_id']])->execute();
            echo json_encode(['status' => 1, 'message' => 'Updated Successfully']);
        } else {
            echo json_encode(['status' => 0, 'message' => 'Please send the Sendout ID']);
        }
    }
}

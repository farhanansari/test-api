<?php

/* * ************************************************************************************
 * Class name      : NotificationsController
 * Description     : Keep/store notifications detail
 * Created Date    : 25-10-2016
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
use Cake\Routing\Router;

class NotificationsController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('Notification');
        $this->loadComponent('RequestHandler');
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    public function test() {
        $this->autoRender = false;
        $this->Auth->allow();
        $notifyTable = TableRegistry::get('Notifications');
        echo $current_time = $notifyTable->get_current_time();
        echo "<br>two minutes after" . $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+2 minutes', $current_time)));
        echo "<br>";
        $users = TableRegistry::get('Users');
        $user_data = array(1854, 1857, 1859, 1861, 1863);
        $usersql = $users->get_hiringmanager_list($user_data);

//   echo strtotime(date('d-m-Y H:i:s'));
    }

    /*     * **************************************************************************************
     * Function name   : interviewAppointmentReminder
     * Description     : send notification for an interview
     * Created Date    : 31-10-2015
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function index() {
        $this->autoRender = false;
        $this->Auth->allow();
        $notifyTable = TableRegistry::get('Notifications');
        $firebase = [];
        $allNotifcations = [];
        $getAllInterviewTimes = $this->get_currenttime_notifications();
        if (!empty($getAllInterviewTimes)) {
            list($firebase, $allNotifcations, $email_text, $sendSMS) = $this->message_format($getAllInterviewTimes);

            if (!empty($response)) {
                $this->notification_update($response);
            }
            $data = $this->getJobOrder($email_text);

            $this->Notification->email($data);
            $response = $this->Notification->multiRequest($allNotifcations);
            $this->Notification->sendsms($sendSMS, $data);
            echo json_encode($response);
        }
    }

    /*     * ***************************************************************************************
     * Function name   : get_currenttime_notifications
     * Description     : send notification for an interview
     * Created Date    : 02-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function get_currenttime_notifications() {
        $notifyTable = TableRegistry::get('Notifications');
        $current_time = $notifyTable->get_current_time();    
        return $getAllInterviewTimes = $notifyTable->find('all')->select(['id', 'sendout_id', 'sender', 'typeID',
                    'email_verify_id', 'trigger_timestamp', 'receipients', 'navigation', 'appointment_id',
                    'fixed_timestamp', 'user.id', 'user.email', 'user.device_id', 'user.bullhorn_entity_id', 'user.firstName', 'user.lastName', 'user.phone', 'user.role'])->join([
                    'user' => [
                        'table' => 'user',
                        'type' => 'INNER',
                        'conditions' => 'receipients = user.bullhorn_entity_id'
                    ]
                ])->where(['trigger_timestamp ' => $current_time, 'Notifications.status' => 1])->toArray();
//  , 'Notifications.status' => 1
    }

    /*     * **************************************************************************************
     * Function name   : message_format
     * Description     : send notification for an interview
     * Created Date    : 03-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function message_format($getAllInterviewTimes) {
        $firebase = array();
        $allNotifcations = array();
        $email_text = array();
        $sendSMS = array();
        $sendOutIDs = "";
        $fullName = array();
        $sendoutTable = array();
        $notifyTypeTable = TableRegistry::get('NotificationType');
        foreach ($getAllInterviewTimes as $sendto) {
            $device_id = $sendto['user']['device_id'];
            $appointment_date = "";
            $appointment_time = "";
            if (!is_null($sendto['fixed_timestamp']) && !empty($sendto['fixed_timestamp'])) {
                $appointment_date = date('d/m/Y', $sendto['fixed_timestamp']);
                $appointment_time = date('g:i A', $sendto['fixed_timestamp']);
            }
            $sendOutID = $sendto['sendout_id'];
            $senderID = $sendto['sender'];
//Title of the Notification.
            $name = $this->getFirstName($sendto['user']['firstName'], $sendto['user']['lastName']);
            $title = "Hello" . $name;
//Body of the Notification.
            $notification_details = $notifyTypeTable->get_notification_type();
            if (isset($notification_details[$sendto['typeID']]['mobile_notification']) && !empty($notification_details[$sendto['typeID']]['mobile_notification'])) {
                $body = $notification_details[$sendto['typeID']]['mobile_notification'];
            } else {
                $body = "Please be available on " . $appointment_date . " at " . $appointment_time . " for your interview scheduled through PeopleCaddie";
            }
            if ($device_id != "" || !empty($device_id) && $sendto['user']['role'] == CANDIDATE_ROLE) {
                $firebase[] = $sendto['id'];
                $extra_data = $this->notification_extra_data($sendto);
                $extra_data['typeID'] = $sendto['typeID'];
                $notification = array('title' => $title, 'body' => $body, 'priority'=> 'high','sound' => 'default');
//This array contains, the token and the notification. The 'to' attribute stores the token.
                $fields = array('to' => $device_id,'notification' => $notification, 'data' => array('response' => $extra_data));

                $allNotifcations[$sendto['id']] = [
                    'post_data' => $fields
                ];
            } //else {
//Start: client admin registration if not email verified within 24 hrs, send mail to respective sales rep or super admin
//if (($sendto['email_verify_id'] != null && strtotime('+24 hours', $sendto['fixed_timestamp']) == $sendto['trigger_timestamp']) || $sendto['typeID'] == 3) {
            if ($sendto['typeID'] == 35 || $sendto['typeID'] == 3) {
                if ($sendto['typeID'] == 3) {
                    $sale_rep_or_super_admin = TableRegistry::get('Users')->get_email($sendto['receipients'], 'bullhorn_entity_id');
                } else {
                    $sale_rep_or_super_admin = TableRegistry::get('Users')->get_email($sendto['sender'], 'bullhorn_entity_id');
                }
                $tomail = $sale_rep_or_super_admin[0]['email'];
                $name = $this->getFirstName($sale_rep_or_super_admin[0]['firstName'], $sale_rep_or_super_admin[0]['lastName']);
                $title = "Hello" . $name;
            } else {
                $tomail = $sendto['user']['email'];
            }
//End
            /* to update the sendout application status */
            if(($sendto['typeID'] == PERFORMANCE_RATING_REQUEST) || ($sendto['typeID'] == PERFORMANCE_RATING_ADMIN_FOLLOW_UP)) {
                    $sendout_status = $this->sendout_status_update($sendto['typeID'],$sendto['sendout_id']);
            }
            if (isset($sendto['sendout_id']) && !empty($sendto['sendout_id'])) {
                $sendOutIDs = $sendto['sendout_id'];
            }
            if (isset($sendOutID) && !empty($sendOutID)) {
                $canPhoneNumber = $this->getPhoneno($sendOutID);
            }

            $hm_messageUpdateIDs = array(/* hiring managers welcome and follow ups remainder */
                HM_WELCOME, HM_WELCOME_REMINDER_1, HM_WELCOME_REMINDER_2
            );
            $performanFollowUP = array(
                PERFORMANCE_RATING_SR_FOLLOW_UP_1 => 0, PERFORMANCE_RATING_SR_FOLLOW_UP_2 => 1, PERFORMANCE_RATING_ADMIN_FOLLOW_UP => 2
            );
            $messageUpdateIDs = array(
                CONTRACTOR_EMAIL_VALIDATION_REMINDER_3, FINAL_ASSIGNEMENT_CONF_B, FINAL_ASSIGNEMENT_CONF_C,ALTER_INTERVIEW_SCHEDULE_SR,ALTER_INTERVIEW_SCHEDULE_HM,
                HM_FOLLOW_UP_1, HM_FOLLOW_UP_2, PERFORMANCE_RATING_REQUEST, INTERVIEW_FOLLOW_UP_1, INTERVIEW_FOLLOW_UP_2, INTERVIEW_CONF_NOTIFICATION_B, INTERVIEW_APP_REMINDER_1B, INTERVIEW_APP_REMINDER_2B,
                PERFORMANCE_RATING_REMINDER_1, PERFORMANCE_RATING_REMINDER_2, PERFORMANCE_RATING_SR_FOLLOW_UP_1, PERFORMANCE_RATING_SR_FOLLOW_UP_2, PERFORMANCE_RATING_ADMIN_FOLLOW_UP, ASSIGNMENT_PENDING_APPLICANT_CONF);

            if (in_array($notification_details[$sendto['typeID']]['id'], $messageUpdateIDs)) {
                $sendoutTable = TableRegistry::get('Sendout')->find('all')->select('candidate_id')->where(['sendout_id' => $sendOutID])->first();
                if (!empty($sendoutTable)) {
                    $sendoutTable = $sendoutTable->toArray();
                    if (isset($performanFollowUP[$notification_details[$sendto['typeID']]['id']])) {
                        $fullName = TableRegistry::get('Users')->full_name($sendto['sender']);
                        $convertedName = implode('', (array) $fullName['full_name']); // converting array to string
                    } else {
                        $fullName = TableRegistry::get('Users')->full_name($sendoutTable['candidate_id']);
                        $convertedName = implode('', (array) $fullName['full_name']); // converting array to string
                    }
                    $originalText = $notification_details[$sendto['typeID']]['message_text']; /* fetch the original message */
                    $findText = array("First Name", "Last Name"); /* find the string to replace */
                    $replaceText = array($convertedName); /* replace string value with full name */
                    $newPhraseMessage = str_replace($findText, $replaceText, $originalText); /* reformated string */
                } else {
                    $newPhraseMessage = $notification_details[$sendto['typeID']]['message_text']; /* fetch the original message */
                }
            } else if (in_array($notification_details[$sendto['typeID']]['id'], $hm_messageUpdateIDs)) { /* hiring managers remainders */

                $newPhraseMessage = $this->get_salesRepName($senderID, $notification_details[$sendto['typeID']]['message_text']);
            } else {
                $newPhraseMessage = $notification_details[$sendto['typeID']]['message_text']; /* fetch the original message */
            }

            $email_text[$sendto['id']] = array(
                'to' => $tomail,
                'subject' => $notification_details[$sendto['typeID']]['type'],
                'mail_subject' => $notification_details[$sendto['typeID']]['mail_subject'],
                'title' => $title, /* title of the message */
                'message' => $newPhraseMessage, /* Assign the message to send */
                'typeID' => $sendto['typeID'],
                'user_id' => $sendto['user']['id'], /* user id to get email verification link */
                'user_phone' => (!empty($canPhoneNumber)) ? $canPhoneNumber : "", /* user phone number for interview confirmation */
                'sendout_id' => $sendOutIDs,
                'appointment_id' => (!is_null($sendto['appointment_id'])) ? $sendto['appointment_id'] : "",
                'contractor_full_name' => $fullName, /* contrator full name */
                'bull_horn_id' => $sendoutTable); /* bullhorn id */
// }

            if ($sendto['user']['role'] == CANDIDATE_ROLE) {
//                $emailValid = array(CONTRACTOR_EMAIL_VALIDATION_LINK,CONTRACTOR_EMAIL_VALIDATION_REMINDER_1,CONTRACTOR_EMAIL_VALIDATION_REMINDER_2,CONTRACTOR_EMAIL_VALIDATION_REMINDER_3);
//                if(in_array($notification_details[$sendto['typeID']]['id'], $emailValid) && !empty($sendto['user']['id'])) {
//                   $validateURL = $this->getShortLink($sendto['user']['id']);
//                   $notification_details[$sendto['typeID']]['sms_text'] = $notification_details[$sendto['typeID']]['sms_text']."\n".$validateURL;
//                }
//                $notification_details[$sendto['typeID']]['sms_text'].="\n Regards, \n PeopleCaddie!";
                $sendSMS[$sendto['id']] = [
                    'typeID' => $sendto['typeID'],
                    'number' => $sendto['user']['phone'],
                    'message' => !empty($notification_details[$sendto['typeID']]['sms_text']) ? "Hi" . $name . " " . $notification_details[$sendto['typeID']]['sms_text'] : "Hi" . $name . " " . "Please check your status often in PeopleCaddie. Thanks!"
                ];
            }
        }
        pr(array($firebase, $allNotifcations, $email_text, $sendSMS));
        return array($firebase, $allNotifcations, $email_text, $sendSMS);
    }
    
    /* *************************************************************************************
     * Function name   : sendout_status_update
     * Description     : To update the performance rating application status
     * Created Date    : 24-02-2017
     * Created By      : Balasuresh
     * ************************************************************************************/
    public function sendout_status_update($type_id,$sendout_id) {
        $sendout_tbl = TableRegistry::get('Sendout');
        if(($type_id == PERFORMANCE_RATING_REQUEST) && (!empty($sendout_id)) && !is_null($sendout_tbl)) {
            $sendout_tbl->query()->update()->set(['application_progress' => ASSIGNMENT_UNDERWAY])
                                ->where(['sendout_id' => $sendout_id])->execute();
        } elseif(($type_id == PERFORMANCE_RATING_ADMIN_FOLLOW_UP ) && (!empty($sendout_id)) && !is_null($sendout_tbl)) {
            $sendout_tbl->query()->update()->set(['application_progress' => AWAITING_PERFORMANCE_RATING])
                                ->where(['sendout_id' => $sendout_id])->execute();
        } else {
            
        }
        return;
    }

    /*     * ************************************************************************************
     * Function name   : getShortLink
     * Description     : To generate the shorten link for email validation link
     * Created Date    : 10-01-2017
     * Created By      : Balasuresh
     * *********************************************************************************** */

    public function getShortLink($sender_id) {
        $token = $this->Notification->getToken($sender_id);
        if (!empty($token)) {
            $longurl = WEB_SERVER_ADDR_MARKETING . 'index.php?token=' . $token;
            $url = "http://api.bit.ly/shorten?version=2.0.1&longUrl=$longurl&login=balasureshfsp&apiKey=R_743b22dcfe094596bb63f53b9e50e541&format=json&history=1";
            $s = curl_init();
            curl_setopt($s, CURLOPT_URL, $url);
            curl_setopt($s, CURLOPT_HEADER, false);
            curl_setopt($s, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($s);
            curl_close($s);

            $obj = json_decode($result, true);
            return $validateURL = !empty($obj["results"]["$longurl"]["shortUrl"]) ? str_replace("http://bit.ly/", Router::url('/', true) . 'users/k2/', $obj["results"]["$longurl"]["shortUrl"]) : '';
        }
    }

    /*     * *************************************************************************************
     * Function name   : get_salesRepName
     * Description     : To fetch the sales representative full name
     * Created Date    : 15-12-2016
     * Created By      : Balasuresh
     * ************************************************************************************ */

    public function get_salesRepName($sender_id, $originalText) {
        $fullName = TableRegistry::get('Users')->full_name($sender_id);
        $convertedName = implode('', (array) $fullName['full_name']);
        $findText = array("[S1]", "[S2]");
        $replaceText = array($convertedName);
        $newPhraseMessage = str_replace($findText, $replaceText, $originalText);
        return $newPhraseMessage;
    }

    /*     * *************************************************************************************
     * Function name   : getPhoneno
     * Description     : To fetch the candidates phone number
     * Created Date    : 15-12-2016
     * Created By      : Balasuresh
     * ************************************************************************************ */

    public function getPhoneno($senderID) {
        if (!empty($senderID)) {
            $this->loadModel('Users');
            $sendoutTable = TableRegistry::get('Sendout')->find('all')->select('candidate_id')->where(['sendout_id' => $senderID])->first();
            $getPhone = $this->Users->find('all')->select(['phone'])->where(['bullhorn_entity_id' => $sendoutTable['candidate_id']])->first();
            if (!empty($getPhone)) {
                $getPhone = $getPhone->toArray();
                return $getPhone['phone'];
            }
        }
    }

    /*     * **************************************************************************************
     * Function name   : notification_update
     * Description     : For updating fire base sending statuss
     * Created Date    : 03-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function notification_update($response) {
        $i = 0;
        $notifyTable = TableRegistry::get('Notifications');
        foreach ($response as $data) {
            $data = json_decode($data);
            if ($data->success) { // failed
                $firebase = 0;
                $query = $notifyTable->query()->update()->set(['firebase_status' => $firebase])
                        ->where(['id' => $firebase[$i]])
                        ->execute();
            }

            $i++;
        }
    }

    /*     * **************************************************************************************
     * Function name   : notification_screen
     * Description     : Find screen based on notification detail
     * Created Date    : 04-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function notification_extra_data($send_to) {
        $extra_data = array();
        switch ($send_to) {
            case!is_null($send_to['sendout_id']) && !empty($send_to['sendout_id']) :
                $sendout_dat = TableRegistry::get('Sendout')->detail($send_to['sendout_id'], 'joborder_id');
                $job_order_id = isset($sendout_dat[0]['joborder_id']) ? $sendout_dat[0]['joborder_id'] : "";
                $extra_data['status'] = APPOINTMENT_SCREEN;
                $extra_data['sendout_id'] = $send_to['sendout_id'];
                $extra_data['joborder_id'] = $job_order_id;
                $extra_data['navigation'] = $send_to['navigation'];
                $extra_data['candidate_id'] = $send_to['receipients'];
                break;

            case!is_null($send_to['email_verify_id']) && !empty($send_to['email_verify_id']) :
                $extra_data['candidate_id'] = $send_to['receipients'];
                break;
        }
        return $extra_data;
    }

    /*
     * **************************************************************************************
     * Function name   : polling
     * Description     : Find screen based on notification detail
     * Created Date    : 09-11-2016
     * Created By      : Sivaraj V
     * Request Input   : bullhorn_id => user who logged in, read_polling_ids => in array format
     * **************************************************************************************
     */

public function polling() {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $params = $this->request->data;
//        $params['bullhorn_id'] = 1027;
            if (!empty($params['read_polling_ids']) && is_array($params['read_polling_ids'])) {
                $polling_status = 0;
                TableRegistry::get('Notifications')->query()->update()->set(['polling_status' => $polling_status])
                        ->where(['id IN' => $params['read_polling_ids']])
                        ->execute();
            }
            if (isset($params['bullhorn_id'])) {
                $resp = (isset($params['role']) && ($params['role'] == SUPER_ADMIN_ROLE)) ? $this->admin_polling($params['bullhorn_id'],$params) : $this->get_polling($params['bullhorn_id'], $params);
                $result['status'] = 0;
                $result['total'] = count($resp);
                if (count($resp)) {
                    $result['status'] = 1;
                    foreach($resp as $key => $response) {
                       if(isset($response['page_count'])) {
                           $result['page_count'] = $response['page_count'];
                           unset($resp[$key]);
                       }
                    }
                }
                $result['data'] = $resp;
                echo json_encode($result);
            }
    }
    }
    
    /*
     * *************************************************************************************
     * Function name   : admin_polling
     * Description     : get all admin polling message
     * Created Date    : 28-03-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function admin_polling($receipient_id = null, $params = array()){

        $hmPerformanceArr = [PERFORMANCE_RATING_SR_FOLLOW_UP_1,PERFORMANCE_RATING_SR_FOLLOW_UP_2]; //Hiring Manager Welcome Performance Notification
        $hmPerformanceArr = array_flip($hmPerformanceArr);
        
        $notifyTable = TableRegistry::get('Notifications');
        $current_time = $notifyTable->get_current_time();
        $jobIds = $result = [];
        $count = '';
        $select = ['id', 'typeID','sender', 'receipients', 'sendout_id', 'navigation', 'job_submission_id', 'placement_id', 'notification_type.id', 'notification_type.type', 'notification_type.mobile_notification', 'notification_type.message_text', 'sendout.joborder_id', 'Notifications.trigger_timestamp', 'sendout.candidate_id', 'sendout.selected_appointment_id', 'sendout.placement_coordinator_id','Notifications.email_verify_id', 'user.bullhorn_entity_id'];
        $adminRoleCheck = SALESREP_ROLE;
        $oneWeekTime = strtotime("-120 hours", $current_time);
        $condition = [ 'Notifications.status' => 1, 'Notifications.polling_status' => 1,'Notifications.trigger_timestamp <=' => $oneWeekTime];
        $limit = 300; // admin polling notification counts
        $data = $notifyTable->find('all')->select($select)->join([
                            'notification_type' => [
                                'table' => 'notification_type',
                                'type' => 'INNER',
                                'conditions' => 'typeID = notification_type.id'
                            ],
                            'sendout' => [
                                'table' => 'sendout',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.sendout_id = sendout.sendout_id OR Notifications.appointment_id = sendout.appointment_id'
                            ],
                            'email' => [
                                'table' => 'email_verification',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.email_verify_id = email.id'
                            ],
                            'user' => [
                                'table' => 'user',
                                'type' => 'LEFT',
                                'conditions' => 'email.user_id = user.id'
                            ],
                            'sales' => [
                                'table' => 'user',
                                'type' => 'INNER',
                                'conditions' => "Notifications.receipients = sales.bullhorn_entity_id AND sales.role ='$adminRoleCheck'"
                            ]
                            ])->where($condition)->orderDesc('Notifications.trigger_timestamp')->limit($limit);
        
        if ($data->count()) {
            $data = $data->toArray();
            //$count = count($data);
            foreach($data as $jobId) {
               if(isset($jobId['sendout']['joborder_id'])) {
                   $jobIds[] =  $jobId['sendout']['joborder_id'];
               }
            }
            $result = $this->getJobTitle($jobIds);

            foreach ($data as $key => $value) { 
                if (isset($value['sendout']['joborder_id'])) {
                    if(isset($result[$value['sendout']['joborder_id']])) {
                        $value['sendout']['joborder_title'] = $result[$value['sendout']['joborder_id']];
                    }
                    if(isset($hmPerformanceArr[$value['typeID']])) {
                            $value['notification_type']['message_text'] = $this->get_hmName($value['sendout']['placement_coordinator_id'], $value['notification_type']['message_text']);
                    } elseif($value['typeID'] == INTERVIEW_FOLLOW_UP_2) {
                            $value['notification_type']['message_text'] = $this->get_hmName($value['sendout']['candidate_id'], $value['notification_type']['message_text']);
                    } elseif(($value['typeID'] == ALTER_INTERVIEW_SCHEDULE_SR) || ($value['typeID'] == ALTER_INTERVIEW_SCHEDULE_HM)) {
                            $value['notification_type']['message_text'] = $this->get_hmName($value['sender'], $value['notification_type']['message_text']);
                            $value['user']['bullhorn_entity_id'] = $value['sender'];
                    } else {
                        
                    }
                }
                $data[$key]['created'] = $this->time_elapsed_string(date('Y-m-d H:i:s', $value['trigger_timestamp']));
            }
            if (isset($params['type'])) //for showing count ajax based notifications
                $data['count'] = $this->get_poll_count($select,$adminRoleCheck,$condition);
            return $data;
        } else {
            return [];
        }
    }
    
        /*
     * *************************************************************************************
     * Function name   : get_poll_count
     * Description     : to get admin notification polling count
     * Created Date    : 11-04-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function get_poll_count($select,$adminRoleCheck,$condition) {
        $count = '';
        $notifyTable = TableRegistry::get('Notifications');
        $data = $notifyTable->find('all')->select($select)->join([
                            'notification_type' => [
                                'table' => 'notification_type',
                                'type' => 'INNER',
                                'conditions' => 'typeID = notification_type.id'
                            ],
                            'sendout' => [
                                'table' => 'sendout',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.sendout_id = sendout.sendout_id OR Notifications.appointment_id = sendout.appointment_id'
                            ],
                            'email' => [
                                'table' => 'email_verification',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.email_verify_id = email.id'
                            ],
                            'user' => [
                                'table' => 'user',
                                'type' => 'LEFT',
                                'conditions' => 'email.user_id = user.id'
                            ],
                            'sales' => [
                                'table' => 'user',
                                'type' => 'INNER',
                                'conditions' => "Notifications.receipients = sales.bullhorn_entity_id AND sales.role ='$adminRoleCheck'"
                            ]
                            ])->where($condition)->orderDesc('Notifications.trigger_timestamp');
        if ($data->count()) {
            $data = $data->toArray();
            $count = count($data);
        } 
        return $count;
    }
    
    /*
     * *************************************************************************************
     * Function name   : getJobTitle
     * Description     : get all job title
     * Created Date    : 28-03-2017
     * Created By      : Balasuresh A
     * *************************************************************************************
     */
    public function getJobTitle($jobIds = []) {
        $response = [];
        if(!empty($jobIds)) {
            $jobList = array_unique($jobIds);
            $jobListId = implode(',', $jobList);
            $this->BullhornConnection->BHConnect();
            $fields = 'id,title';
            $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $jobListId . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
            $post_params = json_encode($jobListId);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            if(!empty($response)) {
                $response = $this->check_zero_index($response);
                $response = $response['data'];
                $response = array_column($response,'title','id');
            }
        }
        return $response;
    }

    /*
     * *************************************************************************************
     * Function name   : get_polling
     * Description     : get all polling message
     * Created Date    : 02-11-2016
     * Created By      : Sivaraj V
     * *************************************************************************************
     */

    public function get_polling($receipient_id = null, $params = array()) {
        
        $hmNotifyArr = [HM_FOLLOW_UP_1,HM_FOLLOW_UP_2]; //Hiring Manager Welcome Email Notification
        $hmNotifyArr = array_flip($hmNotifyArr);
        $hmPerformanceArr = [PERFORMANCE_RATING_SR_FOLLOW_UP_1,PERFORMANCE_RATING_SR_FOLLOW_UP_2,PERFORMANCE_RATING_ADMIN_FOLLOW_UP]; //Hiring Manager Welcome Performance Notification
        $hmPerformanceArr = array_flip($hmPerformanceArr);
        $page_count = '';
        
        $notifyTable = TableRegistry::get('Notifications');
        $current_time = $notifyTable->get_current_time();
        $condition = ['receipients' => $receipient_id, 'Notifications.status' => 1, 'Notifications.polling_status' => 1, 'Notifications.trigger_timestamp <=' => $current_time];
        $select = ['id', 'typeID','sender', 'receipients', 'sendout_id', 'navigation', 'job_submission_id', 'placement_id', 'notification_type.id', 'notification_type.type', 'notification_type.mobile_notification', 'notification_type.message_text', 'sendout.joborder_id', 'Notifications.trigger_timestamp', 'sendout.candidate_id', 'sendout.selected_appointment_id', 'sendout.placement_coordinator_id','sendout.desired_hourly_rate_to','Notifications.email_verify_id', 'user.bullhorn_entity_id'];
        $userTable = TableRegistry::get('Users');
        $userData = $userTable->find()->select(['role'])->where(['bullhorn_entity_id' => $receipient_id])->first();
        list($count, $limit) = $this->notification_polling_count($condition, $params);
        if ($userData->role == CANDIDATE_ROLE) {
            $condition['notification_type.mobile_notification <>'] = '';
        } else {
            $condition['notification_type.mobile_notification'] = '';
        }
        $data = $notifyTable->find('all')->select($select)->join([
                            'notification_type' => [
                                'table' => 'notification_type',
                                'type' => 'INNER',
                                'conditions' => 'typeID = notification_type.id'
                            ],
                            'sendout' => [
                                'table' => 'sendout',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.sendout_id = sendout.sendout_id OR Notifications.appointment_id = sendout.appointment_id'
                            ],
                            'email' => [
                                'table' => 'email_verification',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.email_verify_id = email.id'
                            ],
                            'user' => [
                                'table' => 'user',
                                'type' => 'LEFT',
                                'conditions' => 'email.user_id = user.id'
                            ]
                        ])->where($condition)
                        ->orderDesc('Notifications.trigger_timestamp')->limit($limit);
        
        /* Added the pagination for mobile applications */
        if(isset($params['platform']) && !empty($params['platform'])) {
            $per_page = isset($params['per_page']) ? $params['per_page'] : 10;
            $page_start = (isset($params['page'])) ? ($params['page'] - 1) * $per_page : 0;
            $notify_count = count($data->toArray());
            $page_count = ceil($notify_count / $per_page);
            
                    $data = $notifyTable->find('all')->select($select)->join([
                            'notification_type' => [
                                'table' => 'notification_type',
                                'type' => 'INNER',
                                'conditions' => 'typeID = notification_type.id'
                            ],
                            'sendout' => [
                                'table' => 'sendout',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.sendout_id = sendout.sendout_id OR Notifications.appointment_id = sendout.appointment_id'
                            ],
                            'email' => [
                                'table' => 'email_verification',
                                'type' => 'LEFT',
                                'conditions' => 'Notifications.email_verify_id = email.id'
                            ],
                            'user' => [
                                'table' => 'user',
                                'type' => 'LEFT',
                                'conditions' => 'email.user_id = user.id'
                            ]
                        ])->where($condition)
                        ->orderDesc('Notifications.trigger_timestamp')->offset($page_start)->limit($per_page);
        }

        if ($data->count()) {
            $data = $data->toArray();
            $statusArr = [
                CONTRACTOR_SENDOUT_REJECTION => 1,
                UNSUCCESSFUL_APPLICANTS => 1,
                CONF_OF_ASSIGNMENT => 2,
                FINAL_ASSIGNEMENT_CONF_A => 2,
                INTERVIEW_REQUEST => 3,
                INTERVIEW_REQUEST_REMINDER_1 => 4,
                INTERVIEW_REQUEST_REMINDER_2 => 4
            ]; //1=>RED, 2=>GREEN, 3=>BLUE, 4=>ORANGE
            foreach ($data as $key => $value) { 
                if (isset($value['sendout']['joborder_id'])) {
                    $valArr = $this->getTitleCompany($value['sendout']['joborder_id']);
                    $value['sendout']['joborder_title'] = $valArr['job_title'];
                    if ($value['typeID'] == CONF_OF_ASSIGNMENT) {
                        $findTextJob = array('[job]','[company]');
                        $replaceTextJob   = array($valArr['job_title'],$valArr['clientCorporation']);
                        $value['notification_type']['mobile_notification'] = str_replace($findTextJob, $replaceTextJob, $value['notification_type']['mobile_notification']);
                        //$value['notification_type']['mobile_notification'] = str_replace(['[job]', '[company]'], $valArr, $value['notification_type']['mobile_notification']);
                    } elseif ($value['typeID'] == INTERVIEW_CONF_NOTIFICATION_A) {
                        $interview_date = $this->getInterview($value['sendout']['selected_appointment_id']);
                        $interview_start_date = date('D M d,Y H:i A', $interview_date['interview_date']);
                        
                        $findText = array('[date]','[type]','[phone]');
                        $replaceText   = array($interview_start_date,$interview_date['interview_type'],$valArr['hm_phone']);
                        $value['notification_type']['mobile_notification'] = str_replace($findText, $replaceText, $value['notification_type']['mobile_notification']);
                       // $value['notification_type']['mobile_notification'] = str_replace('[date]', $interview_start_date, $value['notification_type']['mobile_notification']);
                        
                    } elseif($value['typeID'] == INTERVIEW_FOLLOW_UP_2) {
                            $value['notification_type']['message_text'] = $this->get_hmName($value['sendout']['candidate_id'], $value['notification_type']['message_text']);
                    } elseif (($value['typeID'] == PERFORMANCE_RATING_REQUEST) || ($value['typeID'] == PERFORMANCE_RATING_SR_FOLLOW_UP_1) || ($value['typeID'] == PERFORMANCE_RATING_SR_FOLLOW_UP_2) || ($value['typeID'] == PERFORMANCE_RATING_ADMIN_FOLLOW_UP)) {
                        $sendout_status = $this->sendout_status_update($value['typeID'],$value['sendout_id']);
                        $value['notification_type']['message_text'] = $this->get_hmName($value['sendout']['placement_coordinator_id'], $value['notification_type']['message_text']);
                    } elseif(($value['typeID'] == ALTER_INTERVIEW_SCHEDULE_SR) || ($value['typeID'] == ALTER_INTERVIEW_SCHEDULE_HM)) {
                            $value['notification_type']['message_text'] = $this->get_hmName($value['sender'], $value['notification_type']['message_text']);
                            $value['user']['bullhorn_entity_id'] = $value['sender'];
                    } else {
                        $value['notification_type']['mobile_notification'] = $value['notification_type']['mobile_notification'];
                    }
                } elseif(isset($hmNotifyArr[$value['typeID']])) {
                    $value['notification_type']['message_text'] = $this->get_salesRepName($value['user']['bullhorn_entity_id'], $value['notification_type']['message_text']);
                }
                $data[$key]['created'] = $this->time_elapsed_string(date('Y-m-d H:i:s', $value['trigger_timestamp']));
                if (array_key_exists($value['typeID'], $statusArr)) {
                    $data[$key]['status'] = $statusArr[$value['typeID']];
                } else {
                    $data[$key]['status'] = 0;
                }
            }
            $data[]['page_count'] = $page_count; // for display the page count for mobile applications
            if (isset($params['type'])) //for showing count ajax based notifications
                $data['count'] = $count;
            return $data;
        } else {
            return [];
        }
    }
    
    /*
     * Funtion name :  get_hmName
     * Description  :  To fetch the hiring manager name
     * Date         :  23-01-2017
     * Created by   :  Balasuresh
     */
    public function get_hmName($sender_id, $originalText) {
        $fullName = TableRegistry::get('Users')->full_name($sender_id);
        $convertedName = implode('', (array) $fullName['full_name']);
        $findText = array("First Name", "Last Name");
        $replaceText = array($convertedName);
        $newPhraseMessage = str_replace($findText, $replaceText, $originalText);
        return $newPhraseMessage;
    }
    
    /*
     * Funtion name :  getInterview
     * Description  :  To fetch interview begin date
     * Date         :  21-01-2017
     * Created by   :  Balasuresh
     */

    public function getInterview($appointment_id) {
        $this->BullhornConnection->BHConnect();
        $fields = 'dateBegin,type,communicationMethod';
        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $appointment_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($appointment_id);
        $req_method = 'GET';
        $res = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($res['data'])) {
            return ['interview_date' => $res['data']['dateBegin'], 'interview_type' => $res['data']['communicationMethod']];
        } else {
            return [];
        }
    }

    /**
     * Funtion name :  notification_polling_count
     * Description  :  To fetch notification count
     * Date         :  19-01-2017
     * Created by   :  Akilan
     */
    function notification_polling_count($condition, $params) {
        $notifyTable = TableRegistry::get('Notifications');
        $count = 0;
        $limit = 300;
        if (isset($params['type'])) {
            $count = $notifyTable->find('all')->where($condition)->count();
            $limit = 3;
        }
        return array($count, $limit);
    }

    /*     * ***************************************************************************************
     * Function name   : send sms
     * Description     : send sms with link
     * Created Date    : 14 -11-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function sendSms() {
        $this->autoRender = false;
        $params = $this->request->data;
        $sms = [];
        if (isset($params['number']) && !empty($params['number'])) {
            $mynumber = $this->Notification->validateSmsCountyCode($params['number']);
            $sms['marketing'] = $mynumber;
            $result = $this->Notification->sendsms($sms);
            $numberTable = TableRegistry::get('PhoneNumber');
            $number = $numberTable->newEntity();
            if ($result[$mynumber]['isSent']) {
                $number->status = 1;
            } else {
                $number->status = 0;
            }
            $number->number = $mynumber;
            if ($numberTable->save($number) && $result[$mynumber]['isSent']) {
                echo json_encode([
                    'status' => 1,
                    'message' => "Message is sent to your phone number " . $mynumber,
                ]);
            } else {
                echo json_encode([
                    'status' => 0,
                    'message' => "You will get a message to your phone number " . $mynumber . " as soon as possible. ",
                ]);
            }
        }
    }

    /*
     * *************************************************************************************
     * Function name   : jobmatch
     * Description     : send notification for job matching contractors, for cron process
     * Created Date    : 23-11-2016
     * Created By      : Sivaraj V
     * *************************************************************************************
     */

    public function jobmatch() {
        $this->autoRender = false;
        $this->Auth->allow();
        $jobmatch = TableRegistry::get('JobCandidateMatch');
        $getJobOrder = $jobmatch->find('all')->select(['id', 'joborder_id', 'status'])->where(['status' => 1])->toArray();
        if (!empty($getJobOrder)) { // store joborder only once
            $this->matchingCandidates($getJobOrder);
//pr($getJobOrder); exit;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : matchingCandidates
     * Description     : to get all matching candidates for a job
     * Created Date    : 23-11-2016
     * Created By      : Sivaraj V
     * *************************************************************************************
     */

    public function matchingCandidates($joborders = []) {
// $this->autoRender = false;
// $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $jobCandidateMatch = [];
        $jobCompany = [];
        $queryFilter = '';
        foreach ($joborders as $joborder) {
            $jobDetails = $this->getJobSkills($joborder['joborder_id']);
            $jobSkills = $jobDetails['skills'];
            $jobCompany[$joborder['joborder_id']] = $jobDetails['jobData'];
            if(isset($jobDetails['jobData']['address']['state']) && !empty($jobDetails['jobData']['address']['state'])) {
                $states = $jobDetails['jobData']['address']['state'];
                $queryFilter = '+AND+address.state:"'. $states .'"';
            }
//pr($jobSkills);
            $url = $_SESSION['BH']['restURL'] . '/search/Candidate?query=primarySkills.id:(' . implode('+OR+', array_keys($jobSkills)) . ')+AND+isDeleted:false' . $queryFilter . '&sort=-id&count=2000&BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,firstName,lastName,address,email,primarySkills[100](id,name),categories[100](id,name),phone';
            $post_params = json_encode([]);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//       pr($response);
            if (isset($response['data'])) {
                foreach ($response['data'] as $candidate) {
// Calculate Match %
                    $skills = [];
                    if (!empty($candidate['primarySkills']['data'])) {
                        foreach ($candidate['primarySkills']['data'] as $skill) {
                            $skills[] = $skill['name'];
                        }
                        $percent = $this->getMatchPercent($skills, $jobSkills);
                        if ($percent > 0) {
//$response['data'][$i]['candidateMatchPercent'] = $percent;
//$candidateMatchPercent[$i] = $percent;
                            $jobCandidateMatch[$joborder['joborder_id']][] = $candidate['id'];
                        }
                    }
                }
            }
        }
        $this->getContractors(['jobCandidateMatch' => $jobCandidateMatch, 'jobCompany' => $jobCompany]);
//pr($response); exit;
    }

    /*
     * *************************************************************************************
     * Function name   : getContractors
     * Description     : to get all contractors
     * Created Date    : 23-11-2016
     * Created By      : Sivaraj V
     * *************************************************************************************
     */

    public function getContractors($jobCandidateMatch = null) {
        $userTable = TableRegistry::get('User');
        $allNotifcations = [];
//$listForSalesRepEmail = [];
        $contractorEmails = [];
        $salesRepEmails = [];
        $jobOrderIds = [];
        foreach ($jobCandidateMatch['jobCandidateMatch'] as $joborder_id => $candidate) { //pr($candidate); exit;
            $jobOrderIds[] = $joborder_id;
            $getUsers = $userTable->find('all')->select(['id', 'firstName', 'lastName', 'email', 'bullhorn_entity_id', 'phone', 'device_id', 'device_type'])->where(['bullhorn_entity_id IN' => $candidate, 'role' => CANDIDATE_ROLE])->toArray();
            $tableData = "";
            if (!empty($getUsers)) { // store joborder only once
                $n = 0;
                foreach ($getUsers as $user) {
                    $name = !empty($user['firstName']) ? " " . ucfirst($user['firstName']) . "," : ",";
                    $title = "Hi" . $name;
                    if ($user['device_id'] != "" || !empty($user['device_id'])) {
                        $extra_data = array('joborder_id' => $joborder_id, 'navigation' => NAVIGATION_JOB); //for screen redirection
                        $notification = array('title' => $title, 'body' => 'A job has found you! Your profile matches one of our openings.  Click to review and apply',); // 'badge' => substr(strtotime(date('d-m-Y H:i:s')), -4));
//This array contains, the token and the notification. The 'to' attribute stores the token.
                        $fields = array('to' => $user['device_id'], 'notification' => $notification, 'data' => array('response' => $extra_data));
//                $fields = json_encode($fields);
                        $allNotifcations[$joborder_id] = [
                            'post_data' => $fields
                        ];
                    }
                    if (isset($jobCandidateMatch['jobCompany'][$joborder_id])) {
                       // $contractorEmails[$joborder_id] = [
                        $contractorEmails[] = [
                            'to' => $user['email'],
                            'mail_subject' => 'A job has found you!',
                            'message' => [
                                'candidate' => $user,
                                'joborder' => $jobCandidateMatch['jobCompany'][$joborder_id],
                            ],
                            'typeID' => CONTRACTOR_PRELIMINARY_MATCH,
                        ];
                    }
//$tableData .= "<tr><td>" . ++$n . "</td><td>" . $user['firstName'] . "</td><td>" . $user['lastName'] . "</td><td><a href='mailto:" . $user['email'] . "'>" . $user['email'] . "</a></td><td>" . $user['phone'] . "</td><tr/>";
                }
            }
            if (isset($jobCandidateMatch['jobCompany'][$joborder_id])) {
                $getSalesRepId = TableRegistry::get('Users')->get_sales_rep_id($jobCandidateMatch['jobCompany'][$joborder_id]['clientCorporation']['id']);
                $getSalesRep = TableRegistry::get('Users')->get_email($getSalesRepId, 'bullhorn_entity_id');
                $hiringManager = TableRegistry::get('Users')->get_email($jobCandidateMatch['jobCompany'][$joborder_id]['clientContact']['id'], 'bullhorn_entity_id');
                /* $tableStart = "<table border=1>";
                  $jobDet = $jobCandidateMatch['jobCompany'][$joborder_id]['id'] . "/" . $jobCandidateMatch['jobCompany'][$joborder_id]['title'];
                  $companyDet = $jobCandidateMatch['jobCompany'][$joborder_id]['clientCorporation']['id'] . "/" . $jobCandidateMatch['jobCompany'][$joborder_id]['clientCorporation']['name'];
                  $jD = "<tr><td colspan=5>Job Id/Title: " . $jobDet . "</td></tr>";
                  $jD .= "<tr><td colspan=5>Company Id/Name: " . $companyDet . "</td></tr>";
                  $jD .= "<tr><td colspan=5>Job Posted By: " . $hiringManager[0]['firstName'] . "/" . $hiringManager[0]['email'] . "/" . $hiringManager[0]['phone'] . "</td></tr>";
                  $jD .= "<tr><td colspan=5>Candidates initial matching list</td></tr>";
                  $tableColumn = "<tr><td>#</td><td>FirstName</td><td>LastName</td><td>Email</td><td>Phone</td></tr>";
                  $tableEnd = "</table>";
                  $listForSalesRepEmail[$joborder_id] = [
                  'to' => $getSalesRep[0]['email'],
                  'subject' => $jobDet . ':Initial Match List',
                  'message' => $tableStart . $jD . $tableColumn . $tableData . $tableEnd
                  ]; */
                $salesRepEmails[$joborder_id] = [
                    'to' => $getSalesRep[0]['email'],
                    'mail_subject' => 'Initial Passive Match List',
                    'message' => [
                        'salesrep' => $getSalesRep[0],
                        'joborder' => $jobCandidateMatch['jobCompany'][$joborder_id],
                    ],
                    'typeID' => SALESREP_INITIAL_MATCH_LIST,
                ];
            }
        }
//pr($listForSalesRepEmail);
        $notify = $this->Notification->multiRequest($allNotifcations);
//$this->Notification->email($listForSalesRepEmail);
        $this->Notification->email($contractorEmails);
        $this->Notification->email($salesRepEmails);
//        $jobIds = [];
//        foreach ($notify as $jobid => $result) {
//            $jobIds[] = $jobid;
//        }
        TableRegistry::get('JobCandidateMatch')->updateAll(['status' => 0], ['joborder_id in' => $jobOrderIds]);
        /* if (!empty($jobIds) && TableRegistry::get('JobCandidateMatch')->updateAll(['status' => 0], ['joborder_id in' => $jobIds])) {
          // make status 0 after sending notification only once
          } */
    }

    /*
     * *************************************************************************************
     * Function name   : getJobSkills
     * Description     : to get all skills of a job order
     * Created Date    : 23-11-2016
     * Created By      : Sivaraj V
     * *************************************************************************************
     */

    public function getJobSkills($joborder_id = null) {
        $this->BullhornConnection->BHConnect();
        $fields = 'id,title,address,skills[200](id,name),customText1,customText5,customText6,customFloat1,customFloat2,startDate,durationWeeks,skillList,yearsRequired,categories[100](id,name),clientContact,clientCorporation';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $joborder_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $skills = [];
        if (isset($response['data'])) {
            if (!empty($response['data']['skills']['data'])) {
                foreach ($response['data']['skills']['data'] as $skill) {
                    $skills[$skill['id']] = $skill['name'];
                }
            }
            $data = $response['data'];
        }
        return ['skills' => $skills, 'jobData' => $data];
    }

    /*
     * *************************************************************************************
     * Function name   : getJobOrder
     * Description     : to get job title of a job order
     * Created Date    : 02-12-2016
     * Created By      : Balasuresh A
     * *************************************************************************************
     */

    public function getJobOrder($emails_jobs = []) {
        $job_ordersIDs = [0];
        $appt_ar = '';
        $finalData = [];
        $appointment_sendout_ar = $email_sendout = $selected_appointment_ar = $placement_co_id_ar = $appointment_response = $intw_id_ar = [];
        $hiring_manager_data = $placement_co_id_det = [];
        if (!empty($emails_jobs)) {
            foreach ($emails_jobs as $emails_job) { /* selecting the sendout ids from the email list */
                if (!empty($emails_job['sendout_id'])) :
                    $email_sendout[] = $emails_job['sendout_id'];
                endif;
                if ($emails_job['typeID'] >= INTERVIEW_CONF_NOTIFICATION_A && $emails_job['typeID'] <= UNRESPONSIGN_INTERVIEW_2):
                    $appointmen_sendout_ar[$emails_job['sendout_id']] = $emails_job['sendout_id'];
                endif;
                if (!empty($emails_job['appointment_id']))
                    $appt_ar[] = $emails_job['appointment_id'];
            }

            if (!empty($email_sendout) || !empty($appt_ar)) {
                $sendoutTable = TableRegistry::get('Sendout')->find('all')->select(['sendout_id', 'placement_coordinator_id', 'selected_appointment_id', 'appointment_id', 'interviewer_id', 'joborder_id', 'placement_id','desired_hourly_rate_to'])
                        ->where(['OR' => [['sendout_id IN' => $email_sendout], ['appointment_id IN' => $appt_ar]]]);
                if (!empty($sendoutTable)) {
                    $sendoutTable = $sendoutTable->toArray();
                    foreach ($emails_jobs as $key => $val) {
                        foreach ($sendoutTable as $sendoutTabl) {
                            if (($sendoutTabl['sendout_id'] == $val['sendout_id']) || ($sendoutTabl['appointment_id'] == $val['appointment_id'] && !is_null($sendoutTabl['appointment_id']))) {
                                $emails_jobs[$key]['joborder_id'] = $sendoutTabl['joborder_id'];
                                $emails_jobs[$key]['placement_id'] = $sendoutTabl['placement_id'];
                                $emails_jobs[$key]['appointment_id'] = $sendoutTabl['selected_appointment_id'];
                                $emails_jobs[$key]['placement_coordinator_id'] = $sendoutTabl['placement_coordinator_id'];
                                $emails_jobs[$key]['candidate_bid_value'] = $sendoutTabl['desired_hourly_rate_to'];
                            }

                            $job_ordersIDs[$sendoutTabl['joborder_id']] = $sendoutTabl['joborder_id'];
                            if (!is_null($sendoutTabl['selected_appointment_id'])):
                                $selected_appointment_ar[$sendoutTabl['sendout_id']] = $sendoutTabl['selected_appointment_id'];
                                $intw_id_ar[$sendoutTabl['sendout_id']] = $sendoutTabl['interviewer_id'];
                            endif;
                            if (!is_null($sendoutTabl['placement_coordinator_id'])):
                                $placement_co_id_ar[$sendoutTabl['sendout_id']] = $sendoutTabl['placement_coordinator_id'];
                            endif;
                        }
                    }

//for getting interviewer first and last name;
                    $hiring_manager_data = $this->hiring_manager_data($intw_id_ar);
                    if (!empty($placement_co_id_ar))
                        $placement_co_id_det = $this->hiring_manager_data($placement_co_id_ar);
                    $job_ordersIDs = implode(',', array_filter(array_unique($job_ordersIDs)));

                    $this->BullhornConnection->BHConnect();
                    $fields = 'id,title,clientContact(phone),clientCorporation(id,name,address),customText1,customText5,customText6,startDate,durationWeeks,customFloat1,customFloat2';
                    $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $job_ordersIDs . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                    $post_params = json_encode([]);
                    $req_method = 'GET';
                    if (empty($selected_appointment_ar)) {
                        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                    } else {
                        $curl_data[0]['post_data'] = json_encode($post_params);
                        $curl_data[0]['url'] = $url;
                        $curl_data[0]['req_method'] = $req_method;
                        $selected_appointment_str = implode(',', $selected_appointment_ar);
                        $fields = 'id,dateAdded,dateBegin,dateEnd,communicationMethod,candidateReference(firstName,lastName,customTextBlock3)';
                        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $selected_appointment_str . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                        $curl_data[1]['url'] = $url;
                        $curl_data[1]['req_method'] = $req_method;
                        $curl_data[1]['post_data'] = json_encode($post_params);
                        $response1 = $this->BullhornCurl->multiRequest($curl_data);

                        $response = json_decode($response1[0], True);
                        $appointment_response = json_decode($response1[1], True);
                    }

                    if (isset($response['data'])) {
                        $response = $this->check_zero_index($response);

                        foreach ($emails_jobs as $key => $emails_job_sgl) {
                            $emails_job = array();
                            $emails_job = $emails_job_sgl;
                            if (isset($response['data'][0])) {
                                foreach ($response['data'] as $responseTitle) {
                                    if ($responseTitle['id'] == $emails_job['joborder_id']) {
//                                    if (in_array($responseTitle['id'], $emails_job)) {
                                        $_SESSION['job_' . $emails_job['joborder_id']]['joborder_title'] = $responseTitle['title'];
                                        $_SESSION['job_' . $emails_job['joborder_id']]['company'] = $responseTitle['clientCorporation']['name'];
                                        $_SESSION['job_' . $emails_job['joborder_id']]['client_phone'] = $responseTitle['clientContact']['phone'];
                                        $emails_job['joborder_title'] = $responseTitle['title'];
                                        $emails_job['employer'] = $responseTitle['clientCorporation']['name'];
                                        $emails_job['company_address'] = $responseTitle['clientCorporation']['address'];
                                        $emails_job['client_phone'] = $responseTitle['clientContact']['phone'];
                                        $emails_job['location'] = $responseTitle['customText1'];
                                        $emails_job['startDate'] = $responseTitle['startDate'];
                                        $emails_job['durationWeeks'] = $responseTitle['durationWeeks'];
                                        $emails_job['minHourlyRate'] = $responseTitle['customText5'];
                                        $emails_job['maxHourlyRate'] = $responseTitle['customText6'];
                                        $emails_job['minPayRate'] = $responseTitle['customFloat1'];
                                        $emails_job['maxPayRate'] = $responseTitle['customFloat2'];
                                        $emails_job['joborder_id'] = $responseTitle['id'];
                                        if (isset($placement_co_id_ar[$emails_job['sendout_id']]) && isset($placement_co_id_det[$emails_job['placement_coordinator_id']])) {
                                            $emails_job['placement_coordintator_name'] = $placement_co_id_det[$emails_job['placement_coordinator_id']];
                                        }
                                    }
                                }
                            }
                            if (!empty($appointment_response) && isset($appointment_response['data'])) {
                                $appointment_response = $this->check_zero_index($appointment_response);

                                foreach ($appointment_response['data'] as $appointment_data) {
                                    if ($emails_job['appointment_id'] == $appointment_data['id']) {
                                        $emails_job['dateAdded'] = $appointment_data['dateAdded'];
                                        $_SESSION['job_' . $emails_job['joborder_id']]['dateBegin'] = $appointment_data['dateBegin'];
                                        $_SESSION['job_' . $emails_job['joborder_id']]['communication_type'] = $appointment_data['communicationMethod'];
                                        $emails_job['dateBegin'] = $appointment_data['dateBegin'];
                                        $emails_job['dateEnd'] = $appointment_data['dateEnd'];
                                        $emails_job['placement_contractor_fname'] = $appointment_data['candidateReference']['firstName'];
                                        $emails_job['placement_contractor_lname'] = $appointment_data['candidateReference']['lastName'];
                                        $emails_job['communication_type'] = $appointment_data['communicationMethod'];
                                        $emails_job['placement_contractor_skype'] = $appointment_data['candidateReference']['customTextBlock3'];
                                        if (isset($intw_id_ar[$emails_job['sendout_id']]) && isset($hiring_manager_data[$intw_id_ar[$emails_job['sendout_id']]])) {
                                            $emails_job['hiring_manager'] = $hiring_manager_data[$intw_id_ar[$emails_job['sendout_id']]];
                                        }
                                    }
                                }
                            }

                            $finalData[] = $emails_job;
                        }
                    }
                    return $finalData;
                } else {
                    return $emails_jobs;
                }
            } else {
                return $emails_jobs;
            }
        }
    }

    /*
     * *************************************************************************************
     * Function name   : get hiring manager list
     * Description     : to get hiring manager fullname
     * Created Date    : 12-12-2016
     * Created By      : Akilan
     * *************************************************************************************
     */

    protected function hiring_manager_data($intw_id_ar) {
        $hiring_mgr_data = array();
        if (!empty($intw_id_ar)) {
            $users = TableRegistry::get('Users');
            $hiring_mgr_data = $users->get_hiringmanager_list($intw_id_ar);
        }
        return $hiring_mgr_data;
    }

    protected function time_elapsed_string($datetime, $full = false) {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full)
            $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    /*
     * *************************************************************************************
     * Function name   : getFirstName
     * Description     : to get fullname of sales representative and email title
     * Created Date    : 12-12-2016
     * Created By      : Akilan
     * *************************************************************************************
     */

    public function getFirstName($firstName, $lastName) {
        $name = (!empty($firstName) || !empty($lastName)) ? " " . ucfirst($firstName) . " " . ucfirst($lastName) . "," : ",";
        return $name;
    }

    /*     * ***********************************************************************************
     * Function name   : status_update_reminder
     * Description     : send reminder to the contractor, who haven't logged past two weeks
     * Created Date    : 22-11-2016
     * Created By      : Balasuresh A
     * ************************************************************************************* */

    public function statusReminder() {
        $this->autoRender = false;
        $this->loadModel('Users');
        $current_time = strtotime(date('d-m-Y H:i:s', time()));
        $fourteenDaystime = strtotime("-336 hours", $current_time); // checking 2 weeks last login
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        $userLists = $this->Users->find('all')->select(['bullhorn_entity_id'])->where(['isActive' => 1, 'status' => 1, 'role' => 'contractor', 'last_login !=' => 0, 'last_login' <= $fourteenDaystime]);
        $data = [
            'typeID' => STATUS_UPDATE_REMINDER,
            'trigger_timestamp' => $accept_notifycation
        ];
        if (!empty($userLists)) {
            $userLists = $userLists->toArray();
            foreach ($userLists as $userList) {
                $tmp_data = $data;
                $tmp_data['receipients'] = $userList->bullhorn_entity_id;
                $final_data[] = $tmp_data;
            }
            $notify = TableRegistry::get('Notifications');
            $notify->datasave($final_data);
        }
    }

    public function getTitleCompany($joborder_id) {
        $this->BullhornConnection->BHConnect();
        $fields = 'clientCorporation,title,clientContact(phone)';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $joborder_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($joborder_id);
        $req_method = 'GET';
        $res = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($res['data'])) {
            return ['job_title' => $res['data']['title'],'clientCorporation' => $res['data']['clientCorporation']['name'],'hm_phone' => $res['data']['clientContact']['phone']];
//            return [$res['data']['title'], $res['data']['clientCorporation']['name']];
        } else {
            return [];
        }
    }
    
    /* ************************************************************************************
     * Function name   : remove_notify
     * Description     : to remove the notifications, from mobile applications
     * Created Date    : 06-05-2017
     * Modified Date   : 
     * Created By      : Balasuresh A
     * Modified By     : 
     * ************************************************************************************/
    public function remove_notify($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        if(isset($params['id']) && !empty($params['id'])) {
            $notifyTable = TableRegistry::get('Notifications');
            $notifyTable->query()->update()->set(['status' => 0])->
                    where(['id' => $params['id']])->execute();
            echo json_encode(['status' => 1, 'message' => 'Updated Successfully']);
        } else {
            echo json_encode(['status' => 0, 'message' => 'Please send the Notification ID']);
        }
    }
    
    /* ************************************************************************************
     * Function name   : sendEmail
     * Description     : to store the email address, from marketing site
     * Created Date    : 14-06-2017
     * Modified Date   : 
     * Created By      : Balasuresh A
     * Modified By     : 
     * ************************************************************************************/
    public function sendEmail() {
        $this->autoRender = false;
        $params = $this->request->data;
        if (isset($params['number']) && !empty($params['number'])) {
            $emailTable = TableRegistry::get('EmailAddress');
            $email = $emailTable->newEntity();
            $email->email = $params['number'];
            if ($emailTable->save($email)) {
                echo json_encode([
                    'status' => 1,
                    'message' => "Thanks for subscribing! Welcome to part of peoplecaddie",
                ]);
            } else {
                echo json_encode([
                    'status' => 0,
                    'message' => "There is some problem occured. Please try again later",
                ]);
            }
        }
    }

}

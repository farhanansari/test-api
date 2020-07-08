<?php

/* * ************************************************************************************
 * Class name      : NotificationsTable
 * Description     : Keep/store notifications detail
 * Created Date    : 25-10-2016
 * Created By      : Akilan
 * ************************************************************************************* */

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;
use App\Model\Table\Sendout;

class NotificationsTable extends Table {

    public function initialize(array $config) {
        $this->table('notifications');
        // $this->displayField('username');
        $this->primaryKey('id');
    }

    public function datasave($params) {
        $entities = $this->newEntities($params);
        foreach ($entities as $entity) {
            $this->save($entity);
        }
    }

    /*     * **************************************************************************************
     * Function name   : notification_data
     * Description     : Seperate notification detail for implementing time based notification
     * Created Date    : 26-10-2015
     * Created By      : Akilan
     * ************************************************************************************* */

    public function notification_data($params) {
        switch ($params['type']) {
            case "application_accept" :
                $this->application_accept_notify($params);
                break;
            case "interview_appointment_reminder_save" :
                $this->interview_appointment_reminder_save($params);
                break;
            case "client_registration_process_save" :
                $this->client_registration_process_save($params);
                break;
            case "profile_completion_reminder" :
                $this->profile_completion_reminder_save($params);
                break;
            case "hiring_manager_registration" :
                $this->hiring_manager_registration_process_save($params);
                break;
            case "contractor_registration" :
                $this->contractor_registration_process_save($params);
                break;
            case "candidate_match_update" :
                $this->candidate_match_update($params);
                break;
            case "performance_rating_request" :
                $this->performance_rating_request($params);
                break;
            case "profile_update_notification" :
                $this->profile_update_notification($params);
                break;
            case "review_contractors";
                $this->review_contractors($params);
                break;
            case "contractor_sendout_reject" :
                $this->sendout_rejection($params);
                break;
            case "assignment_confirm_process":
                $this->assignement_confirm_notification($params);
                break;
            case "final_assignment_confirmation":
                $this->final_assignment_confirmation($params);
                break;
            case "slate_update_notify":
                $this->slate_update_notify($params);
                break;
            case "interview_follow_up_start_contractor":
                $this->interview_follow_up($params);
                break;
        }
    }

    /*     * **************************************************************************************
     * Function name   : application_accept_notify
     * Description     : Created notifications based on application accept and stored in table
     * Created Date    : 26-10-2015
     * Created By      : Akilan
     * ************************************************************************************* */

    public function application_accept_notify($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
//        $after_2hrs = strtotime('+3 minutes', $current_time);
//        $after_4hrs = strtotime('+6 minutes', $current_time);        
        $after_2hrs = strtotime('+2 hours', $current_time);
        $after_4hrs = strtotime('+4 hours', $current_time);       
        $after_4hrs_add_min = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $after_4hrs)));
        $salesrep_id = TableRegistry::get('Users')->get_sales_rep_id($params['company_id']);
        $data[] = array('sender' => $params['hiring_manager_id'], 'receipients' => $params['candidate_id']
            , 'trigger_timestamp' => $accept_notifycation, 'navigation' => NAVIGATION_APPOINTMENT, 'typeID' => INTERVIEW_REQUEST, 'sendout_id' => $params['sendout_id']);
        $data[] = array('sender' => $params['hiring_manager_id'], 'receipients' => $params['candidate_id']
            , 'trigger_timestamp' => $after_2hrs, 'navigation' => NAVIGATION_APPOINTMENT, 'typeID' => INTERVIEW_REQUEST_REMINDER_1, 'sendout_id' => $params['sendout_id']);
        $data[] = array('sender' => $params['hiring_manager_id'], 'receipients' => $params['candidate_id']
            , 'trigger_timestamp' => $after_4hrs, 'navigation' => NAVIGATION_APPOINTMENT, 'typeID' => INTERVIEW_REQUEST_REMINDER_2, 'sendout_id' => $params['sendout_id']);
        if (!empty($salesrep_id)) {
            $data[] = array('sender' => $params['candidate_id'], 'receipients' => $salesrep_id
                , 'trigger_timestamp' => $after_4hrs_add_min, 'typeID' => CONTRACTOR_UNRESPONSIVE_INTERVIEW_REQUEST, 'sendout_id' => $params['sendout_id']);
        }
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : assignement_confirm_notification
     * Description     : job submission based notification for HM & contractor
     * Created Date    : 17-11-2016
     * Created By      : Akilan
     * Modified on     : 21-11-2016
     * ************************************************************************************* */

    public function assignement_confirm_notification($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
//        $after_2hrs = strtotime('+2 minutes', $current_time);
//        $after_4hrs = strtotime('+4 minutes', $current_time);
//        $after_6hrs = strtotime('+6 minutes', $current_time);        
        $after_2hrs = strtotime('+2 hours', $current_time);
        $after_4hrs = strtotime('+4 hours', $current_time);
        $after_6hrs = strtotime('+6 hours', $current_time);
        $salesrep_id = TableRegistry::get('Users')->get_sales_rep_id($params['company_id']);
        $data[] = array('sender' => $params['action_user_id'], 'receipients' => $params['action_user_id']
            , 'trigger_timestamp' => $accept_notifycation, 'sendout_id' => $params['sendout_id'], 'job_submission_id' => $params['job_submission_id'], 'typeID' => ASSIGNMENT_PENDING_APPLICANT_CONF);
        $data[] = array('sender' => $params['action_user_id'], 'receipients' => $params['candidate_id'], 'navigation' => NAVIGATION_OFFER
            , 'trigger_timestamp' => $accept_notifycation, 'sendout_id' => $params['sendout_id'], 'job_submission_id' => $params['job_submission_id'], 'typeID' => CONF_OF_ASSIGNMENT);
        $data[] = array('sender' => $params['action_user_id'], 'receipients' => $params['candidate_id'], 'navigation' => NAVIGATION_OFFER
            , 'trigger_timestamp' => $after_2hrs, 'sendout_id' => $params['sendout_id'], 'job_submission_id' => $params['job_submission_id'], 'typeID' => ASSIGNMENT_AWAITING_REMINDER_1);
        $data[] = array('sender' => $params['action_user_id'], 'receipients' => $params['candidate_id'], 'navigation' => NAVIGATION_OFFER
            , 'trigger_timestamp' => $after_4hrs, 'sendout_id' => $params['sendout_id'], 'job_submission_id' => $params['job_submission_id'], 'typeID' => ASSIGNMENT_AWAITING_REMINDER_2);
        if (!empty($salesrep_id)) {
            $data[] = array('sender' => $params['action_user_id'], 'receipients' => $salesrep_id
                , 'trigger_timestamp' => $after_6hrs, 'sendout_id' => $params['sendout_id'], 'job_submission_id' => $params['job_submission_id'], 'typeID' => CONTRACTOR_UNRESPONSIVE_CONF_REQUEST);
        }
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : interview_appointment_reminder_save
     * Description     : Save all details about interview appointment after time selection for interview
     * Created Date    : 31-10-2015
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function interview_appointment_reminder_save($params) {
        $appointment_time = $params['trigger_timestamp'];
//        $before_2hrs = strtotime('-2 hours', $appointment_time);
//        $before_24hrs = strtotime('-24 hours', $appointment_time);        

        $before_2hrs = strtotime('-450 minutes', $appointment_time); //gmt hours -5.30 back so 7.30 hours back 7*60+30=450
        $before_24hrs = strtotime('-1770 minutes', $appointment_time); //gmt hours -5.30 back 24*60+30=1470
        $current_time = $this->get_current_time();        
        $str_appt_time = strtotime($appointment_time);
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        $data[] = array('sender' => $params['sender'], 'receipients' => $params['receipients'], 'navigation' => NAVIGATION_APPOINTMENT_DETAIL
            , 'trigger_timestamp' => $accept_notifycation, 'typeID' => INTERVIEW_CONF_NOTIFICATION_A, 'sendout_id' => $params['sendout_id']);
        $data[] = array('sender' => $params['receipients'], 'receipients' => $params['sender']
            , 'trigger_timestamp' => $accept_notifycation, 'typeID' => INTERVIEW_CONF_NOTIFICATION_B, 'sendout_id' => $params['sendout_id']);
     
        if (intval($before_24hrs) >= intval($current_time)) {
            $data[] = array('sender' => $params['sender'], 'receipients' => $params['receipients'], 'navigation' => NAVIGATION_APPOINTMENT_DETAIL
                , 'trigger_timestamp' => $before_24hrs, 'fixed_timestamp' => $appointment_time, 'typeID' => INTERVIEW_APP_REMINDER_1A, 'sendout_id' => $params['sendout_id']);
            $data[] = array('sender' => $params['receipients'], 'receipients' => $params['sender']
                , 'trigger_timestamp' => $before_24hrs, 'fixed_timestamp' => $appointment_time, 'typeID' => INTERVIEW_APP_REMINDER_1B, 'sendout_id' => $params['sendout_id']);
        }
        if (intval($before_2hrs) >= intval($current_time)) {
            $data[] = array('sender' => $params['sender'], 'receipients' => $params['receipients'], 'navigation' => NAVIGATION_APPOINTMENT_DETAIL
                , 'trigger_timestamp' => $before_2hrs, 'fixed_timestamp' => $appointment_time, 'typeID' => INTERVIEW_APP_REMINDER_2A, 'sendout_id' => $params['sendout_id']);
            $data[] = array('sender' => $params['receipients'], 'receipients' => $params['sender']
                , 'trigger_timestamp' => $before_2hrs, 'fixed_timestamp' => $appointment_time, 'typeID' => INTERVIEW_APP_REMINDER_2B, 'sendout_id' => $params['sendout_id']);
        }
        $this->datasave($data);
    }

    public function client_registration_process_save($params) {
        //pr($params);exit;
        $fixed_timestamp = $params['fixed_timestamp'];
        $before_2hrs = strtotime('+2 hours', $fixed_timestamp);
        $before_8hrs = strtotime('+8 hours', $fixed_timestamp);
        $before_24hrs = strtotime('+24 hours', $fixed_timestamp);
        $data[] = array('sender' => $params['super_or_sales_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $before_2hrs, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => CLIENT_ADMIN_REGISTRATION_2HRS);
        $data[] = array('sender' => $params['super_or_sales_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $before_8hrs, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => CLIENT_ADMIN_REGISTRATION_8HRS);
        $data[] = array('sender' => $params['super_or_sales_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $before_24hrs, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => CLIENT_ADMIN_REGISTRATION_24HRS);
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : get_current_time
     * Description     : get current time upto current minute
     * Created Date    : 01-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function get_current_time() {
        return strtotime(date('d-m-Y h:i a', time()));
    }

    /*     * **************************************************************************************
     * Function name   : stop_further_sendout_alert
     * Description     : Function for disable further alert related with sendout once candidate fixed appointment
     * Created Date    : 02-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function stop_further_sendout_alert($where_cond) {
        //  if (!empty($sendout_data)) {
        $query = $this->query()->update()->set(['status' => 0])
                ->where($where_cond)
                ->execute();
        //  }
    }

    /*     * **************************************************************************************
     * Function name   : send_client_admin_request_received
     * Description     : Client Registration Request Received
     * Created Date    : 03-11-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function send_client_admin_request_received($user_id = null, $email_verify_id = null) {
        $notify_infos = TableRegistry::get('NotificationType');
        $notify_info = $notify_infos->get_notification_type();
        $sale_rep_or_super_admin = TableRegistry::get('Users')->get_owner_info($user_id);
        $email_text[] = array(
            'to' => $sale_rep_or_super_admin[0]['email'],
            'subject' => $notify_info[CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED]['type'],
            'message' => $notify_info[CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED]['message_text']
        );
        $email_text = $this->Notification->email($email_text);
        if ($email_text['data'][0]['isSent']) {
            $this->query()->update()->set(['status' => 0])
                    ->where(['email_verify_id' => $email_verify_id])
                    ->execute();
            return json_encode(
                    [
                        'status' => 1,
                        'is_admin_verified' => 1,
                        'message' => "Email address is verified successfully!",
                        'data' => [
                            'user_id' => $user_id
                        ]
                    ]
            );
        }
        exit;
    }

    /*     * **************************************************************************************
     * Function name   : profile_completion_reminder_save
     * Description     : Save all details about Profile completion reminder
     * Created Date    : 08-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function profile_completion_reminder_save($params) {
        $current_time = $this->get_current_time();
        $second_time = strtotime('+24 hours', $current_time);
        $third_time = strtotime('+24 hours', $second_time);
        $data[] = array('sender' => $params['sender'], 'receipients' => $params['receipients']
            , 'trigger_timestamp' => $current_time, 'typeID' => PROFILE_COMPLETION_REMINDER_1, 'sendout_id' => $params['sendout_id']);
        $data[] = array('sender' => $params['receipients'], 'receipients' => $params['sender']
            , 'trigger_timestamp' => $second_time, 'typeID' => PROFILE_COMPLETION_REMINDER_2, 'sendout_id' => $params['sendout_id']);
        $data[] = array('sender' => $params['sender'], 'receipients' => $params['receipients']
            , 'trigger_timestamp' => $third_time, 'typeID' => PROFILE_COMPLETION_REMINDER_3, 'sendout_id' => $params['sendout_id']);
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : contractor_registration_process_save
     * Description     : Save all details about contractor registration reminder
     * Created Date    : 09-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function contractor_registration_process_save($params) {
        //pr($params);exit;
        $fixed_timestamp = $params['fixed_timestamp'];
        $after_2hrs = strtotime('+2 hours', $fixed_timestamp);
        $after_8hrs = strtotime('+8 hours', $after_2hrs);
        $after_24hrs = strtotime('+24 hours', $after_2hrs);
        $data[] = array('sender' => $params['super_or_sales_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $after_2hrs, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => CONTRACTOR_EMAIL_VALIDATION_REMINDER_1);
        $data[] = array('sender' => $params['super_or_sales_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $after_8hrs, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => CONTRACTOR_EMAIL_VALIDATION_REMINDER_2);
        $data[] = array('sender' => $params['super_or_sales_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $after_24hrs, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => CONTRACTOR_EMAIL_VALIDATION_REMINDER_3);
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : hiring_manager_registration_process_save
     * Description     : Save all details about contractor registration reminder
     * Created Date    : 09-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function hiring_manager_registration_process_save($params) {
        $fixed_timestamp = $params['fixed_timestamp'];
        $hmwelcome = strtotime('+1 minutes', $fixed_timestamp);
        $reminder_1 = strtotime('+24 hours', $fixed_timestamp);
        $reminder_2 = strtotime('+24 hours', $reminder_1);
        $follow_up_1 = strtotime('+24 hours', $reminder_2);
        $follow_up_2 = strtotime('+24 hours', $follow_up_1);
        $data[] = array('sender' => $params['company_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $hmwelcome, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => HM_WELCOME,'status' => 0);
        $data[] = array('sender' => $params['company_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $reminder_1, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => HM_WELCOME_REMINDER_1,'status' => 0);
        $data[] = array('sender' => $params['company_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['client_bullhorn_id']
            , 'trigger_timestamp' => $reminder_2, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => HM_WELCOME_REMINDER_2,'status' => 0);
        $data[] = array('sender' => $params['company_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['company_admin']
            , 'trigger_timestamp' => $follow_up_1, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => HM_FOLLOW_UP_1,'status' => 0);
        $data[] = array('sender' => $params['company_admin'], 'email_verify_id' => $params['email_verify_id'], 'receipients' => $params['company_admin']
            , 'trigger_timestamp' => $follow_up_2, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => HM_FOLLOW_UP_2,'status' => 0);
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : interview_follow_up
     * Description     : Save the notifications to follow up missed interview
     * Created Date    : 09-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function interview_follow_up($params) {
        $fixed_timestamp = $params['trigger_timestamp'];
        $fixed_timestamp = strtotime('-330 minutes', $fixed_timestamp); //GMT time based changes.
        $reminder_1 = strtotime('+4 hours', $fixed_timestamp);
        $reminder_2 = strtotime('+4 hours', $reminder_1);
        $unresponse_reminder_1 = strtotime('+24 hours', $fixed_timestamp);
        $unresponse_reminder_2 = strtotime('+24 hours', $unresponse_reminder_1);
        $salesrep_id = TableRegistry::get('Users')->get_sales_rep_id($params['company_id']);
        $data[] = array('sendout_id' => $params['sendout_id'], 'appointment_id' => $params['appointment_id'], 'receipients' => $params['receipients']
            , 'trigger_timestamp' => $reminder_1, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => INTERVIEW_FOLLOW_UP_1);
        $data[] = array('sendout_id' => $params['sendout_id'], 'appointment_id' => $params['appointment_id'], 'receipients' => $params['receipients']
            , 'trigger_timestamp' => $reminder_2, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => INTERVIEW_FOLLOW_UP_2);
        if (!empty($salesrep_id)) {
            $data[] = array('appointment_id' => $params['appointment_id'],'sendout_id' => $params['sendout_id'],'receipients' => $salesrep_id
                , 'trigger_timestamp' => $unresponse_reminder_1, 'typeID' => UNRESPONSIGN_INTERVIEW_1);
            $data[] = array('appointment_id' => $params['appointment_id'], 'sendout_id' => $params['sendout_id'],'receipients' => $salesrep_id
                , 'trigger_timestamp' => $unresponse_reminder_2, 'fixed_timestamp' => $fixed_timestamp, 'typeID' => UNRESPONSIGN_INTERVIEW_2);
        }
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * Function name   : candidate match update
     * Description     : Save the notifications to follow up missed interview
     * Created Date    : 10-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function candidate_match_update($params,$sendout_id = []) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        
        $data = [
            //'sender' => $params['sender'],
            'trigger_timestamp' => $accept_notifycation,
            'fixed_timestamp' => $accept_notifycation,
            'sendout_id' => $sendout_id[0]
        ];
        $final_data = [];
        foreach ($params as $param) {
            foreach ($param as $type) {
                $tmp_data = $data;
                $tmp_data['receipients'] = $type;
                $tmp_data['typeID'] = CONTRACTOR_SLATE_UPDATE_A;
                $final_data[] = $tmp_data;
            }
        }
        /* Block the mutiple notification send to hiring managers role*/
        foreach($final_data as $final_dat) {
           $slateChange[] = $final_dat['receipients'];
        }
        $this->slate_status_update($slateChange);  
        $this->datasave($final_data);
    }

    /*     * *************************************************************************************
     * input => $params = array(array(
     * receiver_HM => id of hiring manager
     * receiver_PC_SR => id of sales rep
     * receiver_company_admin => id of company admin
     * sender => id of sender
     * email_verify_id => email_verify_id
     * ))
     * Function name   : performance_rating_request
     * Description     : Save the notifications to performance rating request
     * Created Date    : 11-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function performance_rating_request($params) {
        $current_time = $params['dateEnd'];
        $current_time=$this->add_time_job_dateEnd($current_time);
        $current_time = strtotime('-330 minutes', $current_time); //GMT time based changes.
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        $company_id = TableRegistry::get('Users')->get_companyadmin_id($params['company_id']);
        $salesrep_id = TableRegistry::get('Users')->get_sales_rep_id($params['company_id']);
        $data = [
            'sender' => $params['placement_coordinator_id'],
            'fixed_timestamp' => $accept_notifycation,
        ];
        $final_data = [];
        $type_sender = [
            PERFORMANCE_RATING_REQUEST => $params['placement_coordinator_id'],
            PERFORMANCE_RATING_REMINDER_1 => $params['placement_coordinator_id'],
            PERFORMANCE_RATING_REMINDER_2 => $params['placement_coordinator_id'],
            PERFORMANCE_RATING_SR_FOLLOW_UP_1 => $salesrep_id,
            PERFORMANCE_RATING_SR_FOLLOW_UP_2 => $salesrep_id,
            PERFORMANCE_RATING_ADMIN_FOLLOW_UP => $company_id
        ];

        $add_hours = 0;
        foreach ($type_sender as $type => $sender) {
            if (!empty($sender)) {
                $tmp_data = $data;
                $tmp_data['receipients'] = $sender;
                $tmp_data['typeID'] = $type;
                $tmp_data['placement_id'] = $params['placement_id'];
                $tmp_data['sendout_id'] = $params['sendout_id'];
                $tmp_data['job_submission_id'] = $params['job_submission_id'];
                $tmp_data['trigger_timestamp'] = strtotime("+$add_hours hours", $accept_notifycation);
                $final_data[] = $tmp_data;
                $add_hours += 24;
            }
        }

        $this->datasave($final_data);
    }
    
    /**
     * Function   : add_time_job_dateend
     * Created by : Akilan
     * Date       : 03-12-2017
     */
    public function add_time_job_dateEnd($current_time){
        $current_time= date("d-m-Y",$current_time)." ".date('h:i a');
        return $current_time= strtotime('+333 minutes', strtotime($current_time));
    }

    /*     * **************************************************************************************
     * input => $params = array(
     * receiver_HM => id of hiring manager
     * receiver_PC_SR => id of sales rep
     * receiver_company_admin => id of company admin
     * sender => id of sender
     * email_verify_id => email_verify_id
     * )
     * Function name   : profile_update_notification
     * Description     : Triggers when a profile is being updated
     * Created Date    : 11-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function profile_update_notification($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        $data = [
            'sender' => $params['sender'],
            'email_verify_id' => $params['email_verify_id'],
            'fixed_timestamp' => $accept_notifycation,
            'typeID' => PROFILE_UPDATE_NOTIFICATION,
            'receipients' => $params['receiver'],
            'trigger_timestamp' => $accept_notifycation
        ];
        $this->datasave($data);
    }

    /*     * **************************************************************************************
     * input => $params = array(array(
     * receiver_HM => id of hiring manager
     * receiver_PC_SR => id of sales rep
     * sender => id of sender
     * ))
     * Function name   : review_contractors
     * Description     : Save the notifications when Contractors Ready for Review
     * Created Date    : 11-11-2016
     * Created By      : Sathyakrishnan
     * ************************************************************************************* */

    public function review_contractors($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("d-m-Y H:i:s", strtotime('+1 minutes', $current_time)));
        $data = [
            'sender' => $params['sender'],
            'fixed_timestamp' => $accept_notifycation,
            'sendout_id' => $params['sendout_id'],
        ];
        $final_data = [];
        $type_sender = [
            //REVIEW_CONTRACTOR => [$params['receiver_HM'], $accept_notifycation],
            CONTRACTOR_SLATE_READY => [$params['receiver_PC_SR'], $accept_notifycation],
            REVIEW_CONTRACTOR_REMINDER => [$params['receiver_HM'], strtotime("+2 hours", $accept_notifycation)],
            REVIEW_CONTRACTOR_REMINDER_SR => [$params['receiver_PC_SR'], strtotime("+6 hours", $accept_notifycation)],
            CONTRACTOR_SLATE_UPDATE_B => [$params['receiver_PC_SR'], strtotime("+6 hours", $accept_notifycation)]
        ];
        foreach ($type_sender as $type => $sender) {
            $tmp_data = $data;
            $tmp_data['receipients'] = $sender[0];
            $tmp_data['typeID'] = $type;
            $tmp_data['trigger_timestamp'] = $sender[1];
            $final_data[] = $tmp_data;
        }

        $this->datasave($final_data);
    }
    
    /**************************************************************************************
     * Function name   : reject_interview_timings
     * Description     : send notification to hm and sales rep when candidate choose 'i can't make option'
     * Created Date    : 20-03-2017
     * Created By      : Balasuresh A
     *************************************************************************************/
    public function reject_interview_timings($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("d-m-Y H:i:s", strtotime('+1 minutes', $current_time)));
        $data = [
            'sender' => $params['candidate_id'],
            'trigger_timestamp' => $accept_notifycation,
            'sendout_id' => $params['sendout_id'],
        ];
        $salesrep_id = TableRegistry::get('Users')->get_sales_rep_id($params['hiring_company']);
        $final_data = [];
        $type_sender = [
            ALTER_INTERVIEW_SCHEDULE_SR => [$salesrep_id,$accept_notifycation],
            ALTER_INTERVIEW_SCHEDULE_HM => [$params['interviewer_id'], $accept_notifycation]
        ];
        
        foreach ($type_sender as $type => $sender) {
            $tmp_data = $data;
            $tmp_data['receipients'] = $sender[0];
            $tmp_data['typeID'] = $type;
            $final_data[] = $tmp_data;
        }
        
        $this->datasave($final_data);
    }

    /*     * **************************************************************************************
     * input => $params = array(array(
     * sender_HM => id of hiring manager
     * receiver_contractor => id of contractor
     * ))
     * Function name   : application_received
     * Description     : Save the notifications when Contractors applied jobs
     * Created Date    : 21-11-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function application_received($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        $data = [
            'sender' => $params['sender_HM'],
            'fixed_timestamp' => $accept_notifycation,
            'sendout_id' => $params['sendout_id'],
        ];
        $final_data = [];
        $type_sender = [
            APPLICATION_RECEIVED_31 => [$params['receiver_contractor'], $accept_notifycation],
                //   APPLICATION_RECEIVED_39 => [$params['receiver_contractor'], $accept_notifycation],
        ];
        foreach ($type_sender as $type => $sender) {
            $tmp_data = $data;
            $tmp_data['receipients'] = $sender[0];
            $tmp_data['typeID'] = $type;
            $tmp_data['trigger_timestamp'] = $sender[1];
            $final_data[] = $tmp_data;
        }

        $this->datasave($final_data);
    }

    /*     * *************************************************************************************
     * Function name   : sendout_rejection
     * Description     : Save all details about contractor sendout rejection
     * Created Date    : 19-11-2016
     * Created By      : Balasuresh A
     * *********************************************************************************** */

    public function sendout_rejection($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        $data[] = [
            //  'sender' => $params['sender'],
            'typeID' => CONTRACTOR_SENDOUT_REJECTION,
            'receipients' => $params['receipients'],
            'trigger_timestamp' => $accept_notifycation,
            'sendout_id' => $params['sendout_id']
        ];
        $this->datasave($data);
    }

    /*     * *************************************************************************************
     * input => $params = array(array(
     * receiver_CON => id of contractor
     * receiver_HM => id of hiring manager
     * receiver_PC_SR => id of sales rep
     * sender => id of sender
     * ))
     * Function name   : final_assignment_confirmation
     * Description     : Save the notifications to assignment confirmation
     * Created Date    : 21-11-2016
     * Created By      : Balasuresh A
     * ************************************************************************************* */

    public function final_assignment_confirmation($params) {
        $userTable = TableRegistry::get('Users');
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));

        $data[] = array(
            'sendout_id' => $params['sendout_id'], 'placement_id' => $params['placement_id'],
            'job_submission_id' => $params['job_submission_id'], 'receipients' => $params['candidate_id'],
            'trigger_timestamp' => $accept_notifycation, 'typeID' => FINAL_ASSIGNEMENT_CONF_A, 'navigation' => NAVIGATION_OFFER);
        $data[] = array('sendout_id' => $params['sendout_id'], 'placement_id' => $params['placement_id'],
            'job_submission_id' => $params['job_submission_id'], 'receipients' => $params['placement_coordinator_id'],
            'trigger_timestamp' => $accept_notifycation, 'typeID' => FINAL_ASSIGNEMENT_CONF_B);
        $salesRepVal = $userTable->get_sales_rep_id($params['company_id']);

        if (!empty($salesRepVal)) {
            $data[] = array('sendout_id' => $params['sendout_id'], 'placement_id' => $params['placement_id'],
                'job_submission_id' => $params['job_submission_id'], 'receipients' => $salesRepVal,
                'trigger_timestamp' => $accept_notifycation, 'typeID' => FINAL_ASSIGNEMENT_CONF_C);
        }

        $this->datasave($data);
    }

    /*     * *************************************************************************************
     * Function name   : slate_update_notify
     * Description     : To notify the hiring manager when sales rep updates the slate page
     * Created Date    : 24-11-2016
     * Created By      : Balasuresh A
     * ************************************************************************************* */

    public function slate_update_notify($params) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));

        $data = [
            'sender' => $params['sender'],
            'fixed_timestamp' => $accept_notifycation,
            'sendout_id' => $params['sendout_id']
        ];

        $final_data = [];
        $type_sender = [
            REVIEW_CONTRACTOR => [$params['receiver_HM'], $accept_notifycation],
            REVIEW_CONTRACTOR_REMINDER => [$params['receiver_HM'], strtotime("+4 hours", $accept_notifycation)],
            REVIEW_CONTRACTOR_REMINDER_SR => [$params['receiver_PC_SR'], strtotime("+6 hours", $accept_notifycation)]
        ];
        foreach ($type_sender as $type => $sender) {
            if (!empty($sender[0])) {
                $tmp_data = $data;
                $tmp_data['receipients'] = $sender[0];
                $tmp_data['typeID'] = $type;
                $tmp_data['trigger_timestamp'] = $sender[1];
                $final_data[] = $tmp_data;
            }
        }

        if (!empty($final_data))
            $this->datasave($final_data);
    }
    
    /**************************************************************************************
     * Function name   : slate_status_update
     * Description     : To disable further notification when hiring manager views the candidate slate page
     * Created Date    : 30-01-2017
     * Created By      : Balasuresh A
     *************************************************************************************/
    public function slate_status_update($data = []) {
        $this->autoRender = false;
        $current_time = strtotime(date('d-m-Y H:i:s', time()));
        $after_24hrs = strtotime('-24 hours', $current_time);    
        $hmLastLogin = TableRegistry::get('User')->find('all')->select(['last_login','bullhorn_entity_id'])->where(['bullhorn_entity_id IN' => $data ,'last_login <>' => 0,'status' => 1 ,'last_login' < $after_24hrs]);
        if(!empty($hmLastLogin)) {
            $hmLastLogins = $hmLastLogin->toArray();
            foreach($hmLastLogins as $hmlogin) {
                $lastlogin_bull_horn[] = $hmlogin['bullhorn_entity_id'];
            }
            if(!empty($lastlogin_bull_horn)) {
            $slateNotifys = array(REVIEW_CONTRACTOR,REVIEW_CONTRACTOR_REMINDER,REVIEW_CONTRACTOR_REMINDER_SR,
            CONTRACTOR_SLATE_READY,CONTRACTOR_SLATE_UPDATE_A,CONTRACTOR_SLATE_UPDATE_B);
            
            foreach ($slateNotifys as $slateNotify) {
                foreach ($lastlogin_bull_horn as $lastlog) {
                    TableRegistry::get('Notifications')->query()->update()->set(['status' => 0,'appointment_id' => 0])
                            ->where(['typeID' => $slateNotify, 'receipients' => $lastlog])
                            ->execute();
                }
            }
            }
        }
        return;
    }
    
    /**************************************************************************************
     * Function name   : unsuccessful_applicants
     * Description     : To disable further notification when hiring manager views the candidate slate page
     * Created Date    : 30-01-2017
     * Created By      : Balasuresh A
     *************************************************************************************/
    public function unsuccessful_applicants($candidat_lists = []) {
        $current_time = $this->get_current_time();
        $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
        
        foreach($candidat_lists as $candidat_list ) {
            $tmp_data['sender'] = $candidat_list['candidate_id'];
            $tmp_data['receipients'] = $candidat_list['candidate_id'];
            $tmp_data['typeID'] = UNSUCCESSFUL_APPLICANTS;
            $tmp_data['sendout_id'] = $candidat_list['sendout_id'];
            $tmp_data['trigger_timestamp'] = $accept_notifycation;
            $tmp_data['navigation'] = NAVIGATION_JOB;
            $final_data[] = $tmp_data;
        }
        $this->datasave($final_data);
    }

}

?>

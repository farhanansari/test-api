<?php

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Mailer\Email;
use Twilio\Rest\Client;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Routing\Router;
//use Cake\ORM\ClassRegistry;

class NotificationComponent extends Component {

    /**
     * for handling multi curl request
     * @param type $data => curl data for send
     * @param type $options => if any extra data to send
     * @return type
     */
    function multiRequest($data, $options = array()) {
        // Firebase notification server key  AIzaSyBqrhrsLASzYRQv7gjvgwdwAbra_cvn9hI,AIzaSyBmAKBIZhQnJk2QAm_qjtBMA0c3b8Z3e9k
        $headers = array(
            'Authorization: key=AIzaSyBEGTlyAPRSwF1XffnmnSyJMnh8xIHBY6Q',
            'Content-Type: application/json'
        );
        $primarykey = [];
        // array of curl handles
        $curly = array();
        // data to be returned
        $result = array();

        // multi handle
        $mh = curl_multi_init();

        // loop through $data and create curl handles
        // then add them to the multi-handle
        if (!empty($data)) {
            foreach ($data as $id => $d) {

                $curly[$id] = curl_init();
                if (!isset($d['post_data']['data']['response']))
                    $d['post_data']['data'] = array("response" => "");
                $d['post_data']['data']['response']['image'] = Router::url('/', true).'img/notify_icon.png';
               
                $d['post_data'] = $this->change_text($d['post_data']);               
                $url = 'https://fcm.googleapis.com/fcm/send';
                curl_setopt($curly[$id], CURLOPT_URL, $url);
                curl_setopt($curly[$id], CURLOPT_HEADER, 0);
                curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, true);

                // post?
                if (is_array($d)) {
                    if (!empty($d['post_data'])) {
                        curl_setopt($curly[$id], CURLOPT_POST, true);
                        curl_setopt($curly[$id], CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($curly[$id], CURLOPT_POSTFIELDS, json_encode($d['post_data']));
                    }
                }

                // extra options?
                if (!empty($options)) {
                    curl_setopt_array($curly[$id], $options);
                }

                curl_multi_add_handle($mh, $curly[$id]);
                //collect the primary key ids
                $primarykey[] = $id;
            }

            // execute the handles
            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while ($running > 0);


            // get content and remove handles
            foreach ($curly as $id => $c) {
                $result[$id] = curl_multi_getcontent($c);
                curl_multi_remove_handle($mh, $c);
            }

            // all done
            curl_multi_close($mh);
        }
        if (!empty($primarykey)) {
            //update the notification status
            $this->update_status($primarykey, 'firebase_status');
        }
        return $result;
    }

    /**
     * Name         : Akilan
     * Description  : Did changes in notification text for 
     * Date         : 27-12-2016
     */
    public function change_text($data) {
        if (isset($data['data']['response']['typeID']) && $data['data']['response']['typeID'] == CONF_OF_ASSIGNMENT && isset($data['data']['response']['joborder_id'])) {

            $job_id = $data['data']['response']['joborder_id'];
            if (isset($_SESSION['job_' . $job_id]) && isset($_SESSION['job_' . $job_id]['joborder_title'])) {
                $title = $_SESSION['job_' . $job_id]['joborder_title'];
                $compny = $_SESSION['job_' . $job_id]['company'];
                $data['notification']['body'] = str_replace('[job]', $title, $data['notification']['body']);
                $data['notification']['body'] = str_replace('[company]', $compny, $data['notification']['body']);
            }
            } elseif(isset($data['data']['response']['typeID']) && $data['data']['response']['typeID'] == INTERVIEW_CONF_NOTIFICATION_A && isset($data['data']['response']['joborder_id'])) {
                $job_id = $data['data']['response']['joborder_id'];
                if (isset($_SESSION['job_' . $job_id]) && isset($_SESSION['job_' . $job_id]['dateBegin']) && isset($_SESSION['job_' . $job_id]['client_phone']) && isset($_SESSION['job_' . $job_id]['communication_type'])) {
                 $interview_start_date = date('D M d,Y H:i A', $_SESSION['job_' . $job_id]['dateBegin']);
                 
                 $findText = array('[date]','[type]','[phone]');
                 $replaceText   = array($interview_start_date,$_SESSION['job_' . $job_id]['communication_type'],$_SESSION['job_' . $job_id]['client_phone']);
                 $data['notification']['body'] = str_replace($findText, $replaceText, $data['notification']['body']);
                 //$data['notification']['body'] = str_replace('[date]', $interview_start_date, $data['notification']['body']);
                }
            }
        return $data;
    }

    /*
     * Action       : email
     * Description  : to send email notification
     * Request Input: emails in array format with to,subject and message
     */

    public function email($emails = []) {
        $email = new Email();
        $result = [];
        $primarykey = [];
        if (!empty($emails)) {
            foreach ($emails as $key => $em) {
                if (isset($em['to']) || isset($em['subject']) || isset($em['message']) || isset($em['mail_subject'])) {
                    $em['typeID'] = isset($em['typeID']) ? $em['typeID'] : "";
                    $notificationTypeIDs = array(/* validation remainder email notifications part */
                        CLIENT_ADMIN_REGISTRATION, CLIENT_ADMIN_REGISTRATION_2HRS, CLIENT_ADMIN_REGISTRATION_8HRS
                    );
                    $contracterValidation = array(/* validation remainder email notifications contractors part */
                        CONTRACTOR_EMAIL_VALIDATION_LINK, CONTRACTOR_EMAIL_VALIDATION_REMINDER_1, CONTRACTOR_EMAIL_VALIDATION_REMINDER_2, CONTRACTOR_EMAIL_VALIDATION_REMINDER_3,
                    );
                    $hmWelcome = array(/* validation remainder email notifications hiring managers part */
                        HM_WELCOME, HM_WELCOME_REMINDER_1, HM_WELCOME_REMINDER_2
                    );
                    $profileIncompletionIDs = array(/* in complete profile notifications part */
                        PROFILE_COMPLETION_REMINDER_1, PROFILE_COMPLETION_REMINDER_2, PROFILE_COMPLETION_REMINDER_3
                    );
                    $closoutAssignmentIDs = array(/* performance rating request part */
                        PERFORMANCE_RATING_REQUEST, PERFORMANCE_RATING_REMINDER_1, PERFORMANCE_RATING_REMINDER_2
                    );
                    $unsuccessfulApplications = array(CONTRACTOR_SENDOUT_REJECTION); /* unsuccessful applications */
                    $intervewRequests = array(/* Interview requests reminder */
                        INTERVIEW_REQUEST, INTERVIEW_REQUEST_REMINDER_1, INTERVIEW_REQUEST_REMINDER_2
                    );
                    $HM_interview_confirmation = array(
                        INTERVIEW_CONF_NOTIFICATION_A, INTERVIEW_APP_REMINDER_1A,
                        INTERVIEW_APP_REMINDER_2A
                    );
                    $contract_interview_confirmation = array(
                        INTERVIEW_CONF_NOTIFICATION_B, INTERVIEW_APP_REMINDER_1B,
                        INTERVIEW_APP_REMINDER_2B
                    );
                    $interview_follow_up = array(/* interview follow up reminder */
                        INTERVIEW_FOLLOW_UP_1, INTERVIEW_FOLLOW_UP_2
                    );
                    $hm_interview_unresponse = array(
                        UNRESPONSIGN_INTERVIEW_1, UNRESPONSIGN_INTERVIEW_2
                    );
                    $initialMatchANDClientAdminFollow = array(
                        CONTRACTOR_PRELIMINARY_MATCH, SALESREP_INITIAL_MATCH_LIST, CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED, CLIENT_ADMIN_REGISTRATION_24HRS
                    );
                    $jobApplications = array(
                        APPLICATION_RECEIVED_31, APPLICATION_RECEIVED_39,
                    );
                    $contractorReadyForReview = array(
                        REVIEW_CONTRACTOR, REVIEW_CONTRACTOR_REMINDER, REVIEW_CONTRACTOR_REMINDER_SR,CONTRACTOR_SLATE_READY
                    );
                    $performanFollowUP = array(
                        PERFORMANCE_RATING_SR_FOLLOW_UP_1, PERFORMANCE_RATING_SR_FOLLOW_UP_2, PERFORMANCE_RATING_ADMIN_FOLLOW_UP
                    );
                    $hmFollowUps = array(
                        HM_FOLLOW_UP_1, HM_FOLLOW_UP_2
                    );
                    $confirmationAssignment = array(/* confirmation of assignments */
                        CONF_OF_ASSIGNMENT, ASSIGNMENT_AWAITING_REMINDER_1, ASSIGNMENT_AWAITING_REMINDER_2, CONTRACTOR_UNRESPONSIVE_CONF_REQUEST
                    );
                    $finalAssignments = array(/* final assignments confirmation */
                        FINAL_ASSIGNEMENT_CONF_A, FINAL_ASSIGNEMENT_CONF_B, FINAL_ASSIGNEMENT_CONF_C
                    );
                    $interviewScheduleAlter = [ALTER_INTERVIEW_SCHEDULE_SR,ALTER_INTERVIEW_SCHEDULE_HM]; /* interview schedule alter */

                    try {
                        $email->to($em['to']);
                        $email->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                                ->subject($em['mail_subject']);

                        $em['joborder_title'] = !empty($em['joborder_title']) ? $em['joborder_title'] : '';
                        $em['placement_id'] = !empty($em['placement_id']) ? $em['placement_id'] : '';
                        $em['employer'] = !empty($em['employer']) ? $em['employer'] : '';
                        $em['location'] = !empty($em['location']) ? $em['location'] : '';
                        $em['startDate'] = !empty($em['startDate']) ? $em['startDate'] : '';
                        $em['durationWeeks'] = !empty($em['durationWeeks']) ? $em['durationWeeks'] : '';
                        $em['minHourlyRate'] = !empty($em['minHourlyRate']) ? $em['minHourlyRate'] : '';
                        $em['maxHourlyRate'] = !empty($em['maxHourlyRate']) ? $em['maxHourlyRate'] : '';
                        $em['joborder_id'] = !empty($em['joborder_id']) ? $em['joborder_id'] : '';
                        $em['contractor_full_name'] = !empty($em['contractor_full_name']) ? $em['contractor_full_name']['full_name'] : '';
                        $em['client_phone'] = !empty($em['client_phone']) ? $em['client_phone'] : '';
                        $em['bull_horn_id'] = !empty($em['bull_horn_id']) ? $em['bull_horn_id']['candidate_id'] : '';
                        $em['candidate_bid_value'] = !empty($em['candidate_bid_value']) ? $em['candidate_bid_value'] : '';
                        
                        if (in_array($em['typeID'], $notificationTypeIDs) && isset($em['user_id']) && isset($em['title'])) {

                            $token = $this->getToken($em['user_id']);
                            $verifyLink = Configure::read('server_address') . 'users/confirmation/' . $token;
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 0];
                            $data = $this->sendMail($email, $var);
                        } elseif (in_array($em['typeID'], $contracterValidation)) { /* Email validation for contractors template */
                            $token = $this->getToken($em['user_id']);
                            $verifyLink = Configure::read('marketing_server_address') . 'index.php?token=' . $token;
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 12];
                            $data = $this->sendMail($email, $var);
                        } elseif (in_array($em['typeID'], $hmWelcome)) { /* Email validation for hiring managers template */
                            $token = $this->getToken($em['user_id']);
                            $verifyLink = Configure::read('server_address') . 'users/confirmation/' . $token;
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 13];
                            $data = $this->sendMail($email, $var);
                        } elseif (in_array($em['typeID'], $profileIncompletionIDs)) { /* In complete profile notification email template */

                            $verifyLink = Configure::read('marketing_server_address') . 'index.php#log-in';
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 1];
                            $data = $this->sendMail($email, $var);
                        } elseif (in_array($em['typeID'], $closoutAssignmentIDs)) { /* performance rating request part */
                            $template = 'perf_rating_request';
                            $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                            if ($em['typeID'] == PERFORMANCE_RATING_REQUEST) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'intw_data' => $em, 'placementID' => $em['joborder_id'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 0];
                            } elseif ($em['typeID'] == PERFORMANCE_RATING_REMINDER_1) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'intw_data' => $em, 'placementID' => $em['joborder_id'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 1];
                            } else {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'intw_data' => $em, 'placementID' => $em['joborder_id'], 'message' => $em['message'], 'verifyLink' => $verifyLink, 'statusFlag' => 2];
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (in_array($em['typeID'], $unsuccessfulApplications)) { /* unsuccessful applications */

                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'joborder_id' => $em['joborder_id'], 'employer' => $em['employer'], 'location' => $em['location'], 'message' => $em['message'], 'statusFlag' => 3];
                            $data = $this->sendMail($email, $var);
                        } elseif (in_array($em['typeID'], $intervewRequests)) { /* Interview requests reminder */

                            if ($em['typeID'] == INTERVIEW_REQUEST) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'message' => $em['message'],'joborder_id'  => $em['joborder_id'], 'statusFlag' => 4];
                            } else {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'message' => $em['message'], 'joborder_id'  => $em['joborder_id'], 'statusFlag' => 5];
                            }
                            $data = $this->sendMail($email, $var);
                        } elseif (in_array($em['typeID'], $HM_interview_confirmation)) { /* Interview requests reminder */

                            $template = 'hm_data_interview_confirmation_request';
                            $jobLink = Configure::read('marketing_server_address') . '/job-position.php?job_ticket=' . $em['joborder_id'];
                            $candidateLink = Configure::read('server_address') . 'contractors/edit/' . $em['bull_horn_id'];
                            if ($em['typeID'] == INTERVIEW_CONF_NOTIFICATION_A) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobLink' => $jobLink, 'intw_data' => $em,
                                    'jobTitle' => $em['joborder_title'], 'message' => $em['message'],'joborder_id'  => $em['joborder_id'],'phone_no' => $em['user_phone'], 'statusFlag' => 1];
                            } else {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobLink' => $jobLink, 'intw_data' => $em,
                                    'jobTitle' => $em['joborder_title'], 'message' => $em['message'],'joborder_id'  => $em['joborder_id'], 'phone_no' => $em['user_phone'], 'statusFlag' => 2];
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (in_array($em['typeID'], $contract_interview_confirmation)) { /* Interview requests reminder */

                            $template = 'contractor_data_interview_confirmation_request';
                            $jobLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                            $candidateLink = Configure::read('server_address') . 'contractors/edit/' . $em['bull_horn_id'];

                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobLink' => $jobLink,
                                'candidateLink' => $candidateLink, 'intw_data' => $em,'jobId' => $em['joborder_id'],
                                'jobTitle' => $em['joborder_title'], 'message' => $em['message'], 'phone_no' => $em['user_phone'], 'statusFlag' => 4];
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (in_array($em['typeID'], $interview_follow_up)) { /* interview follow up reminder */
                            $template = 'interview_follow_up';
                            $verifyLink = Configure::read('server_address') . 'contractors/profile/' . $em['bull_horn_id'] . '/' . $em['sendout_id'];
                            if ($em['typeID'] == INTERVIEW_FOLLOW_UP_1) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'contractorName' => $em['contractor_full_name'], 'jobId' => $em['joborder_id'],'statusFlag' => 1];
                            } else {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'contractorName' => $em['contractor_full_name'],'jobId' => $em['joborder_id']];
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (in_array($em['typeID'], $hm_interview_unresponse)) { /* hiring managers interview unresponsive */

                            $template = 'hm_interview_unresponsive';
                            $jobLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];

                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobLink' => $jobLink, 'intw_data' => $em,
                                'jobTitle' => $em['joborder_title'],'placementID'=> $em['joborder_id'],'message' => $em['message']];
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (($em['typeID'] == CONTRACTOR_UNRESPONSIVE_INTERVIEW_REQUEST)) { /* Contractor Unresponsive to Interview Request */

                            $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'],'joborder_id'  => $em['joborder_id']];
                            $template = "con_unresponsive_interview_request";
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (in_array($em['typeID'], $performanFollowUP)) { /* performance rating follow up */
                            $template = "perf_rate_follow_up";
                            if (($em['typeID'] == PERFORMANCE_RATING_SR_FOLLOW_UP_1) || ($em['typeID'] == PERFORMANCE_RATING_SR_FOLLOW_UP_2)) {

                                $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'contractorName' => $em['contractor_full_name'],'rating_data' => $em ,'statusFlag' => 5];
                            } else if (($em['typeID'] == PERFORMANCE_RATING_ADMIN_FOLLOW_UP)) {
                                $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'contractorName' => $em['contractor_full_name'],'rating_data' => $em ];
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (($em['typeID'] == STATUS_UPDATE_REMINDER)) { /* Status Update Reminder */

                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'message' => $em['message']];
                            $template = "status_update_reminder";
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (in_array($em['typeID'], $confirmationAssignment)) { /* Assignment confirmation */
                            $template = "confirmation_assignment";
                            if (($em['typeID'] == CONF_OF_ASSIGNMENT) || ($em['typeID'] == ASSIGNMENT_AWAITING_REMINDER_2)) {

                                $verifyLink = '#';
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobID' => $em['joborder_id'],'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minPayRate' => $em['minPayRate'], 'maxPayRate' => $em['maxPayRate'],'candidate_bid_value' => $em['candidate_bid_value'], 'phone_no' => $em['client_phone'], 'statusFlag' => 6];
                            } else if (($em['typeID'] == ASSIGNMENT_AWAITING_REMINDER_1)) {

                                $verifyLink = '#';
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink,'jobID' => $em['joborder_id'], 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minPayRate' => $em['minPayRate'], 'maxPayRate' => $em['maxPayRate'], 'candidate_bid_value' => $em['candidate_bid_value'], 'phone_no' => $em['client_phone'], 'statusFlag' => 7];
                            } else if (($em['typeID'] == CONTRACTOR_UNRESPONSIVE_CONF_REQUEST)) {
                                $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobID' => $em['joborder_id'], 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minPayRate' => $em['minPayRate'], 'maxPayRate' => $em['maxPayRate'], 'candidate_bid_value' => $em['candidate_bid_value'], 'statusFlag' => 8];
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } elseif (($em['typeID'] == ASSIGNMENT_PENDING_APPLICANT_CONF)) { /* offer received */
                            $template = "pending_application_confirm";
                            $verifyLink = Configure::read('server_address') . 'contractors/profile/' . $em['bull_horn_id'] . '/' . $em['sendout_id'];
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'contractorName' => $em['contractor_full_name'], 'startDate' => $em['startDate'],'jobID' => $em['joborder_id']];
                            $data = $this->sendMail($email, $var, $template);
                        } else if (in_array($em['typeID'], $initialMatchANDClientAdminFollow)) {
                            if ($em['typeID'] == CONTRACTOR_PRELIMINARY_MATCH) {
                                $var = ['candidate' => $em['message']['candidate'], 'jobOrder' => $em['message']['joborder']];
                                $template = "contractor_preliminary_match";
                            } else if ($em['typeID'] == SALESREP_INITIAL_MATCH_LIST) {
                                $var = ['salesrep' => $em['message']['salesrep'], 'jobOrder' => $em['message']['joborder']];
                                $template = "salesres_initial_match";
                            } else if ($em['typeID'] == CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED) {
                                $var = ['firstName' => $em['message']['clientAdmin']['firstName'], 'lastName' => $em['message']['clientAdmin']['lastName']];
                                $template = "client_admin_registration_request_received";
                            } else if ($em['typeID'] == CLIENT_ADMIN_REGISTRATION_24HRS) {
                                $var = ['fullName' => $em['title']];
                                $template = "client_admin_not_validated_24hrs";
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } else if (in_array($em['typeID'], $jobApplications)) {
                            $var = ['title' => $em['title'], 'joborder_title' => $em['joborder_title'], 'joborder_id' => $em['joborder_id'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minHourlyRate' => $em['minPayRate'], 'maxHourlyRate' => $em['maxPayRate']];
                            $template = "application_received";
                            $data = $this->sendMail($email, $var, $template);
                        } else if (in_array($em['typeID'], $contractorReadyForReview)) {
                            $verifyLink = Configure::read('server_address') . 'contractors/slate/' . $em['joborder_id'];
                            $var = ['title' => $em['title'], 'placementID' => $em['placement_id'], 'jobTitle' => $em['joborder_title'],'joborder_id' => $em['joborder_id'],'link' => $verifyLink];
                            if (($em['typeID'] == REVIEW_CONTRACTOR) || ($em['typeID'] == CONTRACTOR_SLATE_READY)) {
                                $template = "contractors_ready_for_review";
                            } else if ($em['typeID'] == REVIEW_CONTRACTOR_REMINDER) {
                                $template = "hm_unresponsive_contractors_slate";
                            } else {
                                $template = "hm_unresponsive_contractors_slate_to_salesrep";
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } else if (in_array($em['typeID'], $hmFollowUps)) {
                            $var = ['title' => $em['title']];
                            $template = "hm_follow_ups";
                            $data = $this->sendMail($email, $var, $template);
                        } else if (($em['typeID'] == CONTRACTOR_SLATE_UPDATE_A)) {
                            $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message']];
                            $template = "candidate_slate_change";
                            $data = $this->sendMail($email, $var, $template);
                        } else if (in_array($em['typeID'], $finalAssignments)) { /* final assignments confirmations */
                            $template = "final_assignment_confirm";
                            /* to calculate the billrate value based on payrate*/
                            $billRate = (isset($em['candidate_bid_value']) && !empty($em['candidate_bid_value'])) ? $this->biddrate_calculate($em['candidate_bid_value']) : "";
                            if (($em['typeID'] == FINAL_ASSIGNEMENT_CONF_A)) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minPayRate' => $em['minPayRate'], 'maxPayRate' => $em['maxPayRate'],'assignmentData' => $em,'statusFlag' => 9];
                            } else if (($em['typeID'] == FINAL_ASSIGNEMENT_CONF_B)) {
                                $verifyLink = Configure::read('server_address') . 'positions/edit/' . $em['joborder_id'];
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minbillRate' => $em['minHourlyRate'], 'maxbillRate' => $em['maxHourlyRate'], 'contractorName' => $em['contractor_full_name'], 'assignmentData' => $em, 'billRate' => $billRate,'statusFlag' => 10];
                            } else if (($em['typeID'] == FINAL_ASSIGNEMENT_CONF_C)) {
                                $var = ['title' => $em['title'], 'subject' => $em['subject'], 'jobTitle' => $em['joborder_title'], 'placementID' => $em['placement_id'], 'message' => $em['message'], 'employer' => $em['employer'], 'location' => $em['location'], 'startDate' => $em['startDate'], 'durationWeeks' => $em['durationWeeks'], 'minbillRate' => $em['minHourlyRate'], 'maxbillRate' => $em['maxHourlyRate'], 'contractorName' => $em['contractor_full_name'],'assignmentData' => $em,'billRate' => $billRate, 'statusFlag' => 11];
                            }
                            $data = $this->sendMail($email, $var, $template);
                        } else if(in_array($em['typeID'], $interviewScheduleAlter)){
                            $template = "interview_schedule_alter_sr";
                            $verifyLink = Configure::read('server_address') . 'contractors/profile/' . $em['bull_horn_id'] . '/' . $em['sendout_id'];
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'verifyLink' => $verifyLink, 'schedule_data' => $em];
                            $data = $this->sendMail($email, $var, $template);
                        } else if($em['typeID'] == UNSUCCESSFUL_APPLICANTS) {
                            $template = "rejected_applicants";
                            $var = ['title' => $em['title'], 'subject' => $em['subject'], 'rejected_data' => $em];
                            $data = $this->sendMail($email, $var, $template);
                        } else {
                            $email->send($em['message']);
                        }

                        $result[$key] = [
                            'isSent' => 1
                        ];
                        $primarykey[] = $key;
                    } catch (Exception $e) {
                        $result[$key] = [
                            'isSent' => 0,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }
        }
        if (!empty($primarykey)) {
            // update the notification status
            $this->update_status($primarykey, 'email_status');
        }
        return ['data' => $result];
    }

    /*
     * Action       : sendMail
     * Description  : to send email notification
     */

    public function sendMail($email, $var, $template = null) {
        if ($template == null) {
            $template = 'validation_remainder';
        }
        $email->template($template, 'user')
                ->emailFormat('html')
                ->viewVars(['var' => $var])
                ->send();
        return;
    }

    /*
     * Action       : getToken
     * Description  : to generate the token for email vaidation
     */

    public function getToken($user_id) {
        $emailVerifyTable = TableRegistry::get('EmailVerification');
        $getToken = $emailVerifyTable->find()->select()->where(['user_id' => $user_id, 'is_verified' => 0, 'is_user_registr' => 1])->toArray();
        if (!empty($getToken)) {
            $token = $getToken[0]['token'];
        }
        return $token;
    }
    
    /*
     * Action       : biddrate_calculate
     * Description  : to calculate the billrate values based on payrate
     */
    public function biddrate_calculate($bidd_rate) {
        $bidd_rate_val = '';
        if(!empty($bidd_rate)) {
            if ($bidd_rate > 105) {
                    $highMarkup = $bidd_rate * (75 / 100);
                    $bidd_rate_val = round($bidd_rate + $highMarkup);
            } else if ($bidd_rate < 20) {
                    $highMarkup = $bidd_rate * (50 / 100);
                    $bidd_rate_val = round($bidd_rate + $highMarkup);
            } else {
                    $highMarkup = (0.002 * $bidd_rate) + 0.54;
                    $bidd_rate_val = round($bidd_rate + ($highMarkup * $bidd_rate));
            }
        }
        return $bidd_rate_val;
    }
    /*
     * Action       : sendsms
     * Description  : to send sms notification
     * Request Input: number in array format
     */
    public function sendsms($params,$data = array()) {

        require ROOT . DS . 'vendor' . DS . 'twilio' . DS . 'Twilio' . DS . 'autoload.php';
        // Your Account SID and Auth Token from twilio.com/console
        $sid = 'AC7a5a987564d143487072afa244c56c78';
        $token = '0047e5c27501c3df6275cd38063b890a';
        $client = new Client($sid, $token);
        // Use the client to do fun stuff like send text messages!
        $primarykey = $result = [];
        foreach ($params as $key => $number) {
            if ($key == "marketing") {
                //$message = "Congratulations! You have completed your first step in finding an ideal job, we will notify you soon with further instructions. PeopleCaddie team"; 
                //$message = "Thank you for your interest in PeopleCaddie. Our site is still under development. We will send you a notification as soon as our mobile app is available.";
                $message = "Thank you for your interest in Peoplecaddie. Tap this link to download the app:";
                $redirectURL = $this->shortLink();
                if(!empty($redirectURL)) {
                    $message = $message. " ". $redirectURL;
                }
            } else if (is_array($number)) {
                
                if($number['typeID'] == CONF_OF_ASSIGNMENT) { // to replace the job title and company name
                    $number = $this->sms_text($number,$data);
                } elseif($number['typeID'] == INTERVIEW_CONF_NOTIFICATION_A) { // to replace the interview start date
                    $number = $this->interview_date($number,$data);
                } else {
                    $number = $number;
                }
                unset($number['typeID']);
                $num = $number['number'];
                $message_data=$number; //for using sms process same marketing and webapp that's why we handled array and int method for send number
                unset($number['number']);
                $number = $num;
                $message = $message_data['message'];
            } else {
                $message = "Hey, Please check your status often in PeopleCaddie. Thanks!";
            }
            
            if ($number == '+917010998402' || $number=='+919944294961') {

          //  if ($number == '') {

                $sms = $client->messages->create(
                        // the number you'd like to send the message to
                        $number, array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => '+15102983619',
                    // the body of the text message you'd like to send
                    'body' => $message,
                        )
                );

                if (is_null($sms->errorCode))
                    $result[$number] = [
                        'isSent' => 1,
                        'status' => $sms->status
                    ];
                else
                    $result[$number] = [
                        'isSent' => 0,
                        'status' => $sms->status,
                        'error' => $sms->errorCode . ' : ' . $sms->errorMessage
                    ];

                $primarykey[] = $key;
            }
        }
        if (!empty($primarykey)) {
            // update the notification status
            $this->update_status($primarykey, 'twilio_status');
        }
        return $result;
    }
    
    /*
     * Action       : sms_text
     * Description  : to replace the job title and company name
     */
    public function sms_text($number,$data) {
        if (!empty($number) & !empty($data)) {
            $job_id = $data[0]['joborder_id'];
            if (isset($_SESSION['job_' . $job_id]) && isset($_SESSION['job_' . $job_id]['joborder_title'])) {
                $title = $_SESSION['job_' . $job_id]['joborder_title'];
                $compny = $_SESSION['job_' . $job_id]['company'];
                $number['message'] = str_replace('[job]', $title, $number['message']);
                $number['message'] = str_replace('[company]', $compny, $number['message']);
            }
        }
        return $number;
    }
    
    /*
     * Action       : interview_date
     * Description  : to add the interview_date in sms notifications
     */
    public function interview_date($number,$data) {
        if (!empty($number) & !empty($data)) {
            $job_id = $data[0]['joborder_id'];
            if (isset($_SESSION['job_' . $job_id]) && isset($_SESSION['job_' . $job_id]['dateBegin'])) {
                 $interview_start_date = date('D M d,Y H:i A', $_SESSION['job_' . $job_id]['dateBegin']);
                 $number['message'] = str_replace('[date]', $interview_start_date, $number['message']);
            }
            return $number;
        }
    }

    /*
     * Action       : update_status
     * Description  : to update status of notification
     * Request Input: ids => array of ids
     *                notification_status => one of (firebase_status, twilio_status, email_status)
     * Author: Sathyakrishnan
     */

    public function update_status($ids, $notification_status) {

        if (!empty($ids) && trim($notification_status) != "") { // to check whether the inputs are wrong
            if (TableRegistry::get('Notifications')->updateAll([$notification_status => 0], ['id in' => $ids])) {
                // status updated successfully
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    /*
     *  check for +1 US country code with the given contact mobile number
     */
    
    public function validateSmsCountyCode($mynumber = null){
            $findme = '+1';
            $pos = strpos($mynumber, $findme); // check for +1 with the given number
            if ($pos === false) {
                $mynumber = $findme . $mynumber; // Appends +1 if it doesnt exist
            }
            return $mynumber;
    }
    
    /* ************************************************************************************
     * Function name   : shortLink
     * Description     : to generate the shorten link, by passing the site base URL
     * Created Date    : 04-05-2017
     * Created By      : Balasuresh A
     * ************************************************************************************/
    public function shortLink() {
        $serverURL = SITE_BASEURL. 'users/k2/';
        if (!empty($serverURL)) {
            $longurl = $serverURL;
            $url = "http://api.bit.ly/shorten?version=2.0.1&longUrl=$longurl&login=balasureshfsp&apiKey=R_743b22dcfe094596bb63f53b9e50e541&format=json&history=1";
            $s = curl_init();
            curl_setopt($s, CURLOPT_URL, $url);
            curl_setopt($s, CURLOPT_HEADER, false);
            curl_setopt($s, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($s);
            curl_close($s);

            $obj = json_decode($result, true);
            return $redirectURL = !empty($obj["results"]["$longurl"]["shortUrl"]) ? $obj["results"]["$longurl"]["shortUrl"] : '';
        }
    }
    

}

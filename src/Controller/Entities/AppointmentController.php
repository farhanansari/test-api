<?php

/* * ************************************************************************************
 * Class name      : AppointmentController
 * Description     : Joborder CRUD process
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

class AppointmentController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : add
     * Description   : To fix the appointment to the candidate for the specific job or may be personal, interview, call, etc,.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 11-10-2016
     * URL           : /peoplecaddie-api/entities/appointment/?
     * Request input : candidateReference[id],jobOrder[id], communicationMethod, location, owner[id], subject, type
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function add($params = null) {
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $notifyTable = TableRegistry::get('Notifications');
        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment?BhRestToken=' . $_SESSION['BH']['restToken'];
        $response = array("result" => 1, "message" => "No record found");
        $sendoutTable = TableRegistry::get('Sendout');
        $secondary_response = array();
        if (isset($params['primary']['dateBegin']) && $params['primary']['dateEnd']) {
            $params['primary']['dateBegin'] = strtotime($params['primary']['dateBegin']);
            $params['primary']['dateEnd'] = strtotime($params['primary']['dateEnd']);
            $params['primary']['dateAdded'] = time();
        }
        if (isset($params['primary']) && !empty($params['primary'])) {
            $post_params = json_encode($params['primary']);
            $req_method = 'PUT';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            if (isset($response['changedEntityId'])) {
                $query = $sendoutTable->query()->update()->set(['appointment_id' => $response['changedEntityId'],
                            'application_progress' => INTERVIEW_REQUESTED,
                            'interviewer_id' => $params['action_user_id']])
                        ->where(['sendout_id' => $params['sendout_id']])
                        ->execute();
                /**
                 * For sending notification if candidate application was accepted
                 */
                $this->notification_data($params);
                if (isset($response['changedEntityId']) && isset($params['secondary']))
                    $secondary_response = $this->secondary_appointment_save($params['secondary'], $response['changedEntityId']);
            }
        }
        echo json_encode(array_merge($response, $secondary_response));
    }

    /*     * ***************************************************************************   
     * Action Name   : add
     * Description   : sending notification if candidate application was accepted.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 04-11-2016
     * Updated Date  : 04-11-2016     
     * **************************************************************************** */

    public function notification_data($params = null) {
        $notifyTable = TableRegistry::get('Notifications');
        $sendoutTable = TableRegistry::get('Sendout');
        $sendout_data = $sendoutTable->get_candidate_sendout($params['primary']['candidateReference']['id'], $params['sendout_id']);
        $send_data = array('type' => 'application_accept',
            'candidate_id' => $params['primary']['candidateReference']['id'],
            'sendout_id' => $params['sendout_id'],
            'hiring_manager_id' => $params['action_user_id'],
            'company_id' => isset($sendout_data[0]['hiring_company']) ? $sendout_data[0]['hiring_company'] : ""
        );
        $notifyTable->notification_data($send_data);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : view
     * Description   : To retrieve the appointement issued.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/appointment/?id=5
     * Request input : id => ID of an appointment.
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : update
     * Description   : To update the appointment issued.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/appointment/?id=5
     * Request input : id => ID of the appointment.
      Params to be updated such as location, subject, etc.
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function update($params = null) {
        $params = $this->request->data;
        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : delete
     * Description   : To delete the the appointment issued.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/appointment/?id=5&isDeleted=true
     * Request input : id => ID of the appointment.
      isDeleted = true
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function delete($params = null) {
        $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['isDeleted'] = 'true';
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : lists
     * Description   : To display appointment list
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 11-10-2016
     * Updated Date  : 11-10-2016
     * URL           : /peoplecaddie-api/entities/appointment/lists
     * Request input : id => ID of the sendout id.
      Fetch appointment id based on sendout id
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function lists($params = null, $type = null) {
        $this->autoRender = false;
        if (is_null($params))
            $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $sendoutTable = TableRegistry::get('Sendout');            
        $check_appointment=$sendoutTable->check_appointment_present($params['sendout_id']);         
        if(empty($check_appointment) && $type!= 'custom'){
            echo json_encode(array('status'=>2,'message'=>'You had scheduled interview already.'));
            return;
        }
        $selected_client_det = $sendoutTable->detail($params['sendout_id'], 'appointment_id');
        if (!empty($selected_client_det))
            $parent_appt_id = $selected_client_det[0]["appointment_id"];    
        $fields = 'id,appointmentUUID,candidateReference,childAppointments(*),clientContactReference,communicationMethod,'
                . 'dateAdded,dateBegin,dateEnd,dateLastModified,description,guests,isAllDay,isDeleted,isPrivate,jobOrder(clientContact(phone)),'
                . 'lead,location,migrateGUID,notificationMinutes,opportunity,owner,parentAppointment,placement,subject,type';      
        $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $parent_appt_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $data = [];
        $order = [];
        $interview_details = [];
        if (!empty($response['data'])) {
            ($type != 'custom') ? $data['status'] = 1 : "";
            //$data['data'][] = $this->appointment_result($response['data']);
            $order[] = [
                'id' => $response['data']['id'],
                'dateBegin' => $response['data']['dateBegin'],
                'dateEnd' => $response['data']['dateEnd'],
            ];
            foreach ($response['data']['childAppointments']['data'] as $res) {
                //$data['data'][] = $this->appointment_result($res);
                $order[] = [
                    'id' => $res['id'],
                    'dateBegin' => $res['dateBegin'],
                    'dateEnd' => $res['dateEnd'],
                ];
            }
            $data['interview_type'] =  $response['data']['communicationMethod'];
            $data['hm_phone_no'] =  $response['data']['jobOrder']['clientContact']['phone'];
            asort($order);
            $ordered = array_values($order);
            foreach ($ordered as $time) {
                $data['data'][] = $this->appointment_result($time);
            }
            foreach ($data['data'] as $key => $row) {
                $dates[$key] = $row['date'];
            }
            array_multisort($dates, SORT_ASC, $data['data']);
        } else {
            $data = [
                'status' => 0,
                'message' => $response
            ];
        }
        if ($type != 'custom')
            echo json_encode($data);
        else
            return $data;
    }

    public function appointment_result($result) {
        return array(
            'id' => $result['id'],
            'date' => date('m/d/Y', $result['dateBegin']),
            'start_time' => date("g:i A", $result['dateBegin']),
            'end_time' => date("g:i A", $result['dateEnd']),
        );
    }

    /*     * * * * * * * * * *  * * * *    
     * Function name : child_appointment
     * Description   : To store&update the candidate education data.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 16-09-2016
     * Updated Date  : 16-09-2016     
     * Request input :  
      array(
      "primary"=>array(
      array(
      "candidateReference" => array("id"=>45),
      "joborder"=>array("id"=>45),
      dateBegin=> date('Y-m-d').' 11:00:00 AM
      dateEnd=> date('Y-m-d').' 11:00:00 AM
      type => skype ,call
      )
      ).
      "secondary"=>array(

      array(
      "candidateReference" => array("id"=>45);
      dateBegin=> date('Y-m-d').' 11:00:00 AM
      dateEnd=> date('Y-m-d').' 11:00:00 AM
      type => skype ,call
      ),
      array(
      "candidateReference" => array("id"=>45);
      dateBegin=> date('Y-m-d').' 11:00:00 AM
      dateEnd=> date('Y-m-d').' 11:00:00 AM
      type => skype ,call
      )
      )
      )
      if certification field is empty it is education  data in response
      if education field is empty it is certification data in response
     */

    public function secondary_appointment_save($params, $parent_appointment_id = null) {
        if (!empty($params)) {
            $curl_data = array();
            $i = 0;
            foreach ($params as $temp) {
                $data = $temp;
                if (isset($data['dateBegin']) && $data['dateEnd']) {
                    $data['dateBegin'] = strtotime($data['dateBegin']);
                    $data['dateEnd'] = strtotime($data['dateEnd']);
                    $data['dateAdded'] = time();
                }
                $data['parentAppointment'] = array("id" => $parent_appointment_id);
                $curl_data[$i]['post_data'] = json_encode($data);
                if (isset($data['id'])) {
                    $curl_data[$i]['url'] = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $data['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
                    $curl_data[$i]['req_method'] = 'POST';
                } else {
                    $curl_data[$i]['url'] = $_SESSION['BH']['restURL'] . '/entity/Appointment?BhRestToken=' . $_SESSION['BH']['restToken'];
                    $curl_data[$i]['req_method'] = 'PUT';
                }
                $i++;
            }
            // pr($curl_data);exit;
            return $response = $this->BullhornCurl->multiRequest($curl_data);
        }
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : select
     * Description   : To select appointment list as candidate
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 12-10-2016
     * Updated Date  : 12-10-2016
     * URL           : /peoplecaddie-api/entities/appointment/selective
     * Request input : id => ID of the appointment.sendout_id  
     */

    public function selective() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $sendoutTable = TableRegistry::get('Sendout');
        $notifyTable = TableRegistry::get('Notifications');
//        $params = [ 'appointment_id' => 0, 'sendout_id' => 1937 ];
        $where_condition = array("sendout_id" => $params['sendout_id'], "job_submission_id IS" => NULL, "placement_id IS" => NULL);
        $notifyTable->stop_further_sendout_alert($where_condition);
        $query = $sendoutTable->query()->update()->set(['selected_appointment_id' => $params['appointment_id'],'application_progress' => INTERVIEW_CONFIRMED])
                ->where(['sendout_id' => $params['sendout_id']])
                ->execute();
        if ($params['appointment_id'] !== 0) {
            $fields = 'dateBegin';
            $appt_id = $params['appointment_id'];
            $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $appt_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
            $post_params = json_encode($params);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            //pr($response); exit;
            if (!empty($response['data'])) {
                $appointment = $response['data']['dateBegin'];
                $url = $_SESSION['BH']['restURL'] . '/entity/Sendout/' . $params['sendout_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=candidate,jobOrder(clientContact)';
                $post_params = json_encode([]);
                $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                if (!empty($response['data'])) {
                    $this->appointment_notification_data($response, $params, $appointment);
                }
            }
        }
        $data = $this->lists($params, 'custom');
        $this->update_appt($data, $params);
        if (($params['appointment_id'] == 0) && isset($params['sendout_id'])) { 
            /* To send notifications to HR ans Sales Rep*/
            $sendOut = $sendoutTable->find()->select(['candidate_id','hiring_company','interviewer_id'])->where(['sendout_id' => $params['sendout_id'], 'selected_appointment_id IS NOT NULL'])->hydrate(false)->first();
            if(!empty($sendOut)) {
                $sendOut['sendout_id'] = $params['sendout_id'];
                $notifyTable->reject_interview_timings($sendOut);
            }
            echo json_encode(array('result' => 1, 'message' => 'Thank You - Your PeopleCaddie Recruiter will be reaching out to you to directly to coordinate the interview'));
        } else {
            echo json_encode(array('result' => 1, 'message' => 'Your appointment has been scheduled'));
        }
    }

    /*     * ********************************************************************    
     * Function Name : appointment_notification_data
     * Description   : To arrange and send notification data
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 02-12-2016
     * Updated Date  : 02-12-2016
     * ********************************************************************* */

    public function appointment_notification_data($response = null, $params = null, $appointment = null) {
        $notifyTable = TableRegistry::get('Notifications');
        $sendout_data = TableRegistry::get('Sendout')->get_candidate_sendout($response['data']['candidate']['id'], $params['sendout_id']);
        if (!empty($sendout_data)) {
            $where_condition = array("appointment_id" => $sendout_data[0]['appointment_id']);
            $notifyTable->stop_further_sendout_alert($where_condition);
            $notify = [
                'sender' => $response['data']['jobOrder']['clientContact']['id'], // hiring manager
                'receipients' => $response['data']['candidate']['id'], // candidate/contractor
                'sendout_id' => $params['sendout_id'],
                'trigger_timestamp' => $appointment,
                'type' => 'interview_appointment_reminder_save'
            ];

            $notify1 = [
                'receipients' => $sendout_data[0]['interviewer_id'], // candidate/contractor
                'sendout_id' => $params['sendout_id'],
                'appointment_id' => $sendout_data[0]['appointment_id'],
                'company_id' => $sendout_data[0]['hiring_company'],
                'trigger_timestamp' => $appointment,
                'type' => 'interview_follow_up_start_contractor'
            ];
            $notifyTable->notification_data($notify);
            $notifyTable->notification_data($notify1);
        }
    }

    /**
     * For updating appointment based on accepted timing appointment id
     * @param type $datas
     * @param type $params
     * @return type
     */
    public function update_appt($datas, $params) {
        $i = 0;
        $curl_data = array();
        //for stopping further alert if candidate is selected    
        foreach ($datas['data'] as $data) {
            $post_data = array(
                'appointment' => array('id' => $data['id']),
                'attendee' => array('id' => $params['candidate_id'], '_subtype' => 'Candidate'),
                'acceptanceStatus' => ($data['id'] == $params['appointment_id']) ? 1 : 0
            );
            $curl_data[$i]['post_data'] = json_encode($post_data);
            $curl_data[$i]['url'] = $_SESSION['BH']['restURL'] . '/entity/AppointmentAttendee?BhRestToken=' . $_SESSION['BH']['restToken'];
            $curl_data[$i]['req_method'] = 'PUT';

            $i++;
        }
        $response = $this->BullhornCurl->multiRequest($curl_data);
        return $response;
    }
    
     /**********************************************************************    
     * Function Name : appointment_view
     * Description   : To fetch and send notification data
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 15-02-2017
     * Updated Date  : 
     * ********************************************************************/
    public function appointment_view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $data = [];
        $location = '';
        if(isset($params['sendout_id']) && !empty($params['sendout_id'])) {
            $sendoutTable = TableRegistry::get('Sendout')->find('all')->select(['selected_appointment_id', 'interviewer_id'])
                        ->where(['sendout_id' => $params['sendout_id']])->first();
            if(!empty($sendoutTable)) {
                $sendoutTable = $sendoutTable->toArray();
                    $appointment_id = $sendoutTable['selected_appointment_id'];
                    $interviewer_id = $sendoutTable['interviewer_id'];
                    $hiring_manager_data = $this->hiring_manager_data($interviewer_id);
                    
                    $fields = 'communicationMethod,dateBegin,candidateReference(firstName,lastName,phone,customTextBlock3),jobOrder(startDate,dateEnd,customText1,clientCorporation(address))';
                    $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $appointment_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                    $post_params = json_encode($params);
                    $req_method = 'GET';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                    if (isset($response['data']) && !empty($response['data'])) {
                        
                        $response['data']['job_title'] = $response['data']['jobOrder']['title'];
                        $response['data']['company_name'] = $response['data']['jobOrder']['clientCorporation']['name'];
                        $response['data']['company_address'] = $response['data']['jobOrder']['clientCorporation']['address'];
                        $response['data']['date'] = $response['data']['dateBegin'];
                        $response['data']['time'] = date("h:i A", $response['data']['dateBegin']);
                        $response['data']['interviewer_name'] = ucwords($hiring_manager_data);
                        
                        $communicationType = !empty($response['data']['communicationMethod']) ? $response['data']['communicationMethod'] : '';
                        
                        switch ($communicationType) {
                            case 'Phone interview':
                                
                                    $phoneNo = (isset($response['data']['candidateReference']['phone']) && !empty($response['data']['candidateReference']['phone'])) ? $response['data']['candidateReference']['phone'] : "";
                                    if(!empty($phoneNo)) {
                                        $candidatePhone = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $phoneNo);
                                        $message = 'Hiring manager to call you at '.$candidatePhone ;
                                    }
                                break;
                                
                            case 'Skype Interview':
                                
                                    $message = (isset($response['data']['candidateReference']['customTextBlock3']) && !empty($response['data']['candidateReference']['customTextBlock3'])) ? 'Hiring manager to call you at '.$response['data']['candidateReference']['customTextBlock3']  : "[please update your profile with SkypeID]";
                                break;
                            
                            case 'In person interview':
                                
                                if(isset($response['data']['jobOrder']['clientCorporation']['address']) && !empty($response['data']['jobOrder']['clientCorporation']['address'])) {
                                    $address = $response['data']['jobOrder']['clientCorporation']['address']['address2'];
                                    $city = $response['data']['jobOrder']['clientCorporation']['address']['city'];
                                    $state = $response['data']['jobOrder']['clientCorporation']['address']['state'];
                                    $countryName = $response['data']['jobOrder']['clientCorporation']['address']['countryName'];
                                    $zipCode = $response['data']['jobOrder']['clientCorporation']['address']['zip'];
                                    
                                    $location = $address.', '.$city.', '.$state.', '.trim($countryName).', '.$zipCode;
                                }
                                $message = 'Hiring manager to meet you at '. $location;
                                
                                break;
                            
                            default:
                                break;
                        }
                        
                        $response['data']['message'] = $message;
                        $response['data']['start_date'] = $response['data']['jobOrder']['startDate'];
                        $response['data']['end_date'] = $response['data']['jobOrder']['dateEnd'];
                        unset($response['data']['dateBegin']);
                        unset($response['data']['candidateReference']);
                        unset($response['data']['jobOrder']);
                        $data = [
                            'status' => 1,
                            'message' => $response
                        ];
                        echo json_encode($data);
                    } else {
                        echo json_encode(array('result' => 0, 'message' => 'Please try again later'));
                    }
                    
            }
        } else {
            echo json_encode(array('result' => 0, 'message' => 'Please send the valid send out ID'));
        }
        
    }
    
    /* *********************************************************************    
     * Function Name : hm_interview_confirm
     * Description   : To fetch the interview confirm notification data
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 04-04-2017
     * Updated Date  : 
     * ********************************************************************/
    public function hm_interview_data($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $data = [];
        $location = '';
        if(isset($params['sendout_id']) && !empty($params['sendout_id'])) {
            $sendoutTable = TableRegistry::get('Sendout')->find('all')->select(['selected_appointment_id', 'interviewer_id'])
                        ->where(['sendout_id' => $params['sendout_id']])->first();
            if(!empty($sendoutTable)) {
                $sendoutTable = $sendoutTable->toArray();
                    $appointment_id = $sendoutTable['selected_appointment_id'];
                    
                    $fields = 'communicationMethod,dateBegin,candidateReference(firstName,lastName,phone,customTextBlock3),jobOrder(startDate,dateEnd,customText1,clientCorporation(address))';
                    $url = $_SESSION['BH']['restURL'] . '/entity/Appointment/' . $appointment_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                    $post_params = json_encode($params);
                    $req_method = 'GET';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

                    if (isset($response['data']) && !empty($response['data'])) {
                        
                        $response['data']['job_title'] = $response['data']['jobOrder']['title'];
                        $response['data']['company_name'] = $response['data']['jobOrder']['clientCorporation']['name'];
                        $response['data']['company_address'] = $response['data']['jobOrder']['clientCorporation']['address'];
                        $response['data']['interview_date'] = $response['data']['dateBegin'];
                        $response['data']['interview_time'] = date("h:i A", $response['data']['dateBegin']);
                        $contractorName = $response['data']['candidateReference']['firstName'].' '. $response['data']['candidateReference']['lastName'];
                        $response['data']['contractor_name'] = ucwords($contractorName);
                        
                        $communicationType = !empty($response['data']['communicationMethod']) ? $response['data']['communicationMethod'] : '';
                        
                        switch ($communicationType) {
                            case 'Phone interview':
                                
                                    $phoneNo = (isset($response['data']['candidateReference']['phone']) && !empty($response['data']['candidateReference']['phone'])) ? $response['data']['candidateReference']['phone'] : "";
                                    if(!empty($phoneNo)) {
                                        $candidatePhone = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $phoneNo);
                                        $message = 'Please call the contractor at '.$candidatePhone ;
                                    }
                                break;
                                
                            case 'Skype Interview':
                                
                                    $message = (isset($response['data']['candidateReference']['customTextBlock3']) && !empty($response['data']['candidateReference']['customTextBlock3'])) ? 'Please call the contractor at '.$response['data']['candidateReference']['customTextBlock3']  : "[Contractor did not updated his/her profile with SkypeID]";
                                break;
                            
                            case 'In person interview':
                                
                                if(isset($response['data']['jobOrder']['clientCorporation']['address']) && !empty($response['data']['jobOrder']['clientCorporation']['address'])) {
                                    $address = $response['data']['jobOrder']['clientCorporation']['address']['address2'];
                                    $city = $response['data']['jobOrder']['clientCorporation']['address']['city'];
                                    $state = $response['data']['jobOrder']['clientCorporation']['address']['state'];
                                    $countryName = $response['data']['jobOrder']['clientCorporation']['address']['countryName'];
                                    $zipCode = $response['data']['jobOrder']['clientCorporation']['address']['zip'];
                                    
                                    $location = $address.', '.$city.', '.$state.', '.trim($countryName).', '.$zipCode;
                                }
                                $message = 'Please meet the contractor at '. $location;
                                
                                break;
                            
                            default:
                                break;
                        }
                        
                        $response['data']['message'] = $message;
                        $response['data']['start_date'] = $response['data']['jobOrder']['startDate'];
                        $response['data']['end_date'] = $response['data']['jobOrder']['dateEnd'];
                        unset($response['data']['dateBegin']);
                        unset($response['data']['candidateReference']);
                        unset($response['data']['jobOrder']);
                        $data = [
                            'status' => 1,
                            'message' => $response
                        ];
                        echo json_encode($data);
                    } else {
                        echo json_encode(array('result' => 0, 'message' => 'Please try again later'));
                    }
                    
            }
        } else {
            echo json_encode(array('result' => 0, 'message' => 'Please send the valid send out ID'));
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
        $hiring_mgr_data = '';
        if (!empty($intw_id_ar)) {
            $users = TableRegistry::get('Users')->find()->select(['firstName','lastName'])->where(['bullhorn_entity_id' => $intw_id_ar])->first();
            if(!empty($users)) {
                $users = $users->toArray();
                $hiring_mgr_data = $users['firstName']." ".$users['lastName'];
            }
        }
        return $hiring_mgr_data;
    }

}

<?php

/* * ************************************************************************************
 * Class name      : CandidateController
 * Description     : Candidate CRUD process
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
use Cake\Mailer\Email;

class CandidateController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
        $this->loadComponent('FileUpload');
        $this->loadComponent('Notification');
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : add
     * Description   : To create the candidate
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/general/candidate/?
     * Request input : employeeType, isEditable, owner[id], firstName, lastName, name, username, password, preferredContact, status
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $userTable = TableRegistry::get('User');
        $device_id = isset($params['device_id']) ? $params['device_id'] : "";
        $device_type = isset($params['device_type']) ? $params['device_type'] : "";
        $platform = isset($params['platform']) ? $params['platform'] : "";
        $params['travelLimit'] = isset($params['travelLimit']) ? $params['travelLimit'] : 1; // 1 is to make yes by default
        unset($params['device_id']); // unset before sending to bullhorn
        unset($params['device_type']); // unset before sending to bullhorn
        unset($params['platform']); // unset before sending to bullhorn
        if (!isset($params['address']['countryID'])) {
            $params['address']['countryID'] = 2378; // - None Specified - country option to set this initially. Note: bullhorn default is 1 for US
        }
        /* Social login registration start */
        if (isset($params['singupType']) && $params['singupType'] == 'LinkedIn') {
            unset($params['singupType']); // unset before sending to bullhorn
            $headshot = $params['headshot'];
            unset($params['headshot']); // unset before sending to bullhorn
            $getUsers = $userTable->find()->select()->where(['email' => $params['email']])->toArray();
            if (!empty($getUsers)) { // third party login access
                //$this->Auth->setUser($getUsers);
                $user_det = $userTable->get($getUsers[0]['id']);
                $params['device_id'] = $device_id;
                $params['device_type'] = $device_type;
                $params['headshot'] = $headshot;
                $user_det = $this->update_user_info($getUsers[0]['id'], $params);
                if ($user_det->isActive) {
                    $candidate_det = $this->candidate_detail_format($user_det);
                    echo json_encode($candidate_det);
                    exit;
                } else {
                    echo json_encode(array('result' => 0, 'error' => 'Unauthorized access!'));
                    exit;
                }
            } else { // third party signup
                $params['password'] = substr((uniqid(mt_rand(), true)), 0, 8); // maximum 30 chars allowed in bullhorn
                $post_params = json_encode($params);
                $req_method = 'PUT';
                $params['social'] = "LinkedIn";
                $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
                    $params['bullhorn_entity_id'] = $response['changedEntityId'];
                    $params['headshot'] = $headshot;
                    if ($device_id != "") {
                        $params['device_id'] = $device_id;
                    }
                    if ($device_type != "") {
                        $params['device_type'] = $device_type == "iOS" ? 1 : 2; // 1 =>iOS, 2 => Android
                    }
                    $access_token = $this->user_save($params, CANDIDATE_ROLE, 'save');
                    $response['access_token'] = $access_token;
                    $response['headshot'] = isset($params['headshot']) ? $params['headshot'] : "";
                    $response['bullhorn_entity_id'] = strval($response['changedEntityId']);
                    $response['result'] = 1;
                    $getUsers = $userTable->find()->select()->where(['bullhorn_entity_id' => $response['changedEntityId'], 'isActive' => 1])->toArray();
                    if (!empty($getUsers)) { // third party singup and login access
                        //$this->Auth->setUser($getUsers);
                        $user_det = $userTable->get($getUsers[0]['id']);
                        if ($user_det->isActive) {

                            $candidate_det = $this->candidate_detail_format($user_det);
                            echo json_encode($candidate_det);

                            exit;
                        } else {
                            echo json_encode(array('result' => 0, 'error' => 'Unauthorized access!'));
                            exit;
                        }
                    }
                    echo json_encode($response);
                    exit;
                } else {
                    echo json_encode(array('result' => 0, 'error' => 'Error : Sorry, We faced failure in contractor signup process', 'data' => $response));
                    exit;
                }
            }
        }
        /* end */
        /**
         * Seperate candidate education & certification data for storing as seperate entity
         */
        $user_id = $this->user_save($params, CANDIDATE_ROLE, 'validate');
        if (is_array($user_id) && !empty($user_id)) {
            $error_msg = $this->format_validation_message($user_id);
            $error_msg = str_replace("Please fix the following error(s):", "", $error_msg);
            echo json_encode(array('result' => 0, 'error' => $error_msg));
            exit;
        }
        list($params, $candidateEducation, $candidateCertification) = $this->seperate_education_detail($params);
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['changedEntityId']) && !empty($response['changedEntityId'])) {
            $params['bullhorn_entity_id'] = $response['changedEntityId'];
            $this->CandidateEducation($candidateEducation, $params['bullhorn_entity_id']);
            $this->CandidateEducation($candidateCertification, $params['bullhorn_entity_id']);
            $this->businessSectorCD($params, $params['bullhorn_entity_id'], 'PUT');
            if ($device_id != "") {
                $params['device_id'] = $device_id;
            }
            if ($device_type != "") {
                $params['device_type'] = $device_type == "iOS" ? 1 : 2; // 1 =>iOS, 2 => Android
            }
            if ($platform != "" && $platform == "PC-Marketing") {
                $params['device_type'] = 3; // PC-Marketing
            }
            if ($platform != "" && $platform == "PC-Web") {
                $params['device_type'] = 4; // PC-Web
            }
            $access_token = $this->user_save($params, CANDIDATE_ROLE, 'save');
            $response['access_token'] = $access_token;
            $response['headshot'] = "";
            $response['bullhorn_entity_id'] = strval($response['changedEntityId']);
            $response['result'] = 1;
            echo json_encode($response);
        } else {
            echo json_encode(array('result' => 0, 'error' => 'Sorry, We faced failure in contractor signup procss'));
        }
    }
    
    /* ****************************************************************************************
     * Function name   : send_sms
     * Description     : send sms with link
     * Created Date    : 05 -05-2017
     * Created By      : Balasuresh A
     * ************************************************************************************* */

    public function send_sms($phoneno) {
        $sms = [];
        
        if (!empty($phoneno)) {
            $mynumber = $this->Notification->validateSmsCountyCode($phoneno);
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
               return;
            } else {
               return;
            }
        }
    }
    
    /*     * *********************************************
     * funciton Name : candidate_detail_format
     * Description   : To retrieve the candidate
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 11-11-2016
     * ************************************************** */

    public function candidate_detail_format($user_det) {
        return array(
            'result' => 1,
            'username' => $user_det->username,
            'firstName' => $user_det->firstName,
            'lastName' => $user_det->lastName,
            'email' => $user_det->email,
            'access_token' => $user_det->access_token,
            'bullhorn_entity_id' => $user_det->bullhorn_entity_id,
            'role' => $user_det->role,
            'phone' => $user_det->phone,
            'user_role' => $user_det->role,
            'company_id' => $user_det->company_id,
            'user_id' => $user_det->id,
            'headshot' => $user_det->headshot
        );
    }

    /*     * * * * * * * * * *  * * * *
     * Action Name   : view
     * Description   : To retrieve the candidate
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           :/peoplecaddie-api/general/candidate/?id=52
     * Request input : id => ID of the candidate.
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function view($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $fields = 'firstName,lastName,customTextBlock1,address,email,customInt1,customInt2,'
                . 'phone,phone2,customFloat1,customFloat2,customTextBlock3,customTextBlock4,customText1,customText2,customText3,customText4,customText5,customText6,customText7,customText8,'
                . 'employmentPreference,hourlyRateLow,hourlyRate,namePrefix,occupation,businessSectors[100](*),'
                . 'travelLimit,educations[10](id,degree,graduationDate,school,certification,expirationDate,customInt1),educationDegree,primarySkills[100](id,name),fileAttachments(id,name),certifications,certificationList,skillSet,categories[100](id,name),category';
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data'])) {
            //$response = $this->getDesiredPositionTitle($response);
            if (count($response['data']['employmentPreference']) == 1 && isset($response['data']['employmentPreference'][0]) && strtolower($response['data']['employmentPreference'][0]) == 'permanent') { // Permanent
                $response['data']['employmentPreference'] = "";
            } else {
                $response['data']['employmentPreference'] = (!empty($response['data']['employmentPreference'])) ? implode(',', $response['data']['employmentPreference']) : "";
            }
            
//            if (!empty($response['data']['fileAttachments']['data'])) {
//                $fileIds = [];
//                foreach ($response['data']['fileAttachments']['data'] as $fileData) {
//                    $fileIds[] = $fileData['id'];
//                }
//                $getFiles = $this->getAttachments($params['id'], $fileIds);
//                $response['data']['fileAttachments']['files'] = $getFiles;
//            }
            
            if (!empty($response['data']['fileAttachments']['data'])) {
                $fileIds = [];
                $getFilesInfo = $this->getFiles($params['id']);
                foreach ($response['data']['fileAttachments']['data'] as $key => $fileData) {
                    $fileIds[] = $fileData['id'];
                    if(isset($getFilesInfo[$fileData['id']])) {
                        $response['data']['fileAttachments']['data'][$key]['filepath'] = $getFilesInfo[$fileData['id']];
                    }
                }
                $getFiles = $this->getAttachments($params['id'], $fileIds);
                $response['data']['fileAttachments']['files'] = $getFiles;
                foreach($response['data']['fileAttachments']['data'] as $key1 => $fileData1) {
                    foreach($getFiles as $key2 => $getFile) {
                        if($fileData1['id'] == $getFile['file_id']) {
                            $response['data']['fileAttachments']['data'][$key1]['name'] = $getFile['filename'];
                        }
                    }
                }
            }
        }
        list($response['data']['headshot'], $response['data']['rating']) = $this->getHeadshot($params['id']);
        if (isset($response['data']) && !empty($response['data']['primarySkills']['data'])) {
            foreach ($response['data']['primarySkills']['data'] as $skill) {
                $skills[] = $skill['name'];
            }
            $response['data']['customSkillSet'] = implode(',', $skills);
        }
        $response = $this->seperate_education_response($response);
        $response['data']['sendout'] = null;
        if (isset($params['sendout_id'])) {
            $response['data']['sendout'] = $this->getSendOut($params['sendout_id'], $params['id']);
        }
        if(isset($params['id']) && !empty($params['id'])) {
            $candidateRating = TableRegistry::get('Users')->find('all')->select(['rating'])->where(['bullhorn_entity_id ' => $params['id'],'rating IS NOT NULL'])->first();
            if(!empty($candidateRating)) {
                $candidateRating = $candidateRating->toArray();
            }
        }
        $response['data']['overall_rating'] = (!empty($candidateRating['rating']) && isset($candidateRating['rating'])) ? $candidateRating['rating'] : 0;
        if(isset($params['id']) && !empty($params['id'])) {
            $response['data']['performanceHistory'] = $this->performance_history($params['id']);
            $response['data']['referenceDetails'] = $this->reference_details($params['id']);
            $response['data']['failedtoShow'] = $this->failed_to_show($params['id']);
        }
        echo json_encode($response);
    }

    /*
     * *************************************************************************************
     * Function name   : performance_history
     * Description     : To fetch the candidate performance history via placement id
     * Created Date    : 15-02-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function performance_history($candidate_id) {
//        $this->autoRender = false;
//        $response['data'] = $candidatePlacement = [];
//        
//        $perfTable = TableRegistry::get('Performance');
//        //$candidatePerf = $perfTable->find('list',['keyField' => 'grade','valueField' => 'placement_id'])->where(['candidate_bullhorn_id' => $candidate_id, 'placement_id IS NOT NULL']);
//        $candidatePerformance = $perfTable->find('all')->select(['placement_id','grade'])->where(['candidate_bullhorn_id' => $candidate_id, 'placement_id IS NOT NULL']);
//        
//        if(!empty($candidatePerformance)) {
//            $candidatePerformance = $candidatePerformance->toArray();
//
//            foreach($candidatePerformance as $candidatePerf) {
//                $candidatePlacement[] = $candidatePerf->placement_id;
//            }
//            $candidatePlacementIDs = implode(',', $candidatePlacement);
//            $this->BullhornConnection->BHConnect();
//            $fields = 'id,jobOrder(title,startDate,dateEnd,clientCorporation)';
//            $url = $_SESSION['BH']['restURL'] . '/entity/Placement/' . $candidatePlacementIDs . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields='.$fields;
//            $post_params = json_encode([]);
//            $req_method = 'GET';
//            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//            if (isset($response['data'])) {
//                $response = $this->check_zero_index($response);
//                foreach ($response['data'] as $key => $val) {
//                    foreach ($candidatePerformance as $candidatePerform) {
//                        if ($candidatePerform->placement_id == $val['id']) {
//                            $response['data'][$key]['jobOrder']['grade'] = round($candidatePerform['grade'],1);
//                        }
//                    }
//                }
//                return $response['data'];
//            }
//        } else {
//                return $response['data'];
//        }

        $response['data'] = $candidatePlacement = $job_data = $data = [];
        $perfTable = TableRegistry::get('Performance');
        $referenceTable = TableRegistry::get('Reference');
        $placement_record = $perfTable->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'job_order_id'
                ])->where(['candidate_bullhorn_id' => $candidate_id])->toArray();
        $job_ordersIDs = array_unique(array_filter($placement_record));

        if (!empty($job_ordersIDs)) {
            $job_ordersIDStr = implode(",", $job_ordersIDs);
            $fields = 'id,title,clientCorporation(id,name,address),startDate,dateEnd';
            $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $job_ordersIDStr . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
            $post_params = json_encode([]);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $response = $this->check_zero_index($response);

            if (isset($response['data'])) {
                foreach ($response['data'] as $job_det) {
                    $job_data[$job_det['id']] = $job_det;
                }
            }
        }
        $placement_record_det = $perfTable->find('all')->select()->where(['candidate_bullhorn_id' => $candidate_id])->toArray();
        $i = 0;
        if (!empty($placement_record_det)) {
            foreach ($placement_record_det as $placement_record_sgl) {
                if (!is_null($placement_record_sgl['job_order_id']) && isset($job_data[$placement_record_sgl['job_order_id']])) {
                    $data[$i] = $job_data[$placement_record_sgl['job_order_id']];
                    $data[$i]['grade'] = $placement_record_sgl['grade'];
                } else {
                    $query = $referenceTable->findById($placement_record_sgl['reference_id']);
                    if (!empty($query)) {
                        $row = $query->first()->toArray();
                        $data[$i]['title'] = $row['title'];
                        $data[$i]['startDate'] = $data[$i]['endDate'] = "";
                        $data[$i]['clientCorporation'] = array('name' => $row['company']);
                        $data[$i]['grade'] = $placement_record_sgl['grade'];
                    }
                }
                $i++;
            }
        }

        return $data;
    }

    /*
     * *************************************************************************************
     * Function name   : reference_details
     * Description     : To fetch the candidate reference details by candidate bullhorn ID
     * Created Date    : 28-02-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function reference_details($candidate_id) {
        $this->autoRender = false;
        $candidateRef = [];

        $referenceTable = TableRegistry::get('Reference');
        if (!empty($candidate_id)) {
            $candidateRef = $referenceTable->find('all')->select(['id', 'name', 'company', 'title', 'email', 'phone'])->where(['candidate_id' => $candidate_id, 'isActive' => 1])->hydrate(false);
            if (!empty($candidateRef)) {
                $candidateRef = $candidateRef->toArray();
            }
        }
        return $candidateRef;
    }
    
    /*
     * *************************************************************************************
     * Function name   : failed_to_show
     * Description     : To fetch the candidate failed to show details by candidate bullhorn ID
     * Created Date    : 20-04-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */
    public function failed_to_show($candidate_id) {
        $sendoutTable = TableRegistry::get('Sendout');
        $failedDetails = [];
        if(!empty($candidate_id)) {
            $failedInfo = $sendoutTable->find('all')->select(['joborder_id'])->where(['candidate_id' => $candidate_id, 'failed_to_show IS NOT NULL']);
            if(!empty($failedInfo)) {
                $failedDetails = $failedInfo->hydrate(false)->toArray();
            }
        }
        return $failedDetails;
    }

    /*
     *  get candidate attachments in pc server
     */

    public function getAttachments($candidate_id = null, $fileIds = []) {
        $attachmentTable = TableRegistry::get('Attachment');
        $getFiles = $attachmentTable->find('all', ['order' => ['id' => 'DESC']])->select(['id', 'candidate_id', 'file_id', 'filename', 'filepath'])->where(['candidate_id' => $candidate_id, 'file_id IN' => $fileIds]);
        if (!empty($getFiles)) {
            return $getFiles->toArray();
        } else {
            return [];
        }
    }
    
    public function getFiles($candidate_id = null) {
        $attachmentTable = TableRegistry::get('Attachment');
        $getFiles = $attachmentTable->find('list',[
            'keyField' => 'file_id',
            'valueField' => 'filepath'
        ])->where(['candidate_id' => $candidate_id]);
        if (!empty($getFiles)) {
            return $getFiles->toArray();
        } else {
            return [];
        }
    }
    /*
     *  Get desired title with value
     */

    public function getDesiredPositionTitle($response) {
        $positionIds = !empty($response['data']['customText3']) ? explode(',', $response['data']['customText3']) : [];
        if (!empty($positionIds)) {
            $desiredTitile = $this->getSkills($positionIds);
            if (isset($desiredTitile['data'])) {
                $checkExistenceId = [];
                foreach ($desiredTitile['data'] as $key => $skills) {
                    foreach ($skills['skills']['data'] as $key2 => $skill) {
                        if (!in_array($skill['id'], $checkExistenceId)) {
                            $checkExistenceId[] = $skill['id'];
                            $response['data']['positionTitle']['data'][] = [
                                'value' => $skill['id'],
                                'label' => $skill['name'],
                            ];
                        }
                    }
                }
                unset($checkExistenceId);
            } else {
                $response['data']['positionTitle']['data'] = [];
            }
        } else {
            $response['data']['positionTitle']['data'] = [];
        }

        return $response;
    }

    /**
     * For seperate candidate education and certification detail
     */
    public function seperate_education_response($response) {
        $candidate_edu = array();
        $candidate_cfn = array();
        if (!empty($response['data']) && isset($response['data']['educations']) && $response['data']['educations']['total'] != 0) {
            $i = 0;
            foreach ($response['data']['educations']['data'] as $sgl) {
                if (!empty($sgl['certification'])) {
                    $candidate_cfn[] = $sgl;
                } else {
                    $candidate_edu[] = $sgl;
                }
                $i++;
            }
            if (!empty($candidate_edu)) {
                usort($candidate_edu, array($this, "ordering_edu_data"));
            }
            if (!empty($candidate_cfn)) {
                usort($candidate_cfn, array($this, "ordering_certify_data"));
            }
            $response['data']['candidateEducation'] = array_reverse($candidate_edu);
            $response['data']['candidateCertification'] = array_reverse($candidate_cfn);
        } else {
            $response['data']['candidateEducation'] = array();
            $response['data']['candidateCertification'] = array();
        }
        return $response;
    }

    /**
     * Function         : ordering_edu_data
     * Date             : 17-01-2017
     * Description      : For ordering the candidate education process based on added date.
     * @param type $a
     * @param type $b
     * @return type
     */
    public function ordering_edu_data($a, $b) {
        return strcasecmp($a["graduationDate"], $b["graduationDate"]);
    }

    /**
     * Function         : ordering_edu_data
     * Date             : 17-01-2017
     * Description      : For ordering the candidate certification process based on added date.
     * @param type $a
     * @param type $b
     * @return type
     */
    public function ordering_certify_data($a, $b) {
        return strcasecmp($a["expirationDate"], $b["expirationDate"]);
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : update
     * Description   : To update the candidate profile.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           :/peoplecaddie-api/general/candidate/?id=52
     * Request input : id => ID of the candidate.=>bullhorn entity id
      Params to be updated such as, firstName, lastName, name, username, password, preferredContact, status
     * Request method: POST
     * Responses:
      1. success:
     */

    public function update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;

        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        if (isset($params['id'])) {
            $status = $this->user_update($params['id'], $params, 'bullhorn_id');
            if (isset($params['updatedByUserId'])) {
                unset($params['updatedByUserId']);
            }
            if ($status != 1 && is_array($status)) {
                $error_msg = $this->format_validation_message($status);
                $error_msg = str_replace("Please fix the following error(s):", "", $error_msg);
                echo json_encode(array('result' => 0, 'error' => $error_msg));
                exit;
            }
        }
        if(isset($params['employmentPreference']) && !empty($params['employmentPreference'])) {
            $params['customTextBlock4'] = $params['employmentPreference'];
        }
        list($params, $candidateEducation, $candidateCertification) = $this->seperate_education_detail($params);
        list($params, $business_sector_detail) = $this->seperate_business_sector($params);
        $post_params = json_encode($params);
        $req_method = 'POST';

        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $this->CandidateEducation($candidateEducation, $params['id']);
        $this->CandidateEducation($candidateCertification, $params['id']);
        $this->businessSectorUpdate($business_sector_detail);
        $response['result'] = 1;
        echo json_encode($response);
    }
    
    /*
     * To remove the old skills list based on new category choosed for mobile applications.
     */
    public function removeSkills($contractor_id) {
        
        $skillList = $response = [];
        $this->BullhornConnection->BHConnect();
        $fields = 'primarySkills[100](id,name)';
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $contractor_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if(!empty($response) && isset($response['data'])) {
            $response['data'] = $this->check_zero_index($response['data']);
            foreach($response['data']['primarySkills']['data'] as $skillId) {
                    $skillList[] = $skillId['id'];
            }
            $skillListId = implode(',',$skillList);
            $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/'.$contractor_id.'/primarySkills/'.$skillListId.'?BhRestToken=' . $_SESSION['BH']['restToken'];
            $post_params = json_encode([]);
            $req_method = 'DELETE';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        }
        return $response;
    }

    /*     * * * * * * * * * *  * * * *
     * Function name : CandidateEducation
     * Description   : seperate candidate education and certification detail
     * Created by    : Akilan
     * Updated by    : Akilan     *
     */

    function seperate_education_detail($params, $candidate_id = null) {
        $candidateEducation = array();
        $candidateCertification = array();
        if (isset($params['candidateEducation'])) {
            $candidateEducation = $params['candidateEducation'];
            unset($params['candidateEducation']);
        }
        if (isset($params['candidateCertification'])) {
            $candidateCertification = $params['candidateCertification'];
            unset($params['candidateCertification']);
        }
        return array($params, $candidateEducation, $candidateCertification);
    }

    /*     * * * * * * * * * *  * * * *
     * Function name : CandidateEducation
     * Description   : To store&update the candidate education data.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 16-09-2016
     * Updated Date  : 16-09-2016
     * Request input :
      Array (
      [id]=>20 (optional if you want to update it)
      [graduationDate] => 08-09-2016
      [school] => Berkeley State University
      [degree] =>Ph.D in maths application
      )
      Array(

      [certification] => certified widget member,
      [school] => IIT,
      [expirationDate]=> 08-09-2016
      )
      if certification field is empty it is education  data in response
      if education field is empty it is certification data in response
     */

    function CandidateEducation($params, $candidate_id = null) {
        if (!empty($params)) {
            $curl_data = array();
            $i = 0;
            foreach ($params as $data) {
                $temp = $data;
                if (isset($temp['graduationDate']) && !empty($temp['graduationDate']))
                    $temp['graduationDate'] = strtotime($temp['graduationDate']);
                if (isset($temp['expirationDate']) && !empty($temp['expirationDate']))
                    $temp['expirationDate'] = strtotime($temp['expirationDate']);
                if (isset($data['id'])) {
                    $curl_data[$i]['url'] = $_SESSION['BH']['restURL'] . '/entity/CandidateEducation/' . $data['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
                    $curl_data[$i]['post_data'] = json_encode($temp);
                    $curl_data[$i]['req_method'] = 'POST';
                } else {
                    if (!is_null($candidate_id))
                        $temp['candidate'] = array('id' => $candidate_id);
                    $curl_data[$i]['url'] = $_SESSION['BH']['restURL'] . '/entity/CandidateEducation?BhRestToken=' . $_SESSION['BH']['restToken'];
                    $curl_data[$i]['post_data'] = json_encode($temp);
                    $curl_data[$i]['req_method'] = 'PUT';
                }
                $i++;
            }

            return $response = $this->BullhornCurl->multiRequest($curl_data);
        }
    }

    /*
     * to add education or certificate from marketing site
     * created by sathyakrishnan
     */

    public function add_education() {
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $b_id = $this->request->data['id'];
        $params = $this->request->data;
        unset($params['id']);
        $new_params[] = $params;
        $response = $this->CandidateEducation($new_params, $b_id);
        echo json_encode($response[0]);
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : delete
     * Description   : To create the candidate
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 24-08-2016
     * Updated Date  : 24-08-2016
     * URL           : /peoplecaddie-api/general/candidate/?id=52
     * Request input : id => ID of the candidate.
      isDeleted = true
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function delete() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        //for mentioned candidate is deleted one
        $params['status'] = $params['isActive'] = 0;
        $status = $this->user_update($params['id'], $params, 'bullhorn_id');
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : submissions
     * Description   : To get all submissions of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Balasuresh A
     * Created Date  : 07-09-2016
     * Updated Date  : 21-02-2017
     * URL           : general/candidate/submissions
     * Request input : id => ID of the candidate.
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function submissions($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $per_page = isset($params['per_page']) ? $params['per_page'] : 10;
        $page_start = (isset($params['page'])) ? ($params['page'] - 1) * $per_page : 0;
        $total = $page_count = '';
        $sendoutTable = TableRegistry::get('Sendout');
        // $selected_client_det = $sendoutTable->lists();
        $selected_client_det = $sendoutTable->submission_lists($params['id'], 'selected_for_client');
        $selected_application_status = $sendoutTable->submission_lists($params['id'], 'application_progress');
        $selected_application_dates = $sendoutTable->submission_lists($params['id'], 'updated');
        $send_out_id_ar = array_keys($selected_client_det);
        $send_out_id_ar = array_unique(array_keys($selected_client_det));
        $send_out_id_str = implode(",", $send_out_id_ar);
        $selected_for_client = isset($params['selected_for_client']) ? $params['selected_for_client'] : "all"; // 0 for pending, 1 for accepted, 2 for rejected, 3 for completed, 4 for offer rejected
//        $fields = 'submissions(id,jobOrder(id,clientCorporation),status),sendouts[10](id,jobOrder(id,clientCorporation))';
//        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $url = $_SESSION['BH']['restURL'] . '/query/Sendout?where=candidate.id+IN+(' . $params['id'] . ')+AND+id+IN+(' . $send_out_id_str . ')&count=200'
                . '&fields=id,jobOrder(id,clientCorporation,correlatedCustomInt1,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response1 = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if(!empty($response1) && isset($response1['count'])) {
            $total = $response1['count'];
        }
        
        $url_new = $_SESSION['BH']['restURL'] . '/query/Sendout?where=candidate.id+IN+(' . $params['id'] . ')+AND+id+IN+(' . $send_out_id_str . ')'
                . '&fields=id,jobOrder(id,clientCorporation,correlatedCustomInt1,title,isOpen,status,isDeleted)&BhRestToken=' . $_SESSION['BH']['restToken'] . '&start=' . $page_start . '&count=' . $per_page;
        
        $post_params_new = json_encode($params);
        $req_method_new = 'GET';
        $response = $this->BullhornCurl->curlFunction($url_new, $post_params_new, $req_method_new);
        $page_count = ceil($total / $per_page);
        $applicationTable = TableRegistry::get('ApplicationStatus')->find('list', ['keyField' => 'id', 'valueField' => 'status'])->toArray();
        if (isset($response['data']) && !empty($response['data'])) {
            $i = 0;
            foreach ($response['data'] as $res) {
                if (isset($selected_client_det[$res['id']])) {
                    $response['data'][$i]['jobOrder']['selected_for_client'] = $selected_client_det[$res['id']];
                    $response['data'][$i]['jobOrder']['application_process'] = $applicationTable[$selected_application_status[$res['id']]];
                    $response['data'][$i]['jobOrder']['application_date'] = strtotime($selected_application_dates[$res['id']]);
                }

                if (is_array($selected_for_client) && in_array($selected_client_det[$res['id']], $selected_for_client)) { // if array, array(2,4) for rejected status
                    //unset($response['data'][$i]); 
                } else if ($selected_for_client != 'all' && $selected_for_client != $selected_client_det[$res['id']]) {
                    unset($response['data'][$i]);
                }

                $i++;
            }
            $response['data'] = array_values($response['data']);
        }
        if (!empty($response['data'])) {
            echo json_encode([
                'status' => 1,
                'data' => $response['data'],
                'page_count' => $page_count,
                'hidden_company' => HIDDEN_COMPANY_TEXT //for showing hidden company text
            ]);
            exit;
        } else {
            echo json_encode([
                'status' => 0,
                'data' => $response1
            ]);
            exit;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : add_reference
     * Description     : To store the candidate reference details
     * Created Date    : 25-02-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function add_reference($params = null) {
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $params = $this->request->data;
        if (!empty($params)) {  
            $params['candidate']['id']= $params['bullhorn_id'];
            $params['referenceFirstName'] = $params['reference_name'];
            $params['companyName'] = $params['reference_company'];
            $params['referenceTitle'] = $params['reference_title'];
            $params['referenceEmail'] = $params['reference_email'];
            $params['referencePhone'] = $params['reference_phone'];
            /* removing the unnecessary index values */
            unset($params['bullhorn_id']);unset($params['reference_name']);unset($params['reference_company']);
            unset($params['reference_title']);unset($params['reference_email']);unset($params['reference_phone']);
            
            $url = $_SESSION['BH']['restURL'] . '/entity/CandidateReference?BhRestToken=' . $_SESSION['BH']['restToken'];
            $post_params = json_encode($params);
            $req_method = 'PUT';
            $responseArr = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            if(!empty($responseArr)) {
            $referenceTable = TableRegistry::get('Reference');
            $reference = $referenceTable->newEntity();
            $token = md5(uniqid(mt_rand(), true));
            $reference->token = $token;
            $reference->candidate_id = $params['candidate']['id'];
            $reference->name = $params['referenceFirstName'];
            $reference->company = $params['companyName'];
            $reference->title = $params['referenceTitle'];
            $reference->email = $params['referenceEmail'];
            $reference->phone = $params['referencePhone'];

            $referenceTable->patchEntity($reference, $params);
                if ($refSave = $referenceTable->save($reference)) {
                    $response['status'] = 1;
                    $response['changedEntityId'] = $refSave->id;
                    echo json_encode($response);
                    $candidateInfo = TableRegistry::get('Users')->find()->select(['firstName', 'lastName'])->where(['bullhorn_entity_id' => $params['candidate']['id']])->hydrate(false)->first();
                    $verifyLink = WEB_SERVER_ADDR . 'users/referencePerformance/' . $token;

                    $var = ['subject' => 'Performance Rating Request', 'referenceName' => $params['referenceFirstName'], 'company' => $params['companyName'], 'title' => $params['referenceTitle'], 'verifyLink' => $verifyLink, 'cand_firstname' => $candidateInfo['firstName'], 'cand_lastname' => $candidateInfo['lastName']];
                    $email = new Email();
                    $email->template('reference_email', 'user')
                            ->emailFormat('html')
                            ->viewVars(['var' => $var])
                            ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                            ->to($params['referenceEmail'])
                            ->subject('Performance Rating Request')
                            ->send();
                }
            } else {
                echo json_encode(
                    [
                        'status' => 0,
                        'message' => "There was error occured, while saving the reference details"
                    ]
                );
                exit;
            }
        }
    }

    /*
     * Action       : reference_confirmation
     * Description  : to verify the token passed to confirm the performance rating request
     * Request Input: token
     */

    public function reference_confirmation() {
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
        $referenceTable = TableRegistry::get('Reference');
        $getPerfStatus = $referenceTable->find()->select()->where(['token' => $params['token']]);
        if (!empty($getPerfStatus)) {
            $getPerfStatus = $getPerfStatus->toArray();
            if (($getPerfStatus[0]['status'] == 1) && ($getPerfStatus[0]['isActive'] == 1)) {
                echo json_encode(
                        [
                            'status' => 0,
                            'message' => "You already rated this contractor",
                            'data' => [
                                'user_id' => $getPerfStatus[0]['candidate_id']
                            ]
                        ]
                );
                exit;
            } else {
                $query = $referenceTable->query()->update()->set(['status' => 1])->where(['token' => $params['token']])->execute();
                $userTable = TableRegistry::get('Users')->get_email($getPerfStatus[0]['candidate_id'], 'bullhorn_entity_id');
                echo json_encode(
                        [
                            'status' => 1,
                            'is_requested' => 1,
                            'message' => "Performance rating requested",
                            'data' => [
                                'reference_id' => $getPerfStatus[0]['id'],
                                'candidate_id' => $getPerfStatus[0]['candidate_id'],
                                'candidate_name' => $userTable[0]['firstName']
                            ]
                        ]
                );
            }
        } else {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "Performance rating is not requested for this contractor"
                    ]
            );
            exit;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : reference_delete
     * Description     : To remove the reference details from the candidate profile page
     * Created Date    : 28-02-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function reference_delete() {
        $this->autoRender = false;
        $params = $this->request->data;
        if (isset($params['candidate_id']) && isset($params['referenceId'])) {
            $referenceTable = TableRegistry::get('Reference');

            $query = $referenceTable->query()->update()->set(['isActive' => 0])->where(['id' => $params['referenceId'], 'candidate_id' => $params['candidate_id']])->execute();
            if ($query) {
                echo json_encode([
                    'status' => 1,
                    'message' => 'Deleted successfully!',
                    'result' => ['referenceId' => $params['referenceId']]
                ]);
                exit;
            }
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate ID and reference id are required']);
            exit;
        }
    }

    /* public function submissions($params = null) {
      $this->autoRender = false;
      $params = $this->request->data;
      $this->BullhornConnection->BHConnect();
      $fields = 'submissions(id,jobOrder(id,clientCorporation),status),sendouts[10](id,jobOrder(id,clientCorporation))';
      $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
      $post_params = json_encode($params);
      $req_method = 'GET';
      $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
      echo json_encode($response);
      } */

    /*     * * * * * * * * *  * * * *
     * Action Name   : file_upload
     * Description   : To upload resume of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 13-09-2016
     * Updated Date  : 29-09-2016
     * URL           : general/candidate/file_upload
     * Request input : id,externalID,fileContent,fileType,name
     * Request method: PUT
     */

    public function fileUpload() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();

        //echo "<pre>";print_r($params);echo "</pre>"; exit;
        $attachmentTable = TableRegistry::get('Attachment');
        $target_dir = WWW_ROOT . "uploads" . DS . "peoplecaddie" . DS . "files" . DS;
        $uploadedFileIds = [];
        $fileContent = [];
        $platform = isset($params['platform']) ? $params['platform'] : "";
        //   echo json_encode(['params'=>$params,'filess'=>$_FILES]); exit;
        if ($platform == "" && isset($params['fileContent'])) {
            $fileContent['fileContent']['file'] = $params['fileContent']['file'];
        } else if ($platform == "iOS" || $platform == "Android") {
            $fileCount = isset($params['fileCount']) ? $params['fileCount'] : 0;
            for ($count = 0; $count < $fileCount; $count++) {
                $fileContent['fileContent']['file'][] = $_FILES['fileContent' . $count];
            }
        }

        //if(isset($params['fileContent']['file']['tmp_name'])){

        $nFiles = count($fileContent['fileContent']['file']);
        $limit = 5;
        $this->canUploadFiles($limit, $nFiles, $params['id']); // check for file upload limit
        $n = 0;
        foreach ($fileContent['fileContent']['file'] as $file_loop) {
            $fileContent['fileContent']['file'][$n] = $file_loop;

//        for ($n = 0; $n < $nFiles; $n++) {

            $params['fileContent'] = base64_encode(file_get_contents($fileContent['fileContent']['file'][$n]['tmp_name']));
            $params['contentType'] = $fileContent['fileContent']['file'][$n]['type'];
            $params['name'] = $fileContent['fileContent']['file'][$n]['name'];
            $params['fileType'] = 'SAMPLE'; // always use SAMPLE

            $url = $_SESSION['BH']['restURL'] . '/file/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $post_params = json_encode($params);
            $req_method = 'PUT';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

            $timestamp = time();
            $target_file = $target_dir . $params['id'] . "_" . $timestamp . "_" . basename($fileContent['fileContent']['file'][$n]['name']);
            if (move_uploaded_file($fileContent['fileContent']['file'][$n]['tmp_name'], $target_file) || $this->FileUpload->upload($fileContent['fileContent']['file'][$n]['tmp_name'], $target_file)) {
                $attachment = $attachmentTable->newEntity();
                $pcFiles = [
                    'candidate_id' => $params['id'],
                    'file_id' => (isset($response['fileId']) && !is_null($response['fileId'])) ? $response['fileId'] : 0,
                    'filename' => $fileContent['fileContent']['file'][$n]['name'],
                    'filepath' => "uploads/peoplecaddie/files/" . $params['id'] . "_" . $timestamp . "_" . basename($fileContent['fileContent']['file'][$n]['name'])
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
            $n++;
        }
//        }
        // }
        echo json_encode($uploadedFileIds);
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : file_upload1
     * Description   : To upload resume of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 13-09-2016
     * Updated Date  : 29-09-2016
     * URL           : general/candidate/file_upload
     * Request input : id,externalID,fileContent,fileType,name
     * Request method: PUT
     */

    /*  public function fileUpload1() {
      $this->autoRender = false;
      $params = $this->request->data;
      $this->BullhornConnection->BHConnect();
      //echo "<pre>";print_r($params);echo "</pre>"; exit;
      $fileContent = [];
      $platform = isset($params['platform']) ? $params['platform'] : "";
      //   echo json_encode(['params'=>$params,'filess'=>$_FILES]); exit;
      if ($platform == "" && isset($params['fileContent'])) {
      $fileContent['fileContent']['file'] = $params['fileContent']['file'];
      } else if ($platform == "iOS" || $platform == "Android") {
      $fileCount = isset($params['fileCount']) ? $params['fileCount'] : 0;
      for ($count = 0; $count < $fileCount; $count++) {
      $fileContent['fileContent']['file'][] = $_FILES['fileContent' . $count];
      }
      }

      $nFiles = count($fileContent['fileContent']['file']);
      $limit = 5;
      $this->canUploadFiles($limit,$nFiles,$params['id']); // check for file upload limit
      $n = 0;
      foreach ($fileContent['fileContent']['file'] as $file_loop) {
      $fileContent['fileContent']['file'][$n] = $file_loop;

      $params['fileContent'] = base64_encode(file_get_contents($fileContent['fileContent']['file'][$n]['tmp_name']));
      $params['contentType'] = $fileContent['fileContent']['file'][$n]['type'];
      $params['name'] = $fileContent['fileContent']['file'][$n]['name'];
      $params['fileType'] = 'SAMPLE'; // always use SAMPLE
      $params['externalID'] = isset($params['externalID'])?$params['externalID']:'portpolio';
      $multiData[] = [
      'url' => $_SESSION['BH']['restURL'] . '/file/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'],
      'post_data' => json_encode($params),
      'req_method' => 'PUT'
      ];
      $n++;
      }
      $responseMulti = $this->BullhornCurl->multiRequest($multiData,[],array("Content-Type:multipart/form-data"));
      foreach($responseMulti as $key => $data){
      $responseMulti[$key] = json_decode($data,true);
      }
      $result = $this->file_upload_local($params['id'], $fileContent, $responseMulti);
      echo json_encode($result);
      }



      public function file_upload_local($candidate_id,$fileContent,$response){
      $attachmentTable = TableRegistry::get('Attachment');
      $target_dir = WWW_ROOT . "uploads" . DS . "peoplecaddie" . DS . "files" . DS;
      $uploadedFileIds = [];
      $timestamp = time();
      $n = 0;
      foreach ($fileContent['fileContent']['file'] as $file_loop) {
      $fileContent['fileContent']['file'][$n] = $file_loop;

      $target_file = $target_dir . $candidate_id . "_" . $timestamp . "_" . basename($fileContent['fileContent']['file'][$n]['name']);
      if (move_uploaded_file($fileContent['fileContent']['file'][$n]['tmp_name'], $target_file) || $this->FileUpload->upload($fileContent['fileContent']['file'][$n]['tmp_name'], $target_file)) {
      $attachment = $attachmentTable->newEntity();
      $pcFiles = [
      'candidate_id' => $candidate_id,
      'file_id' => (isset($response[$n]['fileId']) && !is_null($response[$n]['fileId']))?$response[$n]['fileId']:0,
      'filename' => $fileContent['fileContent']['file'][$n]['name'],
      'filepath' => "uploads/peoplecaddie/files/" . $candidate_id . "_" . $timestamp . "_" . basename($fileContent['fileContent']['file'][$n]['name'])
      ];
      $attachmentTable->patchEntity($attachment, $pcFiles);

      if ($result = $attachmentTable->save($attachment)) {

      $uploadedFileIds['result'][] = [
      'fileId' => (isset($response[$n]['fileId']) && !is_null($response[$n]['fileId']))?$response[$n]['fileId']:0,
      'status' => (isset($response[$n]['fileId']) && !is_null($response[$n]['fileId']))?1:0,
      'pcData' => [
      'id' => $result->id,
      'filename' => $result->filename,
      'filepath' => $result->filepath
      ]
      ];
      }
      } else {
      $uploadedFileIds['result'][] = [
      'fileId' => (isset($response[$n]['fileId']) && !is_null($response[$n]['fileId']))?$response[$n]['fileId']:0,
      'status' => 0
      ];
      }
      $n++;
      }

      return $uploadedFileIds;
      }
     */


    /*     * * * * * * * * *  * * * *
     * Action Name   : file_download
     * Description   : To download resume of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 14-09-2016
     * Updated Date  : 29-09-2016
     * URL           : general/candidate/file_download
     * Request input : candidate_id
     * Request method: GET
     */

    public function file_download() {
        $this->autoRender = false;
        $params = $this->request->data;
        if (isset($params['candidate_id'])) {
            $candidate_id = $params['candidate_id'];
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id is required']);
            exit;
        }
        $attachmentTable = TableRegistry::get('Attachment');
        $getFiles = $attachmentTable->find('all', ['order' => ['id' => 'DESC']])->select(['id', 'candidate_id', 'file_id', 'filename', 'filepath'])->where(['candidate_id' => $candidate_id, 'file_id !=' => 0])->limit(5);
        if (!empty($getFiles)) {
            $getFiles1 = $getFiles->toArray();
            echo json_encode([
                'status' => 1,
                'message' => 'Success!',
                'result' => $getFiles1
            ]);
            exit;
        } else {
            echo json_encode(['status' => 0, 'message' => 'No file uploaded for this candidate']);
            exit;
        }
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : file_delete
     * Description   : To delete resume of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 14-09-2016
     * Updated Date  : 29-09-2016
     * URL           : general/candidate/file_delete
     * Request input : candidate_id, fileId[]
     * Request method: DELETE
     */

    public function file_delete() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        if (isset($params['candidate_id']) && isset($params['fileId'])) {
            $candidate_id = $params['candidate_id'];
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id and file id are required']);
            exit;
        }
        $url = $_SESSION['BH']['restURL'] . '/file/Candidate/' . $candidate_id . '/' . $params['fileId'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode($params);
        $req_method = 'DELETE';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $attachmentTable = TableRegistry::get('Attachment');
        $getFile = $attachmentTable->find()->select(['id', 'filepath'])->where(['candidate_id' => $candidate_id, 'file_id' => $params['fileId']])->toArray();
        //echo "<pre>";print_r($getFile); echo "</pre>";
        if (!empty($getFile)) {
            unlink(WWW_ROOT . $getFile[0]['filepath']);
            $attachmentTable->delete($attachmentTable->get($getFile[0]['id']));
            echo json_encode([
                'status' => 1,
                'message' => 'Deleted successfully!',
                'result' => ['fileId' => isset($response['fileId']) ? $response['fileId'] : null]
            ]);
            exit;
        } else {
            echo json_encode(['status' => 0, 'result' => $response]);
            exit;
        }
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : headshot_upload
     * Description   : To upload headshot of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 16-09-2016
     * Updated Date  : 29-09-2016
     * URL           : general/candidate/headshot_upload
     * Request input : candidate_id,fileContent[headshot]
     * Request method: PUT
     */

    public function headshot_upload() {
        $this->autoRender = false;
        $params = $this->request->data;
        //echo "<pre>";print_r($params);echo "</pre>";
        $userTable = TableRegistry::get('Users');
        $target_dir = WWW_ROOT . "uploads" . DS . "peoplecaddie" . DS . "headshots" . DS;
        $timestamp = time();
        if (isset($params['candidate_id'])) {
            $candidate_id = $params['candidate_id'];
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id is required']);
            exit;
        }

        $user_det = $userTable->find()->select(['id', 'headshot'])->where(['bullhorn_entity_id' => $candidate_id])->toArray();
        if (!empty($user_det)) {
            $user_id = $user_det[0]["id"];
            $user_headshot = $user_det[0]["headshot"];
        } else {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No candidate available!"
                    ]
            );
            exit;
        }

        $platform = isset($params['platform']) ? $params['platform'] : "";

        if ($platform == "" && isset($params['fileContent'])) {
            $fileContent['fileContent']['headshot'] = $params['fileContent']['headshot'];
        } else if ($platform == "iOS" || $platform == "Android") {

            $fileContent['fileContent']['headshot'] = $_FILES['fileContent'];
        }

        if (isset($fileContent['fileContent']['headshot']['tmp_name'])) {
            $target_file = $target_dir . $candidate_id . "_" . $timestamp . "_" . basename($fileContent['fileContent']['headshot']['name']);
            if (move_uploaded_file($fileContent['fileContent']['headshot']['tmp_name'], $target_file) || $this->FileUpload->upload($fileContent['fileContent']['headshot']['tmp_name'], $target_file)) {
                $user_det = $userTable->get($user_id);
                $user_det->id = $user_id;
                $user_det->headshot = "uploads/peoplecaddie/headshots/" . $candidate_id . "_" . $timestamp . "_" . basename($fileContent['fileContent']['headshot']['name']);
                $userTable->patchEntity($user_det, $params);
                if ($result = $userTable->save($user_det)) {
                    if (!empty($user_headshot)) {
                        unlink(WWW_ROOT . $user_headshot); // Deletes existing profile picture from server
                    }
                    echo json_encode([
                        'status' => 1,
                        'message' => 'Headshot uploaded!',
                        'data' => [
                            'headshot' => $result->headshot
                        ]
                    ]);
                    exit;
                } else {
                    echo json_encode(['status' => 0, 'message' => $user_det->errors()]);
                    exit;
                }
            } else {
                echo json_encode(['status' => 0, 'message' => 'Unable to upload headshot!']);
                exit;
            }
        } else {
            echo json_encode(['status' => 0, 'message' => 'No file is available to upload!']);
            exit;
        }
    }

    public function headshot_cropupload() {
        $this->autoRender = false;
        $params = $this->request->data;

        if (isset($params['candidate_id'])) {
            $candidate_id = $params['candidate_id'];
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id is required']);
            exit;
        }

        $userTable = TableRegistry::get('Users');
        $user_det = $userTable->find()->select(['id', 'headshot'])->where(['bullhorn_entity_id' => $candidate_id])->toArray();
        if (!empty($user_det)) {
            $user_id = $user_det[0]["id"];
            $user_headshot = $user_det[0]["headshot"];
        } else {
            echo json_encode([ 'status' => 0, 'message' => "No candidate available!"]);
            exit;
        }

        $data = $params['fileContent']['headshot'];

        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $timestamp = time();
        $data = base64_decode($data);
        $target_dir = "uploads" . DS . "peoplecaddie" . DS . "headshots" . DS;
        $candidate_id = $params['candidate_id'];
        $target_file = WWW_ROOT . $target_dir . $candidate_id . "_" . $timestamp . '.png';
        $imageName = $target_dir . $candidate_id . "_" . $timestamp . '.png';

        if (file_put_contents($target_file, $data)) {
            $user_det = $userTable->get($user_id);
            $user_det->id = $user_id;
            $user_det->headshot = $imageName;
            $userTable->patchEntity($user_det, $params);
            if ($result = $userTable->save($user_det)) {
                if (!empty($user_headshot) && file_exists(WWW_ROOT . $user_headshot)) {
                    unlink(WWW_ROOT . $user_headshot); // Deletes existing profile picture from server
                }
                echo json_encode([
                    'status' => 1,
                    'message' => 'Headshot uploaded!',
                    'data' => [
                        'headshot' => $imageName
                    ]
                ]);
                exit;
            } else {
                echo json_encode(['status' => 0, 'message' => $user_det->errors()]);
                exit;
            }
        } else
            echo json_encode([ 'status' => 0, 'message' => 'Unable to upload headshot!']);
        exit;
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : headshot_delete
     * Description   : To delete headshot of a candidate
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 29-09-2016
     * Updated Date  : 29-09-2016
     * URL           : general/candidate/headshot_delete
     * Request input : candidate_id
     * Request method: PUT
     */

    public function headshot_delete() {
        $this->autoRender = false;
        $params = $this->request->data;
        $userTable = TableRegistry::get('Users');
        if (isset($params['candidate_id'])) {
            $candidate_id = $params['candidate_id'];
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id is required']);
            exit;
        }

        $user_det = $userTable->find()->select(['id', 'headshot'])->where(['bullhorn_entity_id' => $candidate_id])->toArray();
        if (!empty($user_det)) {
            $user_id = $user_det[0]["id"];
            $headshot = $user_det[0]["headshot"];
        } else {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No candidate available!"
                    ]
            );
            exit;
        }

        $user_det = $userTable->get($user_id);
        $user_det->id = $user_id;
        $user_det->headshot = "";
        $userTable->patchEntity($user_det, $params);
        if ($userTable->save($user_det)) {
            unlink(WWW_ROOT . $headshot);
            echo json_encode(['status' => 1, 'message' => 'Headshot deleted!']);
            exit;
        } else {
            echo json_encode(['status' => 0, 'message' => $user_det->errors()]);
            exit;
        }
    }

    /*
     * *************************************************************************************
     * Function name    :   getPositionTitle
     * Description      :   For getting Position Title
     * Created Date     :   03-11-2016
     * Updated Date     :   28-11-2016
     * Created By       :   Siva.G
     * *************************************************************************************
     */

    public function getPositionTitle($ids) {
        $values = implode(',', $ids);
        $fields = 'title';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $values . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = '';
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

        if (isset($response['data'][0])) {
            $returnData = implode(', ', array_map(function ($data) {
                        return $data['title'];
                    }, $response['data']));
        } elseif (isset($response['data'])) {
            $returnData = $response['data']['title'];
        } else {
            $returnData = '';
        }
        return $returnData;
    }

    /*
     * *************************************************************************************
     * Function name   : getHeadshot
     * Description     : For getting Candidate Headshot
     * Created Date    : 03-11-2016
     * Created By      : Siva.G
     * *************************************************************************************
     */

    public function getHeadshot($candidate_id) {
        $headshot = '';
        $params = $user_det = ["", ""];

        $userTable = TableRegistry::get('Users');
        $user_dets = $userTable->find()->select(['headshot', 'rating'])->where(['bullhorn_entity_id' => $candidate_id])->first();
        if (!empty($user_dets)) {
            $user_det = $user_dets->toArray();
            $user_det = array($user_det['headshot'], $user_det['rating']);
        }
        return $user_det;
    }

    public function candidate_performance($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $fields = 'firstName,lastName,address';
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['candidate_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $response['data']['positionTitle'] = $this->getPositionTitle([$params['job_order_id']]);
        list($response['data']['headshot'], $response['data']['rating']) = $this->getHeadshotRating($params['candidate_id']);
        list($response['data']['performance'], $response['data']['timeliness'], $response['data']['professionalism'], $response['data']['duration']) = $this->getAllPerformance($params['candidate_id'], $params['job_order_id']);
        echo json_encode($response);
    }

    public function getHeadshotRating($candidate_id) {
        $userTable = TableRegistry::get('Users');
        $user_det = $userTable->find()->select(['headshot', 'rating'])->where(['bullhorn_entity_id' => $candidate_id])->first()->toArray();

        return [$user_det['headshot'], $user_det['rating']];
    }

    public function getAllPerformance($candidate_id, $job_order_id) {
        $performanceTable = TableRegistry::get('Performance');
        $performance = $performanceTable->find()->select(['performance', 'timeliness', 'professionalism', 'start_date', 'duration'])->where(['job_order_id' => $job_order_id, 'candidate_bullhorn_id' => $candidate_id])->orderDesc('id')->first();
        if (isset($performance)) {
            $duration = date('m/d/Y', strtotime($performance['start_date'])) . " - " . date('m/d/Y', strtotime("+" . $performance['duration'] . " days", strtotime($performance['start_date'])));
            $performance = $performance->toArray();
            $performances = [$performance['performance'], $performance['timeliness'], $performance['professionalism'], $duration];
        } else {
            $performances = ['', '', '', ''];
        }
        return $performances;
    }

    public function ratings() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        
        $response['data'] = $candidatePlacement = $job_data = $data = $result['data'] = [];
        $perfTable = TableRegistry::get('Performance');
        $referenceTable = TableRegistry::get('Reference');
        $placement_record = $perfTable->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'job_order_id'
                ])->where(['candidate_bullhorn_id' => $params['candidate_id']])->toArray();
        $job_ordersIDs = array_unique(array_filter($placement_record));

        if (!empty($job_ordersIDs)) {
            $job_ordersIDStr = implode(",", $job_ordersIDs);
            $fields = 'id,title,clientCorporation(id,name),startDate,dateEnd';
            $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $job_ordersIDStr . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
            $post_params = json_encode([]);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $response = $this->check_zero_index($response);

            if (isset($response['data'])) {
                foreach ($response['data'] as $job_det) {
                    $job_data[$job_det['id']] = $job_det;
                }
            }
        }
        $userTable = TableRegistry::get('Users');
        $user_det = $userTable->find()->select(['rating'])->where(['bullhorn_entity_id' => $params['candidate_id']])->first()->toArray();
        if(!empty($user_det)) {
            $result['overall_rating'] = $user_det['rating'];
            $result['status'] = 1;
        }    
        $placement_record_det = $perfTable->find('all')->select()->where(['candidate_bullhorn_id' => $params['candidate_id']])->toArray();
        $i = 0;
        if (!empty($placement_record_det)) {
            foreach ($placement_record_det as $placement_record_sgl) {
                if (!is_null($placement_record_sgl['job_order_id']) && isset($job_data[$placement_record_sgl['job_order_id']])) {
                    $result['data'][$i]['company_name'] = $job_data[$placement_record_sgl['job_order_id']]['clientCorporation']['name'];
                    $result['data'][$i]['job_title'] = $job_data[$placement_record_sgl['job_order_id']]['title'];
                    $result['data'][$i]['duration'] = date('m/d/Y', $job_data[$placement_record_sgl['job_order_id']]['startDate']) . " - " . date('m/d/Y',$job_data[$placement_record_sgl['job_order_id']]['dateEnd']);
                    $result['data'][$i]['rating'] = $placement_record_sgl['grade'];
                } else {
                    $query = $referenceTable->findById($placement_record_sgl['reference_id']);
                    if (!empty($query)) {
                        $row = $query->first()->toArray();
                        $result['data'][$i]['company_name'] = $row['company'];
                        $result['data'][$i]['job_title'] = $row['title'];
                        $result['data'][$i]['duration'] = "";
                        $result['data'][$i]['rating'] = $placement_record_sgl['grade'];
                    }
                }
                $i++;
            }
        }
        echo json_encode($result);
        
//        $performanceTable = TableRegistry::get('Performance');
//        $joborder = $performanceTable->find('list', [
//                    'keyField' => 'id',
//                    'valueField' => 'job_order_id'
//                ])->where(['candidate_bullhorn_id' => $params['candidate_id']])->orderDesc('id');
//        if ($joborder->count() > 0) {
//            $performance = $performanceTable->find('all')->where(['candidate_bullhorn_id' => $params['candidate_id']]);
//            $userTable = TableRegistry::get('Users');
//            $user_det = $userTable->find()->select(['rating'])->where(['bullhorn_entity_id' => $params['candidate_id']])->first()->toArray();
//            $fields = 'id,clientCorporation,title';
//            $url = $_SESSION['BH']['restURL'] . 'entity/JobOrder/' . implode(',', $joborder->toArray()) . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
//            $post_params = json_encode($params);
//            $req_method = 'GET';
//            $jobOrderResp = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//            $result = [];
//            if (isset($jobOrderResp['data'][0])) {
//                array_walk($jobOrderResp['data'], function($v)use(&$result) {
//                    $result[$v['id']]['company_name'] = $v['clientCorporation']['name'];
//                    $result[$v['id']]['job_title'] = $v['title'];
//                });
//            } else {
//                $result[$jobOrderResp['data']['id']]['company_name'] = $jobOrderResp['data']['clientCorporation']['name'];
//                $result[$jobOrderResp['data']['id']]['job_title'] = $jobOrderResp['data']['title'];
//            }
//
//            $response['overall_rating'] = $user_det['rating'];
//            $response['status'] = 1;
//            foreach ($performance as $pkey => $pData) {
//                $response['data'][$pkey]['company_name'] = $result[$pData['job_order_id']]['company_name'];
//                $response['data'][$pkey]['job_title'] = $result[$pData['job_order_id']]['job_title'];
//                $response['data'][$pkey]['duration'] = date('m/d/Y', strtotime($pData['start_date'])) . " - " . date('m/d/Y', strtotime("+" . $pData['duration'] . " days", strtotime($pData['start_date'])));
//                ;
//                $response['data'][$pkey]['rating'] = $pData['grade'];
//            }
//
//            echo json_encode($response);
//        } else {
//            echo json_encode(['status' => 0, 'message' => 'No Ratings found']);
//        }
    }

    /*
     * *************************************************************************************
     * Function name   : candidateSlatView
     * Description     : To retrive the candidate information with matching score
     * Created Date    : 08-11-2016
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function slatView($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->loadModel('Users');
        $this->loadModel('Sendout');
        $this->loadModel('SlateSorting');
        $sortedList = array();
        $temp['data'] = array();
        $role = (isset($params['role'])) ? $params['role'] : "";
        switch ($role) {
            case SALESREP_ROLE:
                $control_company_ids = $this->get_owned_company(SALESREP_ROLE, $params['bullhorn_entity_id']);
                $limitValue = 10; /* To display the top 10 candidates */
                break;
            case COMPANY_ADMIN_ROLE || HIRINGMANAGER_ROLE:
                $control_company_ids = $this->get_owned_company(COMPANY_ADMIN_ROLE, $params['bullhorn_entity_id']);
                $limitValue = 5; /* To display the top 5 candidates */
                break;
        }

        if (!empty($control_company_ids)) { // if no company assigned for this particular role
            $job_list = $this->Sendout->find('all')->select(['joborder_id'])->distinct(['joborder_id'])
                    ->where(['hiring_company IN' => $control_company_ids, 'selected_for_client' => 0]);
            $jobListValues = $job_list->toArray();
            if (!empty($jobListValues)) { // if no job posted for this particular company
                foreach ($job_list as $job_lis) {
                    $jobIDs[] = $job_lis->joborder_id;
                }
                $canList = $this->Sendout->find('all')->distinct(['candidate_id'])->select(['candidate_id', 'sendout_id', 'joborder_id', 'candidate_suitability', 'candidate_match', 'candidate_brief_desc', 'updated'])
                                ->where(['joborder_id IN' => $jobIDs, 'selected_for_client' => 0])->order(['updated' => 'DESC'])->toArray();

                $sortedcanList = array_slice($canList, 0, $limitValue, true);

                foreach ($sortedcanList as $canLis) {
                    $candidateList[] = $canLis->sendout_id;
                    $candidate_match[] = $canLis->candidate_match;
                    $contractorID[] = $canLis->candidate_id;
                    $candidateSuitability[] = $canLis->candidate_brief_desc;
                }
                $candidateListVal = implode(',', $candidateList);

                $sortedValues = $this->SlateSorting->find('all')->select(['sendout_id'])->where(['user_id' => $params['bullhorn_entity_id']])->first();
                if (!empty($sortedValues)) {
                    $sortedValues = $sortedValues->toArray();
                    if (!empty($sortedValues)) {
                        $sortedValues1 = explode(',', $sortedValues['sendout_id']);
                        $sortedList = array_intersect($sortedValues1, $candidateList); //get common sendout id between new and sorted
                        $candidateList = array_diff($candidateList, $sortedValues1); //get new senout id from already sorted
                        if (!empty($sortedList)) {
                            $candidateList = array_merge($candidateList, $sortedList); //merge new and sorted sendout id
                            $candidateListChecks = $this->Sendout->find('all')->select(['sendout_id'])->where(['sendout_id IN' => $candidateList, 'selected_for_client' => 0])->toArray();
                            foreach ($candidateListChecks as $candidateListCheck) {
                                $candidateList[] = $candidateListCheck->sendout_id;
                            }
                            $candidateListVal = implode(',', $candidateList);
                        }
                    }
                }
                $this->bh_connect = $this->BullhornConnection->BHConnect();
                $fields = 'id,candidate(id,namePrefix,firstName,lastName,address,hourlyRate,occupation),jobOrder(title)';
                $url = $_SESSION['BH']['restURL'] . '/entity/Sendout/' . $candidateListVal . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                $post_params = json_encode('');
                $req_method = 'GET';
                $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

                $candidatesInfo = $this->Users->find('all')->select(['rating', 'headshot', 'bullhorn_entity_id'])->where(['bullhorn_entity_id IN' => $contractorID])->toArray();

                if (isset($response['data'])) {
                    $new_sendout_id = array_flip($candidateList);
                    $response['data'] = $this->check_zero_index($response['data']);
                    foreach ($response['data'] as $key => $val) {
                        foreach ($candidatesInfo as $candidatesInf) {
                            if ($candidatesInf->bullhorn_entity_id == $val['candidate']['id']) {
                                $response['data'][$key]['candidate']['rating'] = $candidatesInf['rating'];
                                $response['data'][$key]['candidate']['headshot'] = $candidatesInf['headshot'];
                            }
                        }
                    }
                    foreach ($response['data'] as $key1 => $val) {
                        foreach ($canList as $canLis) {
                            if ($canLis->candidate_id == $val['candidate']['id']) {
                                $response['data'][$key1]['candidate']['sendout_id'] = $canLis['sendout_id'];
                                $response['data'][$key1]['candidate_match'] = $canLis['candidate_match'];
                                $response['data'][$key1]['candidate']['candidate_brief_desc'] = $canLis['candidate_brief_desc'];
                            }
                        }
                        if (isset($new_sendout_id[$response['data'][$key1]['id']]) && !empty($sortedList))
                            $temp['data'][$new_sendout_id[$response['data'][$key1]['id']]] = $response['data'][$key1];
                    }
                }

                ///if sorting is available
                if (!empty($temp['data'])) {
                    ksort($temp['data']);
                    $response['data'] = $temp['data'];
                }

                //if matching occur with sorting to skip comparing with candiate match field
                if (empty($sortedList)) {
                    uasort($response['data'], function($a, $b) {
                        return $a['candidate_match'] < $b['candidate_match'];
                    });
                }
                echo json_encode($response);
                exit;
            } else { // if no job posted for this particular company
                echo json_encode($response = array());
                exit;
            }
        } else {
            echo json_encode($response = array());
            exit;
        }
    }

//     public function test($params = null) {
//        $this->autoRender = false;
//        $params = $this->request->data;
//        $this->BullhornConnection->BHConnect();
//        $url = $_SESSION['BH']['restURL'] . '/meta/CandidateEducation?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
//        $post_params = json_encode($params);
//        $req_method = 'GET';
//        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//        echo json_encode($response);
//    }

    /*
     * *************************************************************************************
     * Function name   : slateSort
     * Description     : To store the candidate sorted values by salesrep
     * Created Date    : 29-11-2016
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */
    public function slateSort($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->loadModel('SlateSorting');
        $sortTable = TableRegistry::get('SlateSorting');
        $getUser = $sortTable->find()->select()->where(['user_id' => $params['user_id'], 'joborder_id' => $params['joborder_id']])->toArray();
        if (empty($getUser)) {
            $sortCandidate = $this->SlateSorting->newEntity($params);
            if ($this->SlateSorting->save($sortCandidate) && !empty($params['old_sendout_id'])) {
                /* to send notifications to hiring managers */
                $oldSendOutID = explode(',', $params['old_sendout_id']);
                $newSendOutID = explode(',', $params['sendout_id']);
                $result = array_values(array_diff_assoc($oldSendOutID, $newSendOutID));
                if (!empty($result)) {
                    $this->sort_hm_notification($result);
                }
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "Sorting Updated!"
                        ]
                );
                exit;
            }
        }
        if (!empty($getUser)) {
            if (strpos($getUser[0]["sendout_id"], $params['sendout_id']) === false) {
                $sort_id = $getUser[0]["id"];
                $sortCandi = $sortTable->get($sort_id);
                $sortTable->patchEntity($sortCandi, $params);
                $sortCandi->id = $sort_id;
                $sortCandi->user_id = $params['user_id'];
                $sortCandi->joborder_id = $params['joborder_id'];
                $sortCandi->candidate_id = $params['sendout_id'];
                $sortTable->save($sortCandi);
                /* to send notifications to hiring managers */
                $oldSendOutID = explode(',', $getUser[0]["sendout_id"]);
                $newSendOutID = explode(',', $params['sendout_id']);
                $result = array_values(array_diff_assoc($oldSendOutID, $newSendOutID));
                if (!empty($result)) {
                    $this->sort_hm_notification($result);
                }
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "Sorting Updated!"
                        ]
                );
                exit;
            } else {
                echo json_encode(
                        [
                            'status' => 0,
                            'message' => "Already Sorted!"
                        ]
                );
                exit;
            }
        }
    }

    /*
     * *************************************************************************************
     * Function name   : sort_hm_notification
     * Description     : To send notifications to the hiring managers
     * Created Date    : 10-12-2016
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function sort_hm_notification($params = null) {

        $sendoutTable = TableRegistry::get('Sendout')->find('all')->select(['hiring_company'])->where(['sendout_id IN' => $params]);
        if (!empty($sendoutTable)) {
            foreach ($sendoutTable as $sendoutTabl) {
                $data[] = $sendoutTabl->hiring_company;
            }
            $data = array_unique($data);
            if (!empty($data)) {
                $hm_infos = TableRegistry::get('Users');
                foreach ($data as $dat) {
                    $hm_info[] = $hm_infos->get_hiringmanager_id($dat);
                }
                TableRegistry::get('Notifications')->candidate_match_update($hm_info, $params);
            }
        }
    }

    /*
     * *************************************************************************************
     * Function name   : changePassword
     * Description     : For changing the password
     * Created Date    : 07-11-2016
     * Created By      : Balasuresh A
     * *************************************************************************************
     */

    public function changePassword($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $userTable = TableRegistry::get('Users');

        if ($this->Auth->identify()) { //checking the valid user or not
            $user_id = $params['user_id'];
            $getUserBullhornId = $params['bullhorn_entity_id'];
            $params['password'] = $params['new_password'];
            $changePassword = $this->user_update($getUserBullhornId, $params, 'bullhorn_id');

            if ($changePassword) {
                $response = [];
                if ($getUserBullhornId != 0) {

                    $this->BullhornConnection->BHConnect();
                    $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $getUserBullhornId . '?BhRestToken=' . $_SESSION['BH']['restToken'];
                    $bhArr = [
                        'id' => $getUserBullhornId,
                        'password' => $params['new_password']
                    ];
                    $post_params = json_encode($bhArr);
                    $req_method = 'POST';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                }
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "Password reset done successfully!",
                        ]
                );
                exit;
            } else { // failed to update the user password
                echo json_encode(
                        [
                            'status' => 0,
                            'message' => "Unable to reset your password"
                        ]
                );
                exit;
            }
        } else { // current password is not matching
            echo json_encode(
                    [
                        'status' => 2,
                        'message' => "Your current password is not matching"
                    ]
            );
            exit;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : businessSectorCD
     * Description     : To add & delete detail of business sector for candidate
     * Created Date    : 11-11-2016
     * Created By      : Akilan
     * *************************************************************************************
     */

    public function businessSectorCD($params, $id, $req_method) {
        if (isset($params['business_sector']) && !empty($params['business_sector'])) {
            $business_hrs = implode(",", $params['business_sector']);
            $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $id . '/businessSectors/' . $business_hrs . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $params1 = json_encode(array());
            $response = $this->BullhornCurl->curlFunction($url, $params1, $req_method);
        }
    }

    /*
     * *************************************************************************************
     * Function name   : businessSectorUpdate
     * Description     : To add detail business sector for candidate
     * Created Date    : 11-11-2016
     * Created By      : Akilan
     * *************************************************************************************
     */

    public function businessSectorUpdate($params) {
        if (isset($params['old_business_sector']) && isset($params['new_business_sector'])) {
            $old_selected = $params['old_business_sector'];
            $new_selected = $params['new_business_sector'];
            $for_delete = array_diff($old_selected, $new_selected);
            $for_insert = array_diff($new_selected, $old_selected);
            if (!empty($for_insert)) {
                $params['business_sector'] = $for_insert;
                $this->businessSectorCD($params, $params['id'], 'PUT');
            }
            if (!empty($for_delete)) {
                $params['business_sector'] = $for_delete;
                $this->businessSectorCD($params, $params['id'], 'DELETE');
            }
        }
        return;
    }

    /*     * * * * * * * * * *  * * * *
     * Function name : CandidateEducation
     * Description   : seperate candidate education and certification detail
     * Created by    : Akilan
     * Updated by    : Akilan
     */

    function seperate_business_sector($params, $candidate_id = null) {
        $business_sector = array();
        $business_sector['id'] = $params['id'];
        if (isset($params['old_business_sector'])) {
            $business_sector['old_business_sector'] = $params['old_business_sector'];
            unset($params['old_business_sector']);
        }
        if (isset($params['new_business_sector'])) {
            $business_sector['new_business_sector'] = $params['new_business_sector'];
            unset($params['new_business_sector']);
        }
        return array($params, $business_sector);
    }

    public function getSendOut($sendout_id, $candidate_id) {
        $sendoutTable = TableRegistry::get('Sendout');
        $data = $sendoutTable->find()->where(['sendout_id' => $sendout_id, 'candidate_id' => $candidate_id]);
        if ($data->count()) {
            $sendOutInfo = $data->first()->toArray();
            $sendOutInfo['desired_hourly_rate_to'] = $sendoutTable->biddrate_calculate($sendOutInfo['desired_hourly_rate_to']);
            return $sendOutInfo;
        } else {
            return null;
        }
    }

    /*     * * * * * * * * * *  * * * *
     * Function name : canUploadFiles
     * Description   : check for candidate file upload limit
     * Created by    : Sivaraj V
     */

    public function canUploadFiles($limit, $nFiles, $candidate_id) {
        $totalExistingFiles = TableRegistry::get('Attachment')->find('all')->select()->where(['candidate_id' => $candidate_id, 'file_id !=' => 0])->count();
        $totalFiles = $totalExistingFiles + $nFiles;
        if ($totalExistingFiles >= $limit) {
            echo json_encode([
                'status' => 2,
                'message' => "Hi, You have already uploaded " . $totalExistingFiles . " files. Note: You are limited to upload only " . $limit . " files."
            ]);
            exit;
        } else if ($totalExistingFiles < $limit && $totalFiles > $limit) {
            echo json_encode([
                'status' => 2,
                'message' => "Hi, You have already uploaded " . $totalExistingFiles . " files. You can upload " . (((int) $limit) - ((int) $totalExistingFiles)) . " more file(s). Note: You are limited to upload only " . $limit . " files."
            ]);
            exit;
        }
        return true;
    }

    /*
     * *************************************************************************************
     * Function name   : candidateSlatView
     * Description     : To retrive the candidate information with matching score
     * Created Date    : 08-11-2016
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function slatView1($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->loadModel('Users');
        $this->loadModel('Sendout');
        $this->loadModel('SlateSorting');
        $conditions = $sortedList = $mysortedList = $matched_candidate = array();
        $temp['data'] = array();


        $role = (isset($params['role'])) ? $params['role'] : "";

//        switch ($role) {
//            case SUPER_ADMIN_ROLE || SALESREP_ROLE:
//                $limitValue = 10; /* To display the top 10 candidates */
//                break;
//            case COMPANY_ADMIN_ROLE || HIRINGMANAGER_ROLE:
//                $limitValue = 5; /* To display the top 5 candidates */
//                break;
//        }

        if (!empty($params['joborder_id'])) {

            if (($role == SUPER_ADMIN_ROLE) || ($role == SALESREP_ROLE)) {
                $conditions = [['joborder_id' => $params['joborder_id'], 'selected_for_client <>' => 2, 'OR' => [['job_submission_status' => 0], ['job_submission_status' => 1]]]];
                $sortingCondtions = ['joborder_id' => $params['joborder_id'], 'user_id' => $params['bullhorn_entity_id']];
            } else {
                $conditions = [['joborder_id' => $params['joborder_id'], 'candidate_match >' => '0%', 'selected_for_client <>' => 2, 'OR' => [['job_submission_status' => 0], ['job_submission_status' => 1]]]];
                $sortingCondtions = ['OR' => [['joborder_id' => $params['joborder_id']], ['user_id' => $params['bullhorn_entity_id']]]];
            }

            $canList = $this->Sendout->find('all')->distinct(['candidate_id'])->select(['candidate_id', 'sendout_id', 'joborder_id', 'desired_hourly_rate_to', 'candidate_suitability', 'candidate_match', 'candidate_brief_desc', 'updated'])
                            ->where($conditions)->order(['candidate_match' => 'DESC']);
            if (!empty($canList)) {
                $canList = $canList->toArray();
                if (!empty($canList)) {
                    // $sortedcanList = array_slice($canList, 0, $limitValue, true);
                    // $sortedcanList = (($role == COMPANY_ADMIN_ROLE) || ($role == HIRINGMANAGER_ROLE)) ? array_slice($canList, 0, 5, true) : $canList;
                    foreach ($canList as $canLis) {
                        $candidateList[] = $canLis->sendout_id;
                        $candidate_match[] = $canLis->candidate_match;
                        $contractorID[] = $canLis->candidate_id;
                        $candidateSuitability[] = $canLis->candidate_brief_desc;
                    }

                    $sortedValues = $this->SlateSorting->find('all')->select(['sendout_id'])->where($sortingCondtions)->first();
                    if (!empty($sortedValues)) {
                        $sortedValues = $sortedValues->toArray();
                        if (!empty($sortedValues)) {
                            $sortedValues1 = explode(',', $sortedValues['sendout_id']);
                            $new_sendout_id = array_flip($sortedValues1);
                            $sortedList = array_intersect($sortedValues1, $candidateList); //get common sendout id between new and sorted

                            $candidateList = array_diff($candidateList, $sortedValues1); //get new senout id from already sorted

                            if (!empty($sortedList)) {
                                $candidateList = array_merge($sortedList, $candidateList); //merge new and sorted sendout id                               
                                if (!empty($sortedList) && !empty($candidateList))
                                    $desc = 'FIELD(sendout_id, ' . implode(',', $candidateList) . ')';
                                else
                                    $desc = ['candidate_match' => 'DESC'];
                                $candidateListChecks = $this->Sendout->find('all')->select(['sendout_id', 'candidate_match'])->where(['sendout_id IN' => $candidateList, $conditions])->order($desc)->toArray();
                                $i = 0;
                                foreach ($candidateListChecks as $candidateListCheck) {
                                    $candidateList[] = $candidateListCheck->sendout_id;
                                }
                                $final_list_can = array_unique($candidateList);
                                $final_list_candidates = array_flip($final_list_can);
                            }
                        }
                    }
                    /**
                     * Show 5 members to hiring manager and company admin
                     */
                    $candidateList = (($role == COMPANY_ADMIN_ROLE) || ($role == HIRINGMANAGER_ROLE)) ? array_slice($candidateList, 0, 5, true) : $candidateList;
                    $candidateListVal = implode(',', array_unique($candidateList));
                    $this->bh_connect = $this->BullhornConnection->BHConnect();
                    $fields = 'id,candidate(id,namePrefix,firstName,lastName,address,hourlyRate,hourlyRateLow,occupation),jobOrder(title)';
                    $url = $_SESSION['BH']['restURL'] . '/entity/Sendout/' . $candidateListVal . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
                    $post_params = json_encode('');
                    $req_method = 'GET';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

                    $candidatesInfo = $this->Users->find('all')->select(['rating', 'headshot', 'bullhorn_entity_id'])->where(['bullhorn_entity_id IN' => $contractorID])->toArray();

                    if (isset($response['data'])) {
                        $response = $this->check_zero_index($response);
                        foreach ($response['data'] as $key => $val) {
                            foreach ($candidatesInfo as $candidatesInf) {
                                if ($candidatesInf->bullhorn_entity_id == $val['candidate']['id']) {
                                    $response['data'][$key]['candidate']['rating'] = $candidatesInf['rating'];
                                    $response['data'][$key]['candidate']['headshot'] = $candidatesInf['headshot'];
                                }
                            }
                        }

                        $j = 0;
                        foreach ($response['data'] as $key1 => $val) {
                            foreach ($canList as $canLis) {
                                if ($canLis->candidate_id == $val['candidate']['id']) {
                                    $response['data'][$key1]['candidate']['sendout_id'] = $canLis['sendout_id'];
                                    $response['data'][$key1]['candidate_match'] = $canLis['candidate_match'];
                                    //$response['data'][$key1]['candidate']['hourlyRateLow'] = $canLis['desired_hourly_rate_to'];
                                    $response['data'][$key1]['candidate']['hourlyRateLow'] = $this->Sendout->biddrate_calculate($canLis['desired_hourly_rate_to']);
                                    $response['data'][$key1]['candidate']['candidate_brief_desc'] = $canLis['candidate_brief_desc'];
                                    /**
                                     * To check available in hiring manager list
                                     */
                                    $response['data'][$key1]['hm_list'] = 0;
                                }
                            }
                            if (isset($final_list_candidates[$response['data'][$key1]['id']]) && !empty($sortedList))
                                $temp['data'][$final_list_candidates[$response['data'][$key1]['id']]] = $response['data'][$key1];
                        }
                    }
                    ///if sorting is available
                    if (!empty($temp['data'])) {
                        ksort($temp['data']);
                        $response['data'] = $temp['data'];
                    }

                    //if matching occur with sorting to skip comparing with candiate match field
                    if (empty($sortedList)) {
                        uasort($response['data'], function($a, $b) {
                            return $a['candidate_match'] < $b['candidate_match'];
                        });
                    }
                    $k = 0;
                    foreach ($response['data'] as $key1 => $val) {
                        if ($k > 4)
                            break;
                        if (isset($val['candidate_match']) && (int) $val['candidate_match'] > 0) {
                            $response['data'][$key1]['hm_list'] = 1;
                            $k++;
                        }
                    }

                    echo json_encode($response);
                    exit;
                } else {
                    echo json_encode($response = array());
                }
            } else { // if no job posted for this particular company
                echo json_encode($response = array());
                exit;
            }
        } else {
            echo json_encode($response = array());
            exit;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : slate_login_update
     * Description     : To store the last login of hiring manager when view the slate page.
     * Created Date    : 30-01-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function slate_login_update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        if (!empty($params)) {
            TableRegistry::get('Users')->query()->update()->set(['last_login' => $params['last_login']])
                    ->where(['bullhorn_entity_id' => $params['bullhorn_entity_id']])
                    ->execute();
        }
    }
    
    public function file_check($params = null) {
        $this->autoRender = false;
        $params = $this->request->data();
//        $params['candidate_id'] = 2883;
//        $params['file_id'] = 167232;
        $sendoutTable = TableRegistry::get('Sendout');
        if(isset($params['candidate_id']) && !empty($params['candidate_id']) && isset($params['fileId']) && !empty(isset($params['fileId']))) {
            $attachementInfo = $sendoutTable->find('list', ['keyField' => 'id', 'valueField' => 'attachments'])->where(['candidate_id' => $params['candidate_id']]);
            if (!empty($attachementInfo)) {
                $candidateAttach = $attachementInfo->toArray();

                $candidateAttach = array_values($candidateAttach);
                $candidateAttach = implode(',', $candidateAttach);
                $candidateAttach = explode(',', $candidateAttach);
                $candidateAttach = array_filter($candidateAttach);
                $candidateAttachValues = array_flip($candidateAttach);
                    if (isset($candidateAttachValues[$params['fileId']])) {
                        echo json_encode(['status' => 2, 'message' => 'This document is needed for Job Application. If you delete it, hiring manager will not be able to review it']);
                        exit;
                    } else {
                        $this->confirm_file_delete($params['candidate_id'],$params['fileId']);
                    }
            }
        } else {
           echo json_encode(['status' => 0, 'message' => 'Candidate id and file id are required']);
           exit;
        }
    }

    public function confirm_file_delete($candidate_id,$fileId) {
        $this->BullhornConnection->BHConnect();
        if (!empty($candidate_id) && !empty($fileId)) {
            $candidate_id = $candidate_id;
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id and file id are required']);
            exit;
        }
        $url = $_SESSION['BH']['restURL'] . '/file/Candidate/' . $candidate_id . '/' . $fileId . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode('');
        $req_method = 'DELETE';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $attachmentTable = TableRegistry::get('Attachment');
        $getFile = $attachmentTable->find()->select(['id', 'filepath'])->where(['candidate_id' => $candidate_id, 'file_id' => $fileId])->toArray();
        //echo "<pre>";print_r($getFile); echo "</pre>";
        if (!empty($getFile)) {
            unlink(WWW_ROOT . $getFile[0]['filepath']);
            $attachmentTable->delete($attachmentTable->get($getFile[0]['id']));
            echo json_encode([
                'status' => 1,
                'message' => 'Deleted successfully!',
                'result' => ['fileId' => isset($response['fileId']) ? $response['fileId'] : null]
            ]);
            exit;
        } else {
            echo json_encode(['status' => 0, 'result' => $response]);
            exit;
        }
    }
    
    /*
     * *************************************************************************************
     * Function name   : category_update
     * Description     : To update and store the category list values via cron jobs
     * Created Date    : 24-04-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */
    
    public function category_update() {
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $category = [];
        $categoryTable = TableRegistry::get('Category');

        $url = $_SESSION['BH']['restURL'] . '/options/Category?BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=300&fields=*&rand=' . rand(1, 1111111111);
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data']) && !empty($response)) {
            foreach ($response['data'] as $k => $data) {
                $response['data'][$k]['bullhorn_entity_id'] = $response['data'][$k]['value'];
                $response['data'][$k]['category_name'] = $response['data'][$k]['label'];
                unset($response['data'][$k]['value']);
                unset($response['data'][$k]['label']);
            }

            foreach ($response['data'] as $data) {
                $existingId = $this->findCatID($data['bullhorn_entity_id']); // get existing record

                if ($existingId) { // if record exists, map data to correct record
                    $categoryTable->query()->update()->set(['category_name' => $data['category_name']])->
                            where(['bullhorn_entity_id' => $existingId['bullhorn_entity_id']])->execute();
                } else { // save data to table
                    $addData = $categoryTable->newEntity($data);
                    $categoryTable->save($addData);
                }
            }
        }
    }
    
    public function findCatID($cat_id = null) {
        $categoryTable = TableRegistry::get('Category');
        $catVal = [];
        $catRecord = $categoryTable->find()->select(['bullhorn_entity_id'])->where(['bullhorn_entity_id' => $cat_id])->first();
        if(!empty($catRecord)) {
            $catVal = $catRecord->toArray();  
        }
        return $catVal;
    }
    
    /*
     * *************************************************************************************
     * Function name   : skills_update
     * Description     : To update and store the category list values via cron jobs
     * Created Date    : 24-04-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */
    
    public function skills_update() {
        $this->autoRender = false;
        $skillTable = TableRegistry::get('SkillList');
        $this->BullhornConnection->BHConnect();
        $categories = implode(',', $this->getCategories());
        $result = $this->getAllSkills($categories); 
        if(isset($result['data'][0]) && !empty($result['data'][0])){
                foreach($result['data'][0] as $skillData) {
                    $existingSkill = $this->findSkillID($skillData['bullhorn_category_id'],$skillData['bullhorn_skill_id']); // get existing record
                        if($existingSkill) { // if record exists, map data to correct record
                            $skillTable->query()->update()->set(['skill_name' => $skillData['skill_name']])
                                ->where(['bullhorn_category_id' => $existingSkill['bullhorn_category_id'],'bullhorn_skill_id' => $existingSkill['bullhorn_skill_id']])->execute();
                        } else { // save data to table
                             $addData = $skillTable->newEntity($skillData);
                             $skillTable->save($addData);
                        }
                }
            } else {
            }
    }
    
    public function findSkillID($cat_id,$skill_id) {
        $skillTable = TableRegistry::get('SkillList');
        $skillList = [];
        if(!empty($cat_id) && !empty($skill_id)){
            $skillVal = $skillTable->find()->select(['bullhorn_category_id','bullhorn_skill_id'])->where(['bullhorn_category_id' => $cat_id,'bullhorn_skill_id'=> $skill_id])->first();
            if(!empty($skillVal)) {
                $skillList = $skillVal->toArray();
            }
        }
        return $skillList;
    }
    
    public function getCategories() {
        $this->BullhornConnection->BHConnect();
        $category = [];
        $url = $_SESSION['BH']['restURL'] . '/options/Category?BhRestToken='. $_SESSION['BH']['restToken'];
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if(isset($response['data'])){
            foreach($response['data'] as $cat){
                $category[] = $cat['value'];
            }
        }
        return $category;
    }
    
    public function getAllSkills($ids = [],  $dupValidate = []) {
        $this->BullhornConnection->BHConnect();
        $category = $response1 = [];
        $post_params = json_encode([]);
        $req_method = 'GET';
        $start = 0;
        $limit = 200;
            for ($i = 0; $i < 1000; $i++) {
                $start = $i * $limit;
                $url = $_SESSION['BH']['restURL'] . '/query/Skill?where=categories.id+IN+(' . $ids . ')&BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,name,categories[1000](id,name)&count=' . $limit . '&start=' . $start;
                $response1 = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                if (empty($response)) {
                    $response = $response1;
                } else {
                    $response['data'] = array_merge($response['data'], $response1['data']);
                }
                if ($response1['count'] != $limit) {                    
                    break;
                }
            }
        $category_with_skills = [];
        $result = [];
        if (isset($response['data'])) {
            if (!isset($response['data'][0])) {
                $resp = $response['data'];
                unset($response['data']);
                $response['data'][] = $resp;
            }
            foreach ($response['data'] as $cat1) {
                if (!empty($cat1['categories']['data'])) {
                    foreach ($cat1['categories']['data'] as $cat2) {
                        if (!empty($dupValidate)) {
                            if (isset($dupValidate[$cat2['id']]) && in_array($cat1['id'], $dupValidate[$cat2['id']])) {
                                $category_with_skills[$cat2['id']][$cat2['name']]['skills'][] = [
                                    'id' => $cat1['id'],
                                    'name' => $cat1['name'],
                                ];
                            }
                        } else {
                            $category_with_skills[] = [
                                'bullhorn_skill_id' => $cat1['id'],
                                'skill_name' => $cat1['name'],
                                'bullhorn_category_id' => $cat2['id']
                            ];
                        }
                    }
                }
            }
            $result['data'][] = $category_with_skills;
        }
        return $result;
    }

}

?>

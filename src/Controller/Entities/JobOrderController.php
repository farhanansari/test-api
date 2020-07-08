<?php

/* * ************************************************************************************
 * Class name      : JobOrderController
 * Description     : Joborder CRUD process
 * Created Date    : 23-08-2016 *
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
use Cake\Core\Configure;
use Cake\Mailer\Email;

class JobOrderController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : add
     * Description   : To create the open job.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/joborder/?
     * Request input : clientContact[id], clientCorporation[id], employmentType, title, owner[id]
     * Request method: PU
     * Responses:
      1. success:{"changedEntityType":"JobOrder","changedEntityId":10,"changeType":"INSERT","data":{"clientContact":{"id":"14"},"clientCorporation":{"id":"3"},"employmentType":"'permanent'","title":"'Android Developer'","owner":{"id":"1"},"startDate":1468594630}}
      2.fail:
     */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder?BhRestToken=' . $_SESSION['BH']['restToken'];
//        $params['startDate'] = time();
        $params['dateAdded'] = time();
        if (isset($params['dateEnd']) && !empty($params['dateEnd']))
            $params['dateEnd'] = strtotime($params['dateEnd']);
        if (isset($params['startDate']) && !empty($params['startDate'])) {
            $params['startDate'] = strtotime($params['startDate']);
            if (isset($params['durationWeeks']) && !empty($params['durationWeeks'])) {
                $durationArr = [1 => "1 week", 2 => "2 weeks", 3 => "3 weeks", 4 => "1 month", 5 => "2 months", 6 => "3 months", 7 => "4 months", 8 => "5 months", 9 => "6 months", 10 => "7 months", 11 => "8 months", 12 => "9 months", 13 => "10 months", 14 => "11 months", 15 => "1 year", 16 => "2 years", 17 => "3 years"];
                //$durationArr = [1 => "1 week", 2 => "2 weeks", 3 => "3 weeks", 4 => "1 month", 8 => "2 months", 13 => "3 months", 17 => "4 months", 21 => "5 months", 26 => "6 months", 30 => "7 months", 34 => "8 months", 39 => "9 months", 43 => "10 months", 47 => "11 months", 52 => "1 year", 104 => "2 years", 156 => "3 years"];
                $params['dateEnd'] = strtotime('+' . $durationArr[$params['durationWeeks']], $params['startDate']);
                $params['dateEnd'] = strtotime('-1 day',$params['dateEnd']);
            }
        }

        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $data = $this->sendMail($response);
        echo json_encode($response);
    }
    
    /* *************************************************************************
     * Action Name   : sendMail
     * Description   : To send email notification, when new job is created
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 09-05-2017
     * Updated Date  : 
     * ************************************************************************/
    
    public function sendMail($response = []) {
        if(!empty($response) && isset($response['changedEntityType'])) {
            $toAddress = 'austinfox@peoplecaddie.com';
                $verifyLink = Configure::read('server_address') . 'positions/edit/' . $response['changedEntityId'];
                $var = ['subject' => 'New Position Created','job_order_id' => $response['changedEntityId'], 'job_title' => $response['data']['title'],'verifyLink' => $verifyLink];
                    $email = new Email();
                    $email->template('job_creation', 'user')
                            ->emailFormat('html')
                            ->viewVars(['var' => $var])
                            ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                            ->to($toAddress)
                            ->subject('New Position Created')
                            ->send();
        }
        return;
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : view
     * Description   : To retrieve the candidate profile.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/joborder/?id=10
     * Request input : id => ID of the job and candidate_id
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function view($params = null) {
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $inviteContractorsTable = TableRegistry::get('InviteContractor');
        $userTable = TableRegistry::get('Users');
        $candidate_id = isset($params['candidate_id']) ? $params['candidate_id'] : "";
        $sendout_id = isset($params['sendout_id']) ? $params['sendout_id'] : "";
        $fields = 'id,address,clientCorporation(address),clientContact,dateAdded,dateEnd,startDate,description,employmentType,title,salary,skills[50](id,name),skillList,salaryUnit,durationWeeks,customTextBlock3,'
                . 'yearsRequired,isPublic,correlatedCustomInt1,correlatedCustomInt2,correlatedCustomInt3,correlatedCustomFloat1,correlatedCustomFloat2,customInt1,customInt2,customText1,customText2,customText3,customText4,customText5,customText6,customText7,customText8,customTextBlock1,customTextBlock2,customFloat1,customFloat2,status,categories[50](id,name),placements[50](candidate(firstName,lastName,email))';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data']) && !empty($candidate_id)) {
            $invisible_job = $this->get_bookmarked_jobs($candidate_id);
            $response['data']['isBookmarked'] = $this->isJobBookmarked($candidate_id, $response['data']['id'], $invisible_job);
            $response['data']['customSendout'] = $this->getCustomSendoutInfo($candidate_id, $response['data']['id'], $sendout_id);
            $response['data']['is_applied'] = $this->checkJobApplied($params['id'], $candidate_id);
            $response['data']['is_placed'] = $this->checkJobPlaced($response['data']['customSendout']);
        }


        if (isset($response['data'])) {
            if (isset($response['data']['clientContact']['id'])) {
                $response['data']['hiringManagerGrade'] = $this->getJobViewHMGrade($response['data']['clientContact']['id'], $params['id']);
            } else {
                $response['data']['hiringManagerGrade'] = 0;
            }
            /* get sales representative name for the company*/
            if(isset($response['data']['clientCorporation']['id']) && (!empty($response['data']['clientCorporation']['id']))) {
                $bullhornId = $userTable->get_sales_rep_id($response['data']['clientCorporation']['id']);
                $response['data']['salesRepName'] = $userTable->name_with_email($bullhornId);
            } else {
                $response['data']['salesRepName'] = '';
            }
            $response['data']['customCategory'] = $this->getCategory(trim($response['data']['customText8']));
            $response['hidden_company'] = HIDDEN_COMPANY_TEXT; //for showing hidden company text
        }
        if (isset($params['invite_contractors']) && $params['invite_contractors']) {
            $getInvites = $inviteContractorsTable->find()->select(['id', 'joborder_id', 'emails'])
                    ->where(['joborder_id' => $response['data']['id']])
                    ->toArray();
            if (!empty($getInvites)) {
                $emails = explode(',', $getInvites[0]['emails']);
                $getUsers = TableRegistry::get('User')->find('all')->select(['id', 'firstName', 'lastName', 'email', 'phone', 'bullhorn_entity_id'])->where(['email IN' => $emails, 'role' => CANDIDATE_ROLE])->toArray();
                $existingInvitedContractors = [];
                $invitedContrators = [];
                if (!empty($getUsers)) {
                    foreach ($getUsers as $contractor) {
                        $existingInvitedContractors[] = $contractor['email'];
                        $invitedContrators[] = $contractor;
                    }
                }
                $newInvitedContractors = array_diff($emails, $existingInvitedContractors); // Find new invited contrators
                if (!empty($newInvitedContractors)) {
                    foreach ($newInvitedContractors as $newinvite) {
                        $invitedContrators[] = [
                            'id' => null,
                            'firstName' => null,
                            'lastName' => null,
                            'email' => $newinvite,
                            'phone' => null,
                            'bullhorn_entity_id' => null
                        ];
                    }
                }

                $response['data']['invite_contractors'] = $invitedContrators;
            }
        }

        /**
         * Check candidate performance was updated
         */
        if (isset($params['id']) && !empty($params['id']) && !empty($response['data']['placements'])) {
            $closedPlacements = $response['data']['placements'];
            $placed_candidate_ids = $this->get_candidate_info($params);
            if (!empty($placed_candidate_ids)) {
                foreach ($response['data']['placements']['data'] as $key => $val) {
                    if (isset($placed_candidate_ids[$val['candidate']['id']])) {
                        $response['data']['placements']['total'] = $response['data']['placements']['total'] - 1;
//                    if (in_array($val['candidate']['id'], $placed_candidate_ids)) {
                        unset($response['data']['placements']['data'][$key]);
                    }
                }
            }
            $placementDetails = $response['data']['placements']['data'];
            $response['data']['placements']['data'] = $this->getBillRate($placementDetails,$response['data']['id']);
            if(empty($placementDetails) && !empty($closedPlacements)) {
                $response['data']['placements'] = $closedPlacements;
                $response['data']['placements']['data'] = $this->getBillRate($closedPlacements['data'],$response['data']['id']);
            }
        }

        echo json_encode($response);
    }
    
    /*
     * *************************************************************************************
     * Function name   : getBillRate
     * Description     : To retrive the candidate pay rate and billrate values
     * Created Date    : 14-04-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */
    public function getBillRate($placementDetails = [], $joborder_id = null) {
        $data = [];
        $sendoutTable = TableRegistry::get('Sendout');
        if(!empty($placementDetails) && !empty($joborder_id)) {
            foreach($placementDetails as $key => $contractorDetail) {
                $payrateVal = $sendoutTable->find()->select(['desired_hourly_rate_to'])->where(['candidate_id' => $contractorDetail['candidate']['id'], 'joborder_id'=> $joborder_id])->first();
                if(!empty($payrateVal)) {
                $payrateVal = $payrateVal->toArray();
                $billrateVal = $sendoutTable->biddrate_calculate($payrateVal['desired_hourly_rate_to']);
                $taxrateVal = $sendoutTable->tax_calculate($payrateVal['desired_hourly_rate_to']);
                $placementDetails[$key]['candidate']['payRate'] = $payrateVal['desired_hourly_rate_to'];
                $placementDetails[$key]['candidate']['billRate'] = $billrateVal;
                $placementDetails[$key]['candidate']['taxRate'] = $taxrateVal;
                }
            }
            $data = $placementDetails;
        }
        return $data;
    }
    /*
     * get category name using category id
     */

    public function getCategory($id = null, $getCollection = false) {
        //$this->BullhornConnection->BHConnect();
        $category = [
            'id' => '',
            'name' => ''
        ];
        $collection = [];
        $url = $_SESSION['BH']['restURL'] . '/options/Category?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data'])) {
            foreach ($response['data'] as $cat) {
                if ($cat['value'] == $id) {
                    $category = [
                        'id' => $cat['value'],
                        'name' => $cat['label']
                    ];
                }
                $collection[$cat['value']] = [
                    'id' => $cat['value'],
                    'name' => $cat['label']
                ];
            }
        }
        if ($getCollection) {
            return $collection;
        } else {
            return $category;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : get_candidate_info
     * Description     : To retrive the candidate information by placement id
     * Created Date    : 05-01-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function get_candidate_info($params) {
        $performanceTable = TableRegistry::get('Performance');
        $candidate_bull_id = $performanceTable->find('all')->select(['sendout.candidate_id'])
                        ->join([
                            'sendout' => [
                                'table' => 'sendout',
                                'type' => 'RIGHT',
                                'conditions' => 'Performance.placement_id = sendout.placement_id'
                            ]
                        ])->where(['job_order_id' => $params['id'], 'Performance.placement_id is NOT NULL']);
        $candidate_bull_list = [];
        if (!empty($candidate_bull_id)) {
            $candidate_bull_ids = $candidate_bull_id->toArray();
            foreach ($candidate_bull_ids as $candidate_bull_id) {
                $candidate_bull_list[$candidate_bull_id['sendout']['candidate_id']] = $candidate_bull_id['sendout']['candidate_id'];
            }
            return $candidate_bull_list;
        }
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : update
     * Description   : To update the job details.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/joborder/?id=10
     * Request input : id => ID of the job.
      Params to be updated such as employmentType, title, etc,.
     * Request method: POST
     * Responses:
      1. success:{"changedEntityType":"JobOrder","changedEntityId":10,"changeType":"UPDATE","data":{"employmentType":"'temporary'","title":"'Android jr Developer'","startDate":1468605245}}
      2.fail:
     */

    public function update($params = null) {
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        if (isset($params['dateEnd']) && !empty($params['dateEnd']))
            $params['dateEnd'] = strtotime($params['dateEnd']);
        if (isset($params['startDate']) && !empty($params['startDate'])) {
            $params['startDate'] = strtotime($params['startDate']);
            if (isset($params['durationWeeks']) && !empty($params['durationWeeks'])) {
                $durationArr = [1 => "1 week", 2 => "2 weeks", 3 => "3 weeks", 4 => "1 month", 5 => "2 months", 6 => "3 months", 7 => "4 months", 8 => "5 months", 9 => "6 months", 10 => "7 months", 11 => "8 months", 12 => "9 months", 13 => "10 months", 14 => "11 months", 15 => "1 year", 16 => "2 years", 17 => "3 years"];
                $params['dateEnd'] = strtotime('+' . $durationArr[$params['durationWeeks']], $params['startDate']);
            }
        }

        $post_params = json_encode($params);
        $req_method = 'POST';
        if (isset($params['performance']) && !empty($params['performance']))
            $this->performance($params['performance']);
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if ($response && isset($params['dateEnd']) && !empty($params['dateEnd'])) {
            $this->updateTrigger($params);
        }
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : delete
     * Description   : To delete the specific job.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/joborder//?id=10
     * Request input : id => ID of the job.
      isDeleted = true
     * Request method: POST
     * Responses:
      1. success:{"changedEntityType":"JobOrder","changedEntityId":17,"changeType":"UPDATE","data":{"isDeleted":"true"}}
      2.fail:
     */

    public function delete($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['isDeleted'] = 'true';
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }
    
    /*
     * Action name   : close_job
     * Description   : To close the job 
     * Created by    : Balasuresh A
     * Updated by    : 
     * Created Date  : 13-04-2017
     * Updated Date  : __-__-____
     * URL           : /peoplecaddie-api/entities/joborder/job_close
     * Request input : id => ID of the job
     */
    public function jobclose($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['status'] = 'Closed';
        $params['isOpen'] = 'false';
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /**
     * Action name   : list
     * Description   : To list out the job order
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 30-08-2016
     * Updated Date  : 30-08-2016
     * URL           : /peoplecaddie-api/entities/joborder/all
     * Request input : id => ID of the job and candidate_id,role, platform
     */
    public function all() {
        $this->autoRender = false;
        $params = $this->request->data;
//        $params = array('role' => CANDIDATE_ROLE, 'candidate_id' => 2177, 'platform' => 'PC-Web');
        $this->BullhornConnection->BHConnect();
        $candidate_id = isset($params['candidate_id']) ? $params['candidate_id'] : "";
        $role = isset($params['role']) ? $params['role'] : "";
        if (empty($role) && !empty($candidate_id)) {
            $role = CANDIDATE_ROLE;
        };
        $per_page = isset($params['per_page']) ? $params['per_page'] : 10;
        $platform = isset($params['platform']) ? $params['platform'] : "";
        $page_start = (isset($params['page'])) ? ($params['page'] - 1) * $per_page : 0;
        $currentTimestamp = time();
        $cond = $hourlyRate = $empPreference = $states = '';
        $invitedJobs = [];
        $fields = 'id,employmentType,type,address,clientContact,clientCorporation,customInt1,durationWeeks,sendouts(id)'
                . ',status,isPublic,isDeleted,payRate,salary,salaryUnit,customInt2,customText1,customText2,customText3,customText4,customText5,'
                . 'customText6,customText8,correlatedCustomInt1,customFloat1,customFloat2,dateAdded,dateClosed,categories[50](id,name),dateEnd,startDate,title,description';
        if (isset($params['aminvited']) && !empty($params['aminvited'])) {
            $invitedJobs = $this->getInvitedJobsList($params['aminvited']);
        }
        if ($candidate_id && ($role == CANDIDATE_ROLE || $platform != 'PC-Web')) {
            $invisibleTable = TableRegistry::get('InvisibleJob');
            $getCandidate = $invisibleTable->find()->select()->where(['candidate_id' => $candidate_id])->toArray();
            list($hourlyRate, $empPreference,$states) = $this->getHourlyRateEmpPref($candidate_id);
            $cond = (!empty($getCandidate) && !empty($getCandidate[0]['joborder_ids'])) ? "-id:(" . str_replace(",", "+,+", $getCandidate[0]['joborder_ids']) . ")+AND+" : '';
            //if ($hourlyRate != 0)
            $cond .= '((customFloat1:[1+TO+' . $hourlyRate . ']+AND+customFloat2:[' . $hourlyRate . '+TO+200])+OR+customFloat2:[' . $hourlyRate . '+TO+200])+AND+';
            if (!empty($empPreference)) {
                // $getWords = $this->getWords($empPreference);
               // $empPreference = $this->myUrlEncode($empPreference); //for removing special characters in url
                $words = implode('"+OR+"', $empPreference);
                $validString = preg_replace('!\s+!', '+', $words); // remove space if exists
                $cond .= 'title:("' . $validString . '")+AND+';
            } else {
                $cond .= "title:(dummytitle)+AND+"; // to get empty data when Desired title is empty.
            }
            if(!empty($states)) {
                $cond .= 'address.state:"'. $states .'"+AND+';
            }
            $cond .= 'NOT+status:Placed+AND+NOT+status:Closed+AND+'; // to block the placed and closed jobs to contractors
        }
        $query = '';
        if (!empty($role)) {
            $control_company_ids = implode('+OR+', $this->get_owned_company($role, $candidate_id));
            if ($role == HIRINGMANAGER_ROLE) {
                $query = 'clientContact.id:' . $candidate_id . '+AND+clientCorporation.id:(' . $control_company_ids . ')+AND+';
            } else {
                $query = $control_company_ids ? 'clientCorporation.id:(' . $control_company_ids . ')+AND+' : '';
            }
            if ($role == CANDIDATE_ROLE) {
                $cond .= "isPublic:1+AND+customInt1:1+AND+";
            }
        }

        if (!empty($platform) && $platform == "PC-Web") {
            if (isset($params['user_type']) && strtolower($params['user_type']) == 'guest') {
                $cond .= "isPublic:1+AND+customInt1:1+AND+customInt2:0+AND+"; // do not get invited jobs to guest user
                $role=CANDIDATE_ROLE;//for restritcting job order start & end date
            }

            $url = $_SESSION['BH']['restURL'] . '/search/JobOrder?query=' . $cond . $query . 'isOpen:1+AND+isDeleted:false+AND+dateEnd:[' . $currentTimestamp . '+TO+*]&sort=-id'
                    . '&fields=' . $fields . '&BhRestToken=' . $_SESSION['BH']['restToken'] . '&count=100';
            $response = $this->job_all_request($url, $params);
            $response = $this->check_zero_index($response);

            if (!empty($response) && $response['total'] > $response['count'] && !empty($response['total'])) {

                $curl_data = array();
                $loopLimit = ceil($response['total'] / $response['count']);
                for ($x = 2; $x <= $loopLimit; $x++) {
                    $startcount = $response['count'] * ($x - 1);
                    $curl_data[$x]['post_data'] = json_encode($params);
                    $curl_data[$x]['url'] = $_SESSION['BH']['restURL'] . '/search/JobOrder?query=' . $cond . $query . 'isOpen:1+AND+isDeleted:false+AND+dateEnd:[' . $currentTimestamp . '+TO+*]&sort=-id&fields=' . $fields . '&BhRestToken=' . $_SESSION['BH']['restToken'] . '&start=' . $startcount . '&count=100';
                    $curl_data[$x]['req_method'] = 'GET';
                }

                $response1 = $this->BullhornCurl->multiRequest($curl_data);

                $arr_fetched = [];
                foreach ($response1 as $i => $json) {
                    $arr_fetched[$i] = json_decode($json, true);
                    $v1[] = $arr_fetched[$i]['data'];
                    $respons = $this->check_zero_index($arr_fetched[$i]);
                    $response['data'] = array_merge($response['data'], $respons['data']);
                }
            }
        } else {
            $url = $_SESSION['BH']['restURL'] . '/search/JobOrder?query=' . $cond . $query . 'isOpen:1+AND+isDeleted:false+AND+dateEnd:[' . $currentTimestamp . '+TO+*]&sort=-id'
                    . '&fields=' . $fields . '&BhRestToken=' . $_SESSION['BH']['restToken'] . '&start=' . $page_start . '&count=' . $per_page;

            $post_params = json_encode($params);
            $req_method = 'GET';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            $response['page_count'] = ceil($response['total'] / $per_page);
            $response['status'] = 1;
            if (isset($response['data']) && !empty($candidate_id)) {
                $i = 0;
                $invisible_job = $this->get_bookmarked_jobs($candidate_id);
                foreach ($response['data'] as $jobOrder) {
                    $response['data'][$i]['isBookmarked'] = $this->isJobBookmarked($candidate_id, $jobOrder['id'], $invisible_job);
//                if ($this->isVisible($candidate_id, $jobOrder['id'])) {
//                    unset($response['data'][$i]);
//                }
                    $i++;
                    continue;
                }
                // $response['data'] = array_values($response['data']);
            }
        }
        $response['data'] = array_values($response['data']); // Reindex
        $response['status'] = 1;
        $response['hidden_company'] = HIDDEN_COMPANY_TEXT; //for showing hidden company text
        // Remove Expired jobs
        $getAllCategories = $this->getCategory('', true); // it gives all categories
        $response = $this->checkjobDate($response, 'all', $invitedJobs, $role, $getAllCategories); //for fitering future start date and past end date
//        $i = 0;
//        foreach ($response['data'] as $jobOrder) {
//            $response['data'][$i]['dateEndFormatted'] = date("M d,Y", $response['data'][$i]['dateEnd']);
//            $response['data'][$i]['dateTodayTime'] = $currentTimestamp;
//            $response['data'][$i]['StartDateFormatted'] = date("M d,Y", $response['data'][$i]['startDate']);
//            $response['data'][$i]['dateTodayTimeFormatted'] = date("M d,Y", $currentTimestamp);
//            $response['data'][$i]['customCategory'] = isset($getAllCategories[trim($response['data'][$i]['customText8'])]) ? $getAllCategories[trim($response['data'][$i]['customText8'])] : ['id' => '', 'name' => ''];
//            if ((strtotime($response['data'][$i]['StartDateFormatted']) > strtotime($response['data'][$i]['dateTodayTimeFormatted'])) || (strtotime($response['data'][$i]['dateEndFormatted']) < strtotime($response['data'][$i]['dateTodayTimeFormatted']))) {
//                unset($response['data'][$i]); // remove expired jobs
//                $response['total'] = $response['total'] - 1;
//                $response['count'] = $response['count'] - 1;
//            }
//            if (isset($response['data'][$i]) && $role == CANDIDATE_ROLE && $response['data'][$i]['customInt2'] == 1 && (empty($invitedJobs) || !in_array($response['data'][$i]['id'], $invitedJobs))) {
//                unset($response['data'][$i]); // remove if this job is not invited to email user 
//                $response['total'] = $response['total'] - 1;
//                $response['count'] = $response['count'] - 1;
//            }
//            $i++;
//            continue;
//        }

        $response['data'] = array_values($response['data']); // Reindex
        /* To validate the jobs results based on the response and candidate id */
        $response = $this->validate_jobs_result($candidate_id, $response, $hourlyRate, $empPreference);
        echo json_encode($response);
    }

    /*
     * *************************************************************************************
     * Function name   : validate_jobs_result
     * Description     : To validate the jobs results based on the candidate id
     * Created Date    : 07-01-2017
     * Modified Date   :
     * Created By      : Balasuresh A
     * Modified By     :
     * *************************************************************************************
     */

    public function validate_jobs_result($candidate_id, $response, $hourlyRate, $empPreference) {

        if (!empty($candidate_id) && empty($response['total']) && empty($hourlyRate) && empty($empPreference)) {
            $response['status'] = 0;
            $response['message'] = 'Please set minimum Hourly Rate and Desired Title';
            return $response;
        } else if ((!empty($candidate_id)) && empty($response['total']) && !empty($hourlyRate) && !empty($empPreference)) {
            $response['status'] = 0;
            $response['message'] = 'No jobs currently available based on your selected preferences. You will be notified when a position match becomes available';
            return $response;
        } else if (empty($candidate_id) && empty($response['total'])) {
            $response['status'] = 0;
            $response['message'] = 'No Records found';
            return $response;
        } else {
            return $response;
        }
    }

    /*
     * Function: getInvitedJobsList
     * Description: get all jobs invited for this user
     * Input: email
     */

    public function getInvitedJobsList($email = null) {
        $invitedJobsTable = TableRegistry::get('InviteContractor');
        $getInvitedJobs = $invitedJobsTable->find('all')->select(['joborder_id'])->hydrate(false)->where(['emails LIKE' => "%" . $email . "%"])->toArray();
        $invitedJobs = [];
        foreach ($getInvitedJobs as $joborder) {
            $invitedJobs[] = $joborder['joborder_id'];
        }
        return $invitedJobs;
    }

    /**
     *
     * @param type $string
     * @return type array
     * Description to get array of words from a comma separated string
     */
    public function getWords($string = '') {
        $arrayForm = explode(',', strtolower($string)); // make an array of small letter words by comma
        $str = implode(' ', $arrayForm); // make a string with single space
        $validString = preg_replace('!\s+!', ' ', $str); // remove double space if exists
        $unique = array_values(array_unique(explode(" ", $validString))); // remove duplicate words
        return $unique;
    }

    /**
     * Description	: To check joborder is bookmarked
     * Created By	: Sivaraj V
     * Created on	: 12-09-2016
     * Updated on	: 12-09-2016
     */
    public function isJobBookmarked($candidate_id = null, $joborder_id = null, $invisbile_data = []) {
//        $bookmarkTable = TableRegistry::get('Bookmarks');
//        $getCandidate = $bookmarkTable->find()->select()->where(['candidate_id' => $candidate_id])->toArray();

        if (empty($invisbile_data)) {
            return 0; // false
        }
        if (!empty($invisbile_data)) {
            $joborders = explode(",", $invisbile_data[0]["joborder_id"]);
            $joborders_ar = array_flip($joborders);
            if (isset($joborders_ar[$joborder_id])) {
//            if (in_array($joborder_id, $joborders)) {
                return 1; // true
            } else {
                return 0; // false
            }
        }
    }

    /**
     * Description	: To optimize candidate bookmark
     * Created By	: Akilan
     * Created on	: 16-12-2016
     * Updated on	: 16-12-2016
     */
    function get_bookmarked_jobs($candidate_id) {
        $bookmarkTable = TableRegistry::get('Bookmarks');
        $getCandidate = $bookmarkTable->find()->select()->where(['candidate_id' => $candidate_id]);
        if (!empty($getCandidate))
            return $getCandidate->toArray();
    }

    /**
     * Description	: To add/update joborder and make it invisible for a candidate
     * Created By	: Sivaraj V
     * Created on	: 20-09-2016
     * Updated on	: 20-09-2016
     * URL          :   entities/joborder/invisible
     * Request input: candidate_id => ID of the candidate and joborder_id
     */
    public function invisible() {
        $this->autoRender = false;
        $params = $this->request->data;
        $invisibleTable = TableRegistry::get('InvisibleJob');
        if (!isset($params['candidate_id']) || !isset($params['joborder_id'])) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "Please make sure candidate and joborder ids are passed"
                    ]
            );
            exit;
        }
        $invisibleTable = TableRegistry::get('InvisibleJob');
        echo $result = $invisibleTable->make_invisible($params);
    }

//    public function search() {
//        $this->autoRender = false;
//        $params = $this->request->data;
//        $search_txt=$params['search_text']='test';
//        $this->BullhornConnection->BHConnect();
//        $url = $_SESSION['BH']['restURL'] . '/search/JobOrder?&query =title:%'.$search_txt.'%&BhRestToken=' . $_SESSION['BH']['restToken'];
//        $params['isDeleted'] = 'true';
//        $post_params = json_encode($params);
//        $req_method = 'POST';
//        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//
//    }

    /**
     * Description	: To check joborder visible for a candidate
     * Created By	: Sivaraj V
     * Created on	: 20-09-2016
     * Updated on	: 20-09-2016
     * Request input: candidate_id => ID of the candidate and joborder_id
     */
    public function isVisible($candidate_id = null, $joborder_id = null) {
        $this->autoRender = false;
        $invisibleTable = TableRegistry::get('InvisibleJob');
        $getCandidate = $invisibleTable->find()->select()->where(['candidate_id' => $candidate_id])->toArray();

        if (empty($getCandidate)) {
            return 0; // false
        }
        if (!empty($getCandidate)) {
            $joborders = explode(",", $getCandidate[0]["joborder_ids"]);
            if (in_array($joborder_id, $joborders)) {
                return 1; // true
            } else {
                return 0; // false
            }
        }
    }

    /**
     * Description	: To get the custom sendout information from pc server for a job applied by a candidate
     * Created By	: Sivaraj V
     * Created on	: 21-09-2016
     * Updated on	: 21-09-2016
     * Request input: candidate_id => ID of the candidate and joborder_id
     */
    public function getCustomSendoutInfo($candidate_id = null, $joborder_id = null, $sendout_id = null) {
        $this->autoRender = false;
        $csendoutTable = TableRegistry::get('Sendout');
        $cuserTable = TableRegistry::get('Users');
        $getSendout = $csendoutTable->find()->select()
                ->where(['candidate_id' => $candidate_id, 'joborder_id' => $joborder_id, 'sendout_id' => $sendout_id])
                ->toArray();
        $user = $cuserTable->find()->select(['firstName', 'lastName'])
                ->where(['bullhorn_entity_id' => $candidate_id])
                ->toArray();
        if (empty($getSendout)) {
            $response = [
                'status' => 0,
                'message' => 'No custom sendout information available',
            ];
            if (!empty($user)) {
                $response['customSendoutInfo'] = [
                    'firstName' => $user[0]['firstName'],
                    'lastName' => $user[0]['lastName'],
                    'can_edit_by_admin' => 1 // this is for web admin who can edit job application
                ];
            }
            return $response;
        }
        if (!empty($getSendout)) {
            $files = [];
            if (!empty($getSendout[0]['attachments'])) {
                $files = TableRegistry::get('Attachment')->find('all')->select(['file_id', 'filename', 'filepath'])
                        ->where(['file_id IN' => explode(',', $getSendout[0]['attachments'])])
                        ->toArray();
            }
            $getSendout[0]['attachments'] = $files;
            //$getSendout[0]['desired_hourly_rate_to'] = $csendoutTable->biddrate_calculate($getSendout[0]['desired_hourly_rate_to']);
            
            /* get hiring manager name */
            if(!empty($getSendout[0]['interviewer_id'])) {
                $getSendout[0]['hiring_manager_name'] = $cuserTable->full_name($getSendout[0]['interviewer_id']);
                /* get hiring manager phone number */
                $hmPhone = $cuserTable->find()->select(['phone'])
                            ->where(['bullhorn_entity_id' => $getSendout[0]['interviewer_id']])
                            ->hydrate(false)->first();
                if(!empty($hmPhone)) {
                    $getSendout[0]['hiring_manager_phone'] = $hmPhone['phone'];
                }
            }
            
            /* get sales representative name and phone number, if interviewer id is null */
            if(empty($getSendout[0]['interviewer_id'])) {
                if(!empty($getSendout[0]['placement_coordinator_id'])) {
                    $getSendout[0]['hiring_manager_name'] = $cuserTable->full_name($getSendout[0]['placement_coordinator_id']);
                    /* get hiring manager phone number */
                     $hmPhone = $cuserTable->find()->select(['phone'])
                            ->where(['bullhorn_entity_id' => $getSendout[0]['placement_coordinator_id']])
                            ->hydrate(false)->first();
                     $getSendout[0]['hiring_manager_phone'] = !empty($hmPhone) ? $hmPhone['phone'] : "";
                }
            }

            if (!empty($user)) {
                $getSendout[0]['firstName'] = $user[0]['firstName'];
                $getSendout[0]['lastName'] = $user[0]['lastName'];
            }
            $getSendout[0]['can_edit_by_admin'] = ($getSendout[0]['selected_for_client'] == 0) ? 1 : 0; // this is for web admin who can edit job application if 1
            return [
                'status' => 1,
                'message' => 'Custom sendout information available',
                'customSendoutInfo' => $getSendout[0]
            ];
        }
    }

    /**
     * Description	: To save candidate performance detail
     * Created By	: Akilan
     * Created on	: 03-10-2016
     * Updated on	: 03-10-2016
     * Request input    : candidate save detail process
     */
    public function performance($params = null) {
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        if(!isset($params['placement_id']) && empty($params['placement_id'])) {
            $sendout_tbl = TableRegistry::get('Sendout');
            $placement_details = $sendout_tbl->find()->select(['placement_id'])->where(['candidate_id' => $params['candidate_bullhorn_id'],'joborder_id' => $params['job_order_id']])->first();
            if(!empty($placement_details)) {
                $params['placement_id'] = $placement_details['placement_id'];
            }
        }
        $dat = $this->candidate_save($params);
        $old_perform_record = $this->candidate_performance_data($params['candidate_bullhorn_id']);
        if (!empty($old_perform_record)) {
            $grade = array();
            $standard = array();
            $duration_ar = array();
            foreach ($old_perform_record as $rcd_sgl) {
                $durtn = $rcd_sgl['duration'];
                $standard[] = $this->calculate_standard($rcd_sgl);
                $duration_ar[] = $durtn;
            }
            $update_data['rating'] = round((array_sum($standard) / array_sum($duration_ar)) / 3, 1);
            $this->user_update($params['candidate_bullhorn_id'], $update_data, 'bullhorn_id');
            if (isset($params['candidate_bullhorn_id']) && isset($params['job_order_id'])) {
                $performanceTable = TableRegistry::get('Performance');
                $performance = $performanceTable->find()->select()->where(['job_order_id' => $params['job_order_id'], 'candidate_bullhorn_id' => $params['candidate_bullhorn_id'], 'placement_id IS NOT' => NULL])->toArray();
                if (!empty($performance)) { // allow placement for the first time or only once
                    $sendout_tbl = TableRegistry::get('Sendout');
                    $sendout_data = $sendout_tbl->get_placement_sendoutid($params['job_order_id'], $params['placement_id']);
                    $sendout_tbl->query()->update()->set(['application_progress' => ASSIGNMENT_CLOSED])
                                ->where(['placement_id' => $params['placement_id']])->execute();
//                    $response = $this->placement(array('candidate' => array('id' => $params['candidate_bullhorn_id']),
//                        'jobOrder' => array('id' => $params['job_order_id'])));
                    if (!empty($sendout_data)) {
                        $sendout_ar = $sendout_data->toArray();
                        $this->stop_notification_data($sendout_ar);
                        $performanceTable->query()->update()->set(['placement_id' => $sendout_ar['placement_id']])
                                ->where(['job_order_id' => $params['job_order_id'], 'candidate_bullhorn_id' => $params['candidate_bullhorn_id']])
                                ->execute();
                    }
                }
            }
            return;
        }
    }

    /**
     * Function    : stop_notification_data
     * Description : To stop notification when candidate grade was updated.
     * Created By  : Akilan
     * Created on  : 30-12-2016    *
     *
     */
    public function stop_notification_data($sendout_ar) {
        if (!is_null($sendout_ar['placement_id']) && !empty($sendout_ar['placement_id'])) {
            $where_condition = array('placement_id' => $sendout_ar['placement_id']);
            TableRegistry::get('Notifications')->stop_further_sendout_alert($where_condition);
        }
    }

    /**
     * Description	: To save candidate performance detail
     * Created By	: Akilan
     * Created on	: 03-10-2016
     * Updated on	: 03-10-2016
     * Request input    : candidate save detail process
     */
    public function candidate_save($params) {
        $userTable = TableRegistry::get('Users');
        $start_date = $this->get_joborder_fields($params['job_order_id'], 'startDate');
        $performanceTable = TableRegistry::get('Performance');
        $performance = $performanceTable->newEntity();
        $performanceTable->patchEntity($performance, $params);
        $performance->grade = $this->calculate_grade($params);
        $performance->start_date = !empty($start_date) ? date("Y-m-d", $start_date) : date('Y-m-d');
        $performanceTable->save($performance);
    }

    /**
     * Description	: To get the custom sendout information from pc server for a job applied by a candidate
     * Created By	: Akilan
     * Created on	: 03-10-2016
     * Updated on	: 03-10-2016
     * Request input    : candidate bullhorn id data
     */
    public function candidate_performance_data($candte_bullhorn_id) {
        $performanceTable = TableRegistry::get('Performance');
        $old_perform_record = $performanceTable->find('all')
                ->where(['candidate_bullhorn_id' => $candte_bullhorn_id,'source_id IS NULL'])
                ->toArray();
        return $old_perform_record;
    }

    /*
     * Reference Performace Candidate Add
     */
    
    public function reference_performance_data($candte_bullhorn_id) {
        $performanceTable = TableRegistry::get('Performance');
        $old_perform_record = $performanceTable->find('all')
                ->where(['candidate_bullhorn_id' => $candte_bullhorn_id])
                ->toArray();
        return $old_perform_record;
    }
    /**
     * Description	: To calculate standard based on performance
     * Created By	: Akilan
     * Created on	: 03-10-2016
     * Updated on	: 03-10-2016
     * Request input    : candidate performance/timely/professional
     */
    public function calculate_standard($params) {
        return array_sum(array($params['performance'], $params['timeliness'], $params['professionalism'])) * $params['duration'];
    }

    /**
     * Description	: To calculate grade based on performance
     * Created By	: Akilan
     * Created on	: 12-11-2016
     * Updated on	: 12-11-2016
     * Request input    : candidate performance/timely/professional
     */
    public function calculate_grade($params) {
        return array_sum(array($params['performance'], $params['timeliness'], $params['professionalism'])) / 3;
    }

//    public function placement($post_params) {
//        $url = $_SESSION['BH']['restURL'] . '/entity/Placement?BhRestToken=' . $_SESSION['BH']['restToken'];
//        $params['dateAdded'] = time();
//        $params['dateBegin'] = time();
//        $post_params = json_encode($params);
//        $req_method = 'PUT';
//        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//        return;
//    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : bhadd
     * Description   : to add or remove skills for a joborder.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 12-10-2016
     * Updated Date  : 12-10-2016
     * URL           : /entities/jobOrder/skills_add?
     * Request input : joborder_id, old_skill_ids, new_skill_ids
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function skills_add() {
        $this->autoRender = false;
        $params = $this->request->data;
        $message = [];
        if (!isset($params['joborder_id'])) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "joborder id is required!"
                    ]
            );
            exit;
        }
        if (isset($params['old_skill_ids']) && is_array($params['old_skill_ids'])) {
            $skills = implode(',', $params['old_skill_ids']);
            if ($this->update_job_skills($params['joborder_id'], $skills, 'DELETE'))
                $message[] = 'Deleted skill id:' . $skills;
        }
        if (isset($params['old_category_ids']) && is_array($params['old_category_ids'])) {
            $categories = implode(',', $params['old_category_ids']);
            if ($this->update_job_categories($params['joborder_id'], $categories, 'DELETE'))
                $message[] = 'Deleted category id:' . $categories;
        }
        if (isset($params['new_category_ids']) && is_array($params['new_category_ids'])) {
            $categories = implode(',', $params['new_category_ids']);
            if ($this->update_job_categories($params['joborder_id'], $categories, 'PUT'))
                $message[] = 'Added category id:' . $categories;
        }
        if (isset($params['new_skill_ids']) && is_array($params['new_skill_ids'])) {
            $skills = implode(',', $params['new_skill_ids']);
            if ($this->update_job_skills($params['joborder_id'], $skills, 'PUT'))
                $message[] = 'Added skill id:' . $skills;
            $this->job_candidate_match($params['joborder_id']);
        }

        if (!empty($message)) {
            $result = [
                'status' => 1,
                'message' => $message
            ];
            echo json_encode($result);
        } else {
            $result = [
                'status' => 0,
                'message' => $message
            ];
            echo json_encode($result);
        }
    }

    /*     * * * * * * * * *  * * * *
     * Action Name   : job_candidate_match
     * Description   : to add a joborder id to send for all matched contractor notifications.
     * Created by    : Sivaraj V
     * Created by    : 23-11-2016
     */

    public function job_candidate_match($joborder_id = null) {
        //$this->autoRender = false;
        // $joborder_id = 1072;
        $jobmatch = TableRegistry::get('JobCandidateMatch');
        $getJobOrder = $jobmatch->find('all')->select(['id'])->where(['joborder_id' => $joborder_id])->toArray();
        if (empty($getJobOrder)) { // store joborder only once
            $job = [
                'joborder_id' => $joborder_id,
                'status' => 1,
            ];
            $data = $jobmatch->newEntity($job);
            $jobmatch->save($data);
        }
    }

    public function update_job_skills($id = '', $skills = '', $req_method = '') {
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $id . '/skills/' . $skills . '?BhRestToken=' . $_SESSION['BH']['restToken'];

        $post_params = json_encode([]);
        //$req_method = 'DELETE'; or PUT
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $result = [];
        if (isset($response['errors'])) {
            $result = [
                'status' => 0,
                'message' => $response
            ];
            echo json_encode($result);
            exit;
        } else {
            return 1;
        }
    }

    public function update_job_categories($id = '', $categories = '', $req_method = '') {
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $id . '/categories/' . $categories . '?BhRestToken=' . $_SESSION['BH']['restToken'];

        $post_params = json_encode([]);
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        $result = [];
        if (isset($response['errors'])) {
            $result = [
                'status' => 0,
                'message' => $response
            ];
            echo json_encode($result);
            exit;
        } else {
            return 1;
        }
    }

    /*
     * Action Name   : invite_contractors
     * Description   : to invite contractors for a job order manually by an admin
     * Created by    : Sivaraj V
     * Created on    : 17-10-2016
     * Request Params: joborder_id, emails in array
     */

    public function invite_contractors() {
        $this->autoRender = false;
        $params = $this->request->data;
        $canSave = false;
        $isSentMails = false;
        //$params = ['joborder_id' => 779,'emails' => ['add'=>['balasureshfsp@gmail.com']]];
        $inviteContractorsTable = TableRegistry::get('InviteContractor');
        if (!isset($params['joborder_id'])) {
            echo json_encode([
                'status' => 0,
                'message' => 'Joborder id is required'
            ]);
            exit;
        }
        $getInvites = $inviteContractorsTable->find()->select(['id', 'joborder_id', 'emails'])
                ->where(['joborder_id' => $params['joborder_id']])
                ->toArray();
        if (!empty($getInvites)) {
            if ((isset($params['emails']['add']) && is_array($params['emails']['add'])) || (isset($params['emails']['delete']) && is_array($params['emails']['delete']))) {
                $old_emails = explode(',', $getInvites[0]['emails']);
                if (isset($params['emails']['delete']) && !empty($params['emails']['delete'])) {
                    $old_emails = array_diff($old_emails, $params['emails']['delete']); // Deleted
                }
                if (isset($params['emails']['add']) && !empty($params['emails']['add'])) {
                    $uniqueMails = array_unique(array_merge($old_emails, $params['emails']['add']));
                    $isSentMails = true;
                } else {
                    $uniqueMails = array_unique($old_emails);
                }
                $emails = implode(',', $uniqueMails);
                $invite = $inviteContractorsTable->get($getInvites[0]['id']);
                $invite->id = $getInvites[0]['id'];
                $invite->joborder_id = $params['joborder_id'];
                $invite->emails = $emails; // conveted to string from array
                $this->invite_contractors_save($inviteContractorsTable, $invite, $emails,$params['joborder_id']);
            } else {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Emails must be in array format'
                ]);
                exit;
            }
        } else {
            if (isset($params['emails']['add']) && is_array($params['emails']['add'])) {
                $emails = implode(',', $params['emails']['add']);
                $params['emails'] = $emails; // conveted to string from array
                $invite = $inviteContractorsTable->newEntity($params);
                $this->invite_contractors_save($inviteContractorsTable, $invite,$emails,$params['joborder_id']); // to send mail, new contractor and not in the PeopleCaddie system
            } else {
                echo json_encode([
                    'status' => 0,
                    'message' => 'Emails must be in array format'
                ]);
                exit;
            }
        }
    }

    /*
     * Action Name   : invite_contractors_save
     * Description   : to save invited contractors into pc server for a job order
     * Created by    : Sivaraj V
     * Created on    : 10-11-2016
     */

    public function invite_contractors_save($inviteContractorsTable = null, $invite = null, $emails = null, $joborder_id = null) {
        if ($isEmpty = $inviteContractorsTable->save($invite)) {
            if (empty($isEmpty->emails) || $isEmpty->emails == '') {
                $inviteContractorsTable->delete($inviteContractorsTable->get($isEmpty->id));
                echo json_encode([
                    'status' => 0,
                    'message' => 'Invite more contractors!'
                ]);
                exit;
            } else {
                $emails = explode(',', $emails);
                $getUsers = TableRegistry::get('User')->find('all')->select(['id', 'firstName', 'lastName', 'email', 'phone', 'bullhorn_entity_id'])->where(['email IN' => $emails, 'role' => CANDIDATE_ROLE])->hydrate(false)->toArray();
                $existingInvitedContractors = [];
                $invitedContrators = [];
                $existingPCContractors = [];
                if (!empty($getUsers)) {
                    $existingPCContractors = array_column($getUsers, 'email');
                    foreach ($getUsers as $contractor) {
                        $existingInvitedContractors[] = $contractor['email'];
                        $invitedContrators[] = $contractor;
                    }
                }
                $newPCContractors = array_diff($emails,$existingPCContractors);
                
                if (!empty($existingPCContractors) && !empty($joborder_id)) { // to send mail, contractor in the PeopleCaddie system
                    $this->invite_contractor_mail($existingPCContractors,$joborder_id);
                }
                if(!empty($newPCContractors) && !empty($joborder_id)) { // to send mail, new contractor and not in the PeopleCaddie system
                    $this->invite_new_contractor_mail($newPCContractors,$joborder_id);
                }

                $newInvitedContractors = array_diff($emails, $existingInvitedContractors); 
                
                if (!empty($newInvitedContractors)) {
                    foreach ($newInvitedContractors as $newinvite) {
                        $invitedContrators[] = [
                            'id' => null,
                            'firstName' => null,
                            'lastName' => null,
                            'email' => $newinvite,
                            'phone' => null,
                            'bullhorn_entity_id' => null
                        ];
                    }
                }
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "Success!",
                            'data' => $invitedContrators
                        ]
                );
                exit;
            }
        } else {
            echo json_encode([
                'status' => 0,
                'message' => 'Unable to invite!'
            ]);
            exit;
        }
    }

    /*
     * Action Name   : invite_contractor_mail
     * Description   : to send mail for invited contractors for a job order manually by an admin
     * Created by    : Sivaraj V
     * Created on    : 10-11-2016
     */

    public function invite_contractor_mail($emails = [],$joborder_id = null) {
        $data = $this->getTitleCompany($joborder_id);
        if(!empty($data)){
            $jobTitle = $data['job_title'];
            foreach ($emails as $emailData) {
                if (filter_var($emailData, FILTER_VALIDATE_EMAIL)) {
                    $jobLink = Configure::read('marketing_server_address') . '/job-position.php?job_ticket=' . $joborder_id;
                    $var = ['subject' => 'You have been invited for a job by a recruiter!', 'job_id' => $joborder_id, 'title' => $jobTitle, 'verifyLink' => $jobLink];
                    $email = new Email();
                    $email->template('invite_contractor', 'user')
                            ->emailFormat('html')
                            ->viewVars(['var' => $var])
                            ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                            ->to($emailData)
                            ->subject('You have been invited for a job by a recruiter!')
                            ->send();
                }
            }
        }
    }
    
    /*
     * Action Name   : invite_new_contractor_mail
     * Description   : to send mail for invited contractors for a job order manually by an admin
     * Created by    : Balasuresh A
     * Created on    : 22-05-2017
     */

    public function invite_new_contractor_mail($emails = [],$joborder_id = null) {
        $data = $this->getTitleCompany($joborder_id);
        if(!empty($data)){
            $jobTitle = $data['job_title'];
            foreach ($emails as $emailData) {
                if (filter_var($emailData, FILTER_VALIDATE_EMAIL)) {
                    $jobLink = Configure::read('marketing_server_address') . '/job-position.php?job_ticket=' . $joborder_id;
                    $var = ['subject' => 'You have been invited for a job by a recruiter!', 'job_id' => $joborder_id, 'title' => $jobTitle, 'verifyLink' => $jobLink, 'androidLink' => '#', 'iosLink' => '#','status' => 1];
                    $email = new Email();
                    $email->template('invite_contractor', 'user')
                            ->emailFormat('html')
                            ->viewVars(['var' => $var])
                            ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                            ->to($emailData)
                            ->subject('You have been invited for a job by a recruiter!')
                            ->send();
                }
            }
        }
    }

    /*
     * Action Name   : getTitleCompany
     * Description   : to get the job title by passing job order id.
     * Created by    : Balasuresh A
     * Created on    : 22-05-2017
     */
    public function getTitleCompany($joborder_id) {
        $this->BullhornConnection->BHConnect();
        $fields = 'title';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $joborder_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($joborder_id);
        $req_method = 'GET';
        $res = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($res['data'])) {
            return ['job_title' => $res['data']['title']];
        } else {
            return [];
        }
    }
    
    /*
     * Action Name   : filter
     * Description   : to invite contractors for a job order manually by an admin
     * Created by    : Sivaraj V
     * Created on    : 17-10-2016
     * Request Params: keyword,city,isBookmarked =>true/false,category id in array format,aminvited => email
     */

    public function filter() {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $query = [];
        $candidate_id = isset($params['candidate_id']) ? $params['candidate_id'] : "";
        $invitedJobs = [];
        $currentTimestamp = time();
        if (isset($params['aminvited']) && !empty($params['aminvited'])) {
            $invitedJobs = $this->getInvitedJobsList($params['aminvited']);
        }
        if ($candidate_id != "") {
            $invisibleTable = TableRegistry::get('InvisibleJob');
            $getCandidate = $invisibleTable->find()->select()->where(['candidate_id' => $candidate_id])->toArray();
            list($hourlyRate, $empPreference,$states) = $this->getHourlyRateEmpPref($candidate_id);
            $query[] = (!empty($getCandidate) && !empty($getCandidate[0]['joborder_ids'])) ? "-id:(" . str_replace(",", "+,+", $getCandidate[0]['joborder_ids']) . ")" : "";
            $query[] = '((customFloat1:[1+TO+' . $hourlyRate . ']+AND+customFloat2:[' . $hourlyRate . '+TO+200])+OR+customFloat2:[' . $hourlyRate . '+TO+200])';
            if (!empty($empPreference)) {
                $words = implode('"+OR+"', $empPreference);
                $validString = preg_replace('!\s+!', '+', $words); // remove space if exists
                $query[] = 'title:("' . $validString . '")';
            } else {
                 $query[] = "title:(dummytitle)"; // to get empty data when Desired title is empty.
            }
            if(!empty($states)) {
                $query[]= 'address.state:"'. $states .'"';
            }
            $company_ids = $this->get_owned_company(CANDIDATE_ROLE, $candidate_id);
            if (!empty($company_ids)) {
                $control_company_ids = implode('+OR+', $company_ids);
                $query[] = 'clientCorporation.id:(' . $control_company_ids . ')';
            }
        }
        $words = "";
        $isBookmarkScreen = false;
        $per_page = 10;
        $page_start = (isset($params['page'])) ? ($params['page'] - 1) * $per_page : 0;
        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $params['keyword'] = trim($params['keyword']);
            if (str_word_count($params['keyword']) == 1) {
                $words = $params['keyword'];
            } else {
                $words = implode('*+', str_word_count($params['keyword'], 1));
            }
            $query[] = "title:(" . $words . "*)";
        }
        if (isset($params['city']) && !empty(trim($params['city']))) {
            $query[] = "customText1:" . trim($params['city']);
        }
        if (isset($params['categoryId']) && !empty($params['categoryId'])) {
//            $query[] = "categories.id:(" . $params['categoryId'] . ")";
            $query[] = "customText8:" . $params['categoryId'];
        }
        if (isset($params['PostingDate'])) {
            $sort = "sort=-dateAdded";
        } else {
            $sort = "sort=-id";
        }
        if ($candidate_id != "" && isset($params['hourlyRateLow']) && !empty($params['hourlyRateLow'])) { // candidate rate per hour
            $query[] = "customFloat1:[" . $params['hourlyRateLow'] . "+TO+*]";
        }
        if (isset($params['ScreenStatus']) && $params['ScreenStatus'] == 'Bookmark') {
            $isBookmarkScreen = true;
        }

        $matchpercent = (isset($params['PercentageMatched'])) ? 1 : 0;
        $hiringgrade = (isset($params['HiringGrade'])) ? 1 : 0;
        if ($hiringgrade) {
            $hiringMangerGrades = $this->getHiringManagerGrade(); //pr($hiringMangerGrades);
            if (!empty($hiringMangerGrades)) {
                $query[] = "clientContact.id:(" . implode('+OR+', array_keys($hiringMangerGrades)) . ")";
            }
        }
        $queryFilter = implode('+AND+', array_filter($query));
        $queryFilter = !empty($query) ? '+AND+(' . $queryFilter . ')' : '';
        $url = $_SESSION['BH']['restURL'] . '/search/JobOrder?query=isOpen:1+AND+isPublic:1+AND+customInt1:1+AND+isDeleted:false+AND+NOT+status:Placed+AND+NOT+status:Closed' . $queryFilter . '&' . $sort . '&fields=id,'
                . 'employmentType,type,address,clientContact,clientCorporation,customInt1,sendouts(id)'
                . ',status,isDeleted,payRate,salary,salaryUnit,correlatedCustomInt1,customInt2,customText1,customText2,customText3,customText4,customText5,'
                . 'customText6,customFloat1,customFloat2,dateAdded,startDate,dateClosed,dateEnd,title,description,skills[100](id,name)&BhRestToken=' . $_SESSION['BH']['restToken'] . '&start=' . $page_start . '&count=' . $per_page;
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        //$response['page_count'] = ceil($response['total'] / $per_page);
        if (isset($response['data']) && !empty($candidate_id)) {
            $response['page_count'] = ceil($response['total'] / $per_page);
            $i = 0;
            $candidateSkills = $this->getCandidateSkills($candidate_id);
            $candidateMatchPercent = [];
            $hiringMangerGradesToSort = [];
            $invisible_job = $this->get_bookmarked_jobs($candidate_id);
            foreach ($response['data'] as $jobOrder) {
                $status = $this->isJobBookmarked($candidate_id, $jobOrder['id'], $invisible_job);
                $response['data'][$i]['isBookmarked'] = $status;
                if ($isBookmarkScreen && $status == 0) { // show only bookmarked jobs in bookmark screen. So removed unbookmarked jobs here
                    unset($response['data'][$i]);
                }
                if (isset($response['data'][$i]) && $response['data'][$i]['customInt2'] == 1 && (empty($invitedJobs) || !in_array($response['data'][$i]['id'], $invitedJobs))) {
                    unset($response['data'][$i]); // remove if this job is not invited to email user 
                }
                // Calculate Match %
                if (isset($response['data'][$i]) && $matchpercent) {
                    $skills = [];
                    if (!empty($jobOrder['skills']['data'])) {
                        foreach ($jobOrder['skills']['data'] as $skill) {
                            $skills[] = $skill['name'];
                        }
                    }
                    $percent = $this->getMatchPercent($candidateSkills, $skills);
                    $response['data'][$i]['candidateMatchPercent'] = $percent;
                    $candidateMatchPercent[$i] = $percent;
                }
                if (isset($response['data'][$i]) && $hiringgrade) {
                    if (isset($hiringMangerGrades[$response['data'][$i]['clientContact']['id']])) {
                        $response['data'][$i]['hiringManagerGrade'] = $hiringMangerGrades[$response['data'][$i]['clientContact']['id']];
                        $hiringMangerGradesToSort[$i] = $hiringMangerGrades[$response['data'][$i]['clientContact']['id']];
                    } else {
                        $hiringMangerGradesToSort[$i] = 0;
                    }
                }

//                if ($this->isVisible($candidate_id, $jobOrder['id'])) {
//                    unset($response['data'][$i]);
//                }
                $i++;
                continue;
            }

            if ($matchpercent) {
                array_multisort($candidateMatchPercent, SORT_DESC, $response['data']);
            }
            if ($hiringgrade) {
                array_multisort($hiringMangerGradesToSort, SORT_DESC, $response['data']);
            }
            if ($matchpercent && $hiringgrade) {
                array_multisort($candidateMatchPercent, SORT_DESC, $hiringMangerGradesToSort, SORT_DESC, $response['data']);
            }
            $response['data'] = array_values($response['data']);
            $response = $this->checkjobDate($response, 'filter', $invitedJobs,CANDIDATE_ROLE); //for filter future start date and past enddate
            
//            foreach ($response['data'] as $jobOrder) {
//                $response['data'][$i]['dateEndFormatted'] = date("M d,Y", $response['data'][$i]['dateEnd']);
//                $response['data'][$i]['dateTodayTime'] = $currentTimestamp;
//                $response['data'][$i]['dateTodayTimeFormatted'] = date("M d,Y", $currentTimestamp);
//                if (strtotime($response['data'][$i]['dateEndFormatted']) < strtotime($response['data'][$i]['dateTodayTimeFormatted'])) {
//                    unset($response['data'][$i]); // remove expired jobs
//                    $response['total'] = $response['total'] - 1;
//                    $response['count'] = $response['count'] - 1;
//                }
//                if (isset($response['data'][$i]) && $response['data'][$i]['customInt2'] == 1 && (empty($invitedJobs) || !in_array($response['data'][$i]['id'], $invitedJobs))) {
//                    unset($response['data'][$i]); // remove if this job is not invited to email user 
//                    $response['total'] = $response['total'] - 1;
//                    $response['count'] = $response['count'] - 1;
//                }
//                $i++;
//                continue;
//            }
            $response['data'] = array_values($response['data']);
        }
        if (isset($response['data']) && !empty($response['data'])) {
            $response['status'] = 1;
            $response['hidden_company'] = HIDDEN_COMPANY_TEXT; //for showing hidden company text
            $response['message'] = "We found some records matching for your search";
        } else {
            $response['status'] = 0;
            $response['message'] = "No records found";
        }
        echo json_encode($response);
    }

    /*     * **********************************************************************
     *
     * Action: getJobViewHMGrade
     * Description: For showing HM grade for job view detail
     * Created by: Akilan 
     * Created on: 25-01-2016
     * 
     * ************************************************************************ */

    public function getJobViewHMGrade($rated_user_bullhorn_id = null, $job_order_id = null) {
        $perfTable = TableRegistry::get('Performance');
        $perfAll = $perfTable->find('all');
        $hmGrade = 0;
        if ($rated_user_bullhorn_id != null) {
            $performance = $perfAll->select(['grade' => $perfAll->func()->sum('grade'), 'job_order_id', 'count' => $perfAll->func()->count('grade'), 'avg' => $perfAll->func()->avg('grade')])->where(['rated_user_bullhorn_id' => $rated_user_bullhorn_id])->group('rated_user_bullhorn_id')->toArray();
        } else {
            $performance = $perfAll->select(['grade' => $perfAll->func()->sum('grade'), 'job_order_id', 'count' => $perfAll->func()->count('grade'), 'avg' => $perfAll->func()->avg('grade')])->where(['job_order_id' => $job_order_id])->group('job_order_id')->toArray();
        }
        $joborders = [];
        if (!empty($performance)) {
            if (isset($performance[0]['avg']))
                $hmGrade = round($performance[0]['avg'], 1);
        }
        return $hmGrade;
    }

    /*
     * Action: getHiringManagerGrade
     * Description: Get hiring manager grade using perfomance table. Gets all joborder and its average grade from DB and find hiring manager from bullhorn using joborder and use both to find average grade of all jobs created by him.
     * Created by: Sivaraj V
     * Created on: 05-12-2016
     */

    public function getHiringManagerGrade($joborder_id = null) {
        $perfTable = TableRegistry::get('Performance');
        $perfAll = $perfTable->find('all');
        if ($joborder_id != null) {
            $performance = $perfAll->select(['grade' => $perfAll->func()->sum('grade'),'rated_user_bullhorn_id', 'job_order_id', 'count' => $perfAll->func()->count('grade'), 'avg' => $perfAll->func()->avg('grade')])->where(['job_order_id' => $joborder_id])->group('job_order_id')->toArray();
        } else {
            $performance = $perfAll->select(['grade' => $perfAll->func()->sum('grade'),'rated_user_bullhorn_id', 'job_order_id', 'count' => $perfAll->func()->count('grade'), 'avg' => $perfAll->func()->avg('grade')])->group('rated_user_bullhorn_id')->toArray();
        }
       
        $joborders = $finalHiringGrade= [];
        if (!empty($performance)) {
            foreach ($performance as $job) {
               // if (!empty($job['job_order_id'])) {
                    //$joborders[$job['job_order_id']] = round($job['avg'], 1);
                    $finalHiringGrade[$job['rated_user_bullhorn_id']] = round($job['avg'], 1);
                //}
            }
        }
//        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . implode(',', array_keys($joborders)) . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,title,clientContact';
//        $post_params = json_encode([]);
//        $req_method = 'GET';
//        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//        //pr($response);
//        $clientContactGrade = [];
//        $finalHiringGrade = [];
//        $response = $this->check_zero_index($response);
//        if (isset($response['data'])) {
////            foreach ($response['data'] as $job_dat) {
////                if (isset($joborders[$job_dat['id']])) {
////                    $clientContactGrade[$job_dat['clientContact']['id']][] = $joborders[$job_dat['id']];
////                }
////            }
////            foreach ($clientContactGrade as $hiringManagerId => $algrade) {
////                $finalHiringGrade[$hiringManagerId] = array_sum($algrade) / count($algrade);
////            }
//            foreach($response['data'] as $job_dat){
//                
//            }
//        }
        //pr($clientContactGrade);
        return $finalHiringGrade;
    }

    public function getCandidateSkills($candidate_id = null) {
        $this->BullhornConnection->BHConnect();
        $skills = [];
        $fields = 'primarySkills[100](id,name)';
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $candidate_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data']) && !empty($response['data']['primarySkills']['data'])) {
            foreach ($response['data']['primarySkills']['data'] as $skill) {
                $skills[] = $skill['name'];
            }
        }
        return $skills;
    }

    public function get_joborder_fields($id, $fields) {
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = '';
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        return (isset($response['data'][$fields])) ? $response['data'][$fields] : "";
    }

    public function getSendOut($candidate_id) {
        $sendoutTable = TableRegistry::get('Sendout');
        $data = $sendoutTable->find()->where(['candidate_id' => $candidate_id])->orderDesc(['id']);
        if ($data->count()) {
            return $data->first()->toArray();
        } else {
            return null;
        }
    }

    public function getHourlyRateEmpPref($candidate_id) {
//        $this->BullhornConnection->BHConnect();
        $employmentPreference = "";
        $hourlyRate = 0;
        $states = "";
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $candidate_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=hourlyRateLow,employmentPreference,customTextBlock4,address(state)';
        $post_params = json_encode('hourlyRateLow,employmentPreference,customTextBlock4');
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

        if (isset($response['data'])) {
            if (count($response['data']['employmentPreference']) == 1 && isset($response['data']['employmentPreference'][0]) && strtolower($response['data']['employmentPreference'][0]) == 'permanent') { // Permanent
                $response['data']['employmentPreference'] = "";
            }
            $hourlyRate = isset($response['data']['hourlyRateLow']) ? $response['data']['hourlyRateLow'] : 0;
            $employmentPreference = (isset($response['data']['employmentPreference']) && !empty($response['data']['employmentPreference'])) ? $response['data']['employmentPreference'] : "";
            $states = (isset($response['data']['address']['state']) && !empty($response['data']['address']['state'])) ? $response['data']['address']['state'] : '';
        }
        
        if(isset($response['data']['customTextBlock4']) && !empty($response['data']['customTextBlock4'])) {
            $employmentPreference = !empty($response['data']['customTextBlock4']) ? explode(',',$response['data']['customTextBlock4']) : "";
        }
        
//        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $candidate_id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=hourlyRateLow,customTextBlock4';
//        $post_params = json_encode('hourlyRateLow,customTextBlock4');
//        $req_method = 'GET';
//        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
//
//        if (isset($response['data'])) {
//            if (count($response['data']['customTextBlock4']) == 1 && isset($response['data']['customTextBlock4'][0]) && strtolower($response['data']['customTextBlock4'][0]) == 'permanent') { // Permanent
//                $response['data']['customTextBlock4'] = "";
//            }
//            $hourlyRate = isset($response['data']['hourlyRateLow']) ? $response['data']['hourlyRateLow'] : 0;
//            $employmentPreference = isset($response['data']['customTextBlock4']) ? explode(',',$response['data']['customTextBlock4']) : "";
//        }

        return [$hourlyRate, $employmentPreference,$states];
    }

    public function updateTrigger($params) {
        $notifyTable = TableRegistry::get('Notifications');
        $typeIDs = [
            PERFORMANCE_RATING_REQUEST,
            PERFORMANCE_RATING_REMINDER_1,
            PERFORMANCE_RATING_REMINDER_2,
            PERFORMANCE_RATING_SR_FOLLOW_UP_1,
            PERFORMANCE_RATING_SR_FOLLOW_UP_2,
            PERFORMANCE_RATING_ADMIN_FOLLOW_UP
        ];
        $data = $notifyTable->find('list', [
                    'keyField' => 'typeID',
                    'valueField' => 'id'
                ])
                ->join([
                    'sendout' => [
                        'table' => 'sendout',
                        'type' => 'RIGHT',
                        'conditions' => 'Notifications.sendout_id = sendout.sendout_id'
                    ]
                ])->where(['sendout.joborder_id' => $params['id'], 'Notifications.typeID IN' => $typeIDs])
                ->orderAsc('typeID');

        if ($data) {
            $current_time = $params['dateEnd'];
            $current_time = $notifyTable->add_time_job_dateEnd($current_time);
            $current_time = strtotime('-330 minutes', $current_time); //GMT time based changes.
            $accept_notifycation = strtotime(date("Y-m-d H:i:s", strtotime('+1 minutes', $current_time)));
            $add_hours = 0;
            foreach ($data as $key => $value) {
                $notifyUpdate = $notifyTable->get($value);
                $notifyUpdate->fixed_timestamp = $accept_notifycation;
                $notifyUpdate->trigger_timestamp = strtotime("+$add_hours hours", $accept_notifycation);
                $notifyTable->save($notifyUpdate);
                $add_hours += 24;
            }
        }
        return;
    }

    /*     * *********************************************************
     * Creator          - Akilan
     * Function         - job_all_request
     * Date             - 19-12-2016
     * Description      - To make job order retrieve list to bullhorn     *
     * ********************************************************** */

    public function job_all_request($url, $params) {
        $post_params = json_encode($params);
        $req_method = 'GET';
        return $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
    }

    public function checkJobApplied($joborder_id, $candidate_id) {
        $applied = 0;
        $csendoutTable = TableRegistry::get('Sendout');
        $getSendout = $csendoutTable->find()->where(['candidate_id' => $candidate_id, 'joborder_id' => $joborder_id])->count();
        if ($getSendout > 0)
            $applied = 1;
        return $applied;
    }

    /**
     * Function   : checkJobPlaced
     * Author     : Siva.G
     * updated by : Akilan
     * Created on : 04-01-2017
     * @param type $sendout
     * @return int
     */
    public function checkJobPlaced($sendout) {
        $placed = 0;
        if (!empty($sendout) && isset($sendout['customSendoutInfo']) && !empty($sendout['customSendoutInfo'])) {
            switch ($sendout['customSendoutInfo']) {
                case isset($sendout['customSendoutInfo']['placement_id']) && !is_null($sendout['customSendoutInfo']['placement_id']):
                    $placed = 1;
                case isset($sendout['customSendoutInfo']['job_submission_status']) && $sendout['customSendoutInfo']['job_submission_status'] == PLACEMENT_REJECT_STATUS:
                    $placed = 1;
                    break;
            }
        }
        return $placed;
    }

    /**
     * Function   : myUrlEncode
     * Author     : Akilan
     * updated by : Akilan
     * Created on : 01-02-2017
     * Description: For replacing special character in desired title text
     * @param type $sendout
     * @return int
     */
    function myUrlEncode($string) {
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        return str_replace($replacements, $entities, $string);
    }

    /**
     * Function   : checkjobDate
     * Author     : Akilan
     * updated by : Akilan
     * Created on : 01-02-2017
     * Description: For checking job start & enddate expired and predisplay
     * @param type $sendout
     * @return int
     */
    function checkjobDate($response, $type, $invitedJobs, $role ="", $getAllCategories = array()) {


        $i = 0;
        foreach ($response['data'] as $jobOrder) {
            $currentTimestamp = time();
            $response['data'][$i]['dateEndFormatted'] = date("M d,Y", $response['data'][$i]['dateEnd']);
            $response['data'][$i]['dateTodayTime'] = $currentTimestamp;
            $response['data'][$i]['dateTodayTimeFormatted'] = date("M d,Y", $currentTimestamp);
            $response['data'][$i]['StartDateFormatted'] = date("M d,Y", $response['data'][$i]['startDate']);
            switch ($type) {
                case 'all' :
                    $response['data'][$i]['customCategory'] = isset($getAllCategories[trim($response['data'][$i]['customText8'])]) ? $getAllCategories[trim($response['data'][$i]['customText8'])] : ['id' => '', 'name' => ''];
                    if (isset($response['data'][$i]) && $role == CANDIDATE_ROLE && $response['data'][$i]['customInt2'] == 1 && (empty($invitedJobs) || !in_array($response['data'][$i]['id'], $invitedJobs))) {
                        unset($response['data'][$i]); // remove if this job is not invited to email user 
                        $response['total'] = $response['total'] - 1;
                        $response['count'] = $response['count'] - 1;
                    }
                    break;
                case 'filter':
                    if (isset($response['data'][$i]) && $response['data'][$i]['customInt2'] == 1 && (empty($invitedJobs) || !in_array($response['data'][$i]['id'], $invitedJobs))) {
                        unset($response['data'][$i]); // remove if this job is not invited to email user 
                        $response['total'] = $response['total'] - 1;
                        $response['count'] = $response['count'] - 1;
                    }
                    break;
            }
            if (isset($response['data'][$i])) {
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

        return $response;
    }
    
    /*
     * Function   : reference_performance_add
     * Author     : Balasuresh A
     * updated by : 
     * Created on : 01-02-2017
     * Updated on : 
     * Description: For saving the reference candidate performance by managers
     */
    public function reference_performance_add() {
        $params = $this->request->data;
        $this->autoRender = false;
        $dat = $this->candidate_save_reference($params);
        $old_perform_record = $this->reference_performance_data($params['candidate_bullhorn_id']);
        if (!empty($old_perform_record)) {
            $grade = array();
            $standard = array();
            $duration_ar = array();
            foreach ($old_perform_record as $rcd_sgl) {
                $durtn = $rcd_sgl['duration'];
                $standard[] = $this->calculate_standard($rcd_sgl);
                $duration_ar[] = $durtn;
            }
            $update_data['rating'] = round((array_sum($standard) / array_sum($duration_ar)) / 3, 1);
            $this->user_update($params['candidate_bullhorn_id'], $update_data, 'bullhorn_id');
            echo json_encode(
                    [
                        'status' => 1,
                        'message' => "Thanks for submitting the performance request"
                    ]
            );
            exit; 
    }
    }
    
    public function candidate_save_reference($params) {
        $userTable = TableRegistry::get('Users');
        $start_date = date('Y-m-d');
        $performanceTable = TableRegistry::get('Performance');
        $performance = $performanceTable->newEntity();
        $performanceTable->patchEntity($performance, $params);
        $performance->grade = $this->calculate_grade($params);
        $performance->start_date = $start_date;
        $performance->source_id = 2;
        $performance->reference_id = $params['reference_id'];
        $performanceTable->save($performance);
    }

}

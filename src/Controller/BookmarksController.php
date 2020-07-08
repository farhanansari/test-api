<?php

/* * ************************************************************************************
 * Class name      : Bookmarks Controller
 * Description     : To bookmark a job into our database
 * Created Date    : 10-09-2016 
 * Created By      : Sivaraj V 
 * ************************************************************************************* */

namespace App\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Network\Session\DatabaseSession;
use Cake\Validation\Validator;
use Cake\Network\Http\Client;
use Cake\Datasource\EntityInterface;

class BookmarksController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /**
     * Description	: To add/update bookmarks of a candidate
     * Created By	: Sivaraj V
     * Created on	: 10-09-2016
     * Updated on	: 10-09-2016	
     * URL          : /bookmarks/delete
     * Request input: candidate_id => ID of the candidate and joborder_id	
     */
    public function add() {
        $this->autoRender = false;
        $params = $this->request->data;
        $bookmarkTable = TableRegistry::get('Bookmarks');
        $getCandidate = $bookmarkTable->find()->select()->where(['candidate_id' => $params['candidate_id']])->toArray();
        if (empty($getCandidate)) {
            $bookmark = $this->Bookmarks->newEntity($params);
            if ($this->Bookmarks->save($bookmark)) {
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "Bookmarked!"
                        ]
                );
                exit;
            }
        }
        if (!empty($getCandidate)) {
            if (strpos($getCandidate[0]["joborder_id"], $params['joborder_id']) === false) {
                $bookmark_id = $getCandidate[0]["id"];
                $bookmark = $bookmarkTable->get($bookmark_id);
                $bookmarkTable->patchEntity($bookmark, $params);
                $bookmark->id = $bookmark_id;
                $bookmark->candidate_id = $params['candidate_id'];
                $bookmark->joborder_id = $getCandidate[0]["joborder_id"] . "," . $params['joborder_id'];
                $bookmarkTable->save($bookmark);

                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "Bookmarked!"
                        ]
                );
                exit;
            } else {
                echo json_encode(
                        [
                            'status' => 0,
                            'message' => "Already bookmarked!"
                        ]
                );
                exit;
            }
        }
    }

    /**
     * Description	: To delete bookmarked joborders of a candidate
     * Created By	: Sivaraj V
     * Created on	: 12-09-2016
     * Updated on	: 12-09-2016	
     * URL          : /bookmarks/delete
     * Request input: candidate_id => ID of the candidate and joborder_id	
     */
    public function delete() {
        $this->autoRender = false;
        $params = $this->request->data;
        $bookmarkTable = TableRegistry::get('Bookmarks');
        $getCandidate = $bookmarkTable->find()->select()->where(['candidate_id' => $params['candidate_id']])->toArray();
        if (empty($getCandidate)) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No bookmark available!"
                    ]
            );
            exit;
        }
        if (!empty($getCandidate)) {
            if (empty($getCandidate[0]["joborder_id"]) && strpos($getCandidate[0]["joborder_id"], $params['joborder_id']) === false) {
                $bookmark_id = $getCandidate[0]["id"];
                $bookmark = $bookmarkTable->get($bookmark_id);
                $result = $bookmarkTable->delete($bookmark);
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "No one bookmark found for this candidate!"
                        ]
                );
                exit;
            } else {
                $bookmark_id = $getCandidate[0]["id"];
                $bookmark = $bookmarkTable->get($bookmark_id);
                $joborders = explode(",", $getCandidate[0]["joborder_id"]);
                if (in_array($params['joborder_id'], $joborders)) {
                    unset($joborders[array_search($params['joborder_id'], $joborders)]);
                    if (!empty($joborders)) {
                        $bookmarkTable->patchEntity($bookmark, $params);
                        $bookmark->id = $bookmark_id;
                        $bookmark->candidate_id = $params['candidate_id'];
                        $bookmark->joborder_id = implode(",", $joborders);
                        $bookmarkTable->save($bookmark);
                    } else {
                        $bookmarkTable->delete($bookmark);
                    }

                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Deleted a bookmark!"
                            ]
                    );
                    exit;
                } else {

                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => "No bookmark found!"
                            ]
                    );
                    exit;
                }
            }
        }
    }

    /**
     * Description	: To list all bookmarked joborders of a candidate
     * Created By	: Sivaraj V
     * Created on	: 12-09-2016
     * Updated on	: 12-09-2016
     * URL          : /bookmarks/all
     * Request input: candidate_id => ID of the candidate.	
     */
    public function all($params = []) {
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();       
        $candidate_id = $params['candidate_id'];
        $bookmarkTable = TableRegistry::get('Bookmarks');
        $getCandidate = $bookmarkTable->find()->select()->where(['candidate_id' => $candidate_id])->toArray();
        if (empty($getCandidate)) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No bookmark available!"
                    ]
            );
            exit;
        }
        if (!empty($getCandidate)) {
            $bookmarkedJobs = $getCandidate[0]["joborder_id"];
        }
        //$fields='id,address,clientCorporation,dateEnd,description,title,salary,skills,skillList,salaryUnit,durationWeeks,'
        //  . 'yearsRequired,customText1,customText2,customText3,customText4,customFloat1,customFloat2'; 
        $fields = 'id,employmentType,type,address,description,clientContact,clientCorporation'
                . ',status,isDeleted,payRate,salary,salaryUnit,correlatedCustomInt1,customText1,customText2,customText3,customText4,customText5,'
                . 'customText6,customFloat1,customFloat2,dateAdded,dateClosed,dateEnd,title';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $bookmarkedJobs . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=' . $fields;
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
      
        if (isset($response['data'])) {
            if (isset($response['data']['id'])) {
                $response['data']['isBookmarked'] = $this->isJobBookmarked($candidate_id, $response['data']['id']);
                $get_result=$response['data'];unset($response['data']);
                $response['data'][]=$get_result;
            } else {
                $i = 0;
                foreach ($response['data'] as $jobOrder) {
                    $response['data'][$i]['isBookmarked'] = $this->isJobBookmarked($candidate_id, $jobOrder['id']);
                    $i++;
                    continue;
                }
            }
            $response['hidden_company']=HIDDEN_COMPANY_TEXT;//for showing hidden company text
        }       
        echo json_encode($response);
    }

    /**
     * Description	: To check joborder is bookmarked
     * Created By	: Sivaraj V
     * Created on	: 12-09-2016
     * Updated on	: 12-09-2016	
     */
    public function isJobBookmarked($candidate_id = null, $joborder_id = null) {
        $bookmarkTable = TableRegistry::get('Bookmarks');
        $getCandidate = $bookmarkTable->find()->select()->where(['candidate_id' => $candidate_id])->toArray();

        if (empty($getCandidate)) {
            return 0; // false
        }
        if (!empty($getCandidate)) {
            $bookmark_id = $getCandidate[0]["id"];
            $bookmark = $bookmarkTable->get($bookmark_id);
            $joborders = explode(",", $getCandidate[0]["joborder_id"]);
            if (in_array($joborder_id, $joborders)) {
                return 1; // true
            } else {
                return 0; // false
            }
        }
    }

}
?>




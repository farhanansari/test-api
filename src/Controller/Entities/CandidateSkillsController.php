<?php

/* * ************************************************************************************
 * Class name      : Candidate Skills Controller
 * Description     : add/remove skills for a candidate
 * Created Date    : 02-09-2016 
 * Created By      : Sivaraj Venkat 
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
use Cake\Mailer\Email;
use Cake\Datasource\ConnectionManager;

class CandidateSkillsController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : add
     * Description   : to add/update candidate skills set.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 16-09-2016
     * Updated Date  : 16-09-2016
     * URL           : /entities/candidate_skills/add?
     * Request input : skill_id=> pass skill id for existing skill or skill_name for new skill
		//<<  skill_category_id => id of the category for this new skill
		  skill_name => skill name for add new skill, >>//
		  candidate_id => candidate id for adding skills
     */

    public function add() {
        $this->autoRender = false;
        $params = $this->request->data;
        //echo "<pre>";print_r($params);echo "</pre>"; exit;
        $skillsTable = TableRegistry::get('Skill');
        $candidateSkillsTable = TableRegistry::get('CandidateSkill');
        $getSkill = [];
        if (isset($params['skill_id'])) {
            $getSkill = $skillsTable->find()->select()->where(['id' => $params['skill_id']])->toArray();
            //$skillType = 'skill_id';
        }else{
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No skill id given!"
                    ]
            );
            exit;
        }
        /*else {
            $getSkill = $skillsTable->find()->select()->where(['skill_name' => $params['skill_name'],'skill_category_id'=>$params['skill_category_id']])->toArray();
            $skillType = 'skill_name';
        }
        if (empty($getSkill) && $skillType == "skill_name") {
            $skils = $skillsTable->newEntity($params);
            if ($result = $skillsTable->save($skils)) {
                $getSkill[0]['id'] = $result->id;
            }
        } else */
            if (empty($getSkill)) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No Skill is available!"
                    ]
            );
            exit;
        }
        if (!empty($getSkill)) {
            $getCandidateSkills = $candidateSkillsTable->find()->select()->where(['candidate_id' => $params['candidate_id']])->toArray();
            if (empty($getCandidateSkills)) {
                $params['skill_ids'] = $getSkill[0]['id'];
                $skill = $candidateSkillsTable->newEntity($params);
                if ($candidateSkillsTable->save($skill)) {
                    $this->bhSkillSet($params['candidate_id']);
                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Skill Added!",
                            ]
                    );
                    exit;
                }
            }
            if (!empty($getCandidateSkills)) { 
                //if (strpos($getCandidateSkills[0]["skill_ids"], $getSkill[0]['id']) === false) {
                    if (!in_array($getSkill[0]['id'], explode(',',$getCandidateSkills[0]["skill_ids"]))) {
                    $candidate_skills_id = $getCandidateSkills[0]["id"];
                    $candidate_skills = $candidateSkillsTable->get($candidate_skills_id);
                    $candidateSkillsTable->patchEntity($candidate_skills, $params);
                    $candidate_skills->id = $candidate_skills_id;
                    $candidate_skills->candidate_id = $params['candidate_id'];
                    $candidate_skills->skill_ids = $getCandidateSkills[0]["skill_ids"] . "," . $getSkill[0]['id'];
                    $candidateSkillsTable->save($candidate_skills);
                    $this->bhSkillSet($params['candidate_id']);
                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Skill Added!"
                            ]
                    );
                    exit;
                } else {
                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => "Already Skill Added!"
                            ]
                    );
                    exit;
                }
            }
        }
    }

    /**
     * Description	: To delete skills for a candidate
     * Created By	: Sivaraj V
     * Created on	: 19-09-2016
     * Updated on	: 19-09-2016	
     * URL          : /entities/candidate_skills/delete?
     * Request input: candidate_id => candidate id, skill_id => candidate skill
     */
    public function delete() {
        $this->autoRender = false;
        $params = $this->request->data;
        //echo "<pre>";print_r($params);echo "</pre>"; exit;
        $candidateSkillsTable = TableRegistry::get('CandidateSkill');
        $getCandidateSkills = $candidateSkillsTable->find()->select()->where(['candidate_id' => $params['candidate_id']])->toArray();
        //echo "<pre>";print_r($getCandidateSkills);echo "</pre>"; exit;
        if (empty($getCandidateSkills)) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No Skills available!"
                    ]
            );
            exit;
        }
        if (!empty($getCandidateSkills)) {
            if (empty($getCandidateSkills[0]["skill_ids"]) && strpos($getCandidateSkills[0]["skill_ids"], $params['skill_id']) === false) {
                $candidate_skills_id = $getCandidateSkills[0]["id"];
                $candidate_skills = $candidateSkillsTable->get($candidate_skills_id);
                $result = $candidateSkillsTable->delete($candidate_skills);
                echo json_encode(
                        [
                            'status' => 1,
                            'message' => "No skill found for this candidate!"
                        ]
                );
                exit;
            } else {
                $candidate_skills_id = $getCandidateSkills[0]["id"];
                $candidate_skills = $candidateSkillsTable->get($candidate_skills_id);
                $skills = explode(",", $getCandidateSkills[0]["skill_ids"]);
                if (in_array($params['skill_id'], $skills)) {
                    unset($skills[array_search($params['skill_id'], $skills)]);
                    if (!empty($skills)) {
                        $candidateSkillsTable->patchEntity($candidate_skills, $params);
                        $candidate_skills->id = $candidate_skills_id;
                        $candidate_skills->candidate_id = $params['candidate_id'];
                        $candidate_skills->skill_ids = implode(",", $skills);
                        $candidateSkillsTable->save($candidate_skills);
                        $this->bhSkillSet($params['candidate_id']);
                    } else {
                        $candidateSkillsTable->delete($candidate_skills);
                    }

                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Removed a skill!"
                            ]
                    );
                    exit;
                } else {

                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => "No skill found!"
                            ]
                    );
                    exit;
                }
            }
        }
    }

    /**
     * Description	: To get all skills for a candidate
     * Created By	: Sivaraj V
     * Created on	: 19-09-2016
     * Updated on	: 19-09-2016	
     * URL          : /entities/candidate_skills/all?
     * Request input: candidate_id => candidate id
     */
    public function all() {
        $this->autoRender = false;
        $params = $this->request->data;
        $candidate_id = 0;
        $this->loadModel('Skill');
        if (isset($params['candidate_id'])) {
            $candidate_id = $params['candidate_id'];
        } else {
            echo json_encode(['status' => 0, 'message' => 'Candidate id is required']); exit;
        }
        $skillIds = TableRegistry::get('CandidateSkill')->find()->select(['skill_ids'])->where(['candidate_id' => $candidate_id])->toArray();
        if (empty($skillIds)) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No category skills available for the candidate!",
                    ]
            );
            exit;
        }
        $getCategoryall = $this->Skill->find('all')->select(['id','skill_name','skill_category.category_name','skill_category.id'])->join([
            'skill_category' => [
                'table' => 'skill_category',
                'type' => 'INNER',
                'conditions' => 'skill_category.id = skill_category_id'
            ]
        ])->where(['Skill.id IN' =>explode(",",$skillIds[0]['skill_ids'])])->order('skill_category.category_name ASC')->toArray();
        $category_with_skills = [];$result= [];
        if(!empty($getCategoryall)){
            foreach($getCategoryall as $cat){
            $category_with_skills[$cat['skill_category']['id']][$cat['skill_category']['category_name']][] = [
                'skill_id' => $cat['id'],
                'skill_name' => $cat['skill_name'],
            ];
            }
     
            foreach($category_with_skills as $catKey => $category){
               $catName = array_keys($category);     
                $result[] = [
                    'category_id' => $catKey,
                    'category_name' => $catName[0],
                    'skills' =>$category[$catName[0]]
                ];
            }

        }
        if (empty($getCategoryall)) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "No category skills available for the candidate!",
                    ]
            );
            exit;
        } else { 
            echo json_encode(
                    [
                        'status' => 1,
                        'message' => "Success!",
                        'result' => $result
                    ]
            );
            exit;
        }
    }

    
    
    
     /* * * * * * * * * *  * * * *    
     * Action Name   : category_add
     * Description   : to add candidate skills set.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 16-09-2016
     * Updated Date  : 16-09-2016
     * URL           : /entities/candidate_skills/category_add?
     * Request input : category_name => category name for skill
     */

    public function category_add() {
        $this->autoRender = false;
        $params = $this->request->data;
        //echo "<pre>";print_r($params);echo "</pre>"; exit;
        $categoryTable = TableRegistry::get('SkillCategory');
        $getCategory = $categoryTable->find()->select(['id'])->where(['category_name' => $params['category_name']])->toArray();
        if(empty($getCategory)){
        $category = $categoryTable->newEntity($params);
                if ($categoryTable->save($category)) {
                    echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Skill Category Added!",
                            ]
                    );
                    exit;
                }
        }else{
                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => "Skill Category Already Exists!",
                            ]
                    );
                    exit;
        }
    
    }
    
    
     /* * * * * * * * * *  * * * *    
     * Action Name   : category_all
     * Description   : to add category for skills set.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 16-09-2016
     * Updated Date  : 16-09-2016
     * URL           : /entities/candidate_skills/category_all?
     * Request input : 
     */

    public function category_all() {
        $this->autoRender = false;
        $this->loadModel('Skill');
        $getCategoryall = $this->Skill->find('all')->select(['id','skill_name','skill_category.category_name','skill_category.id'])->join([
            'skill_category' => [
                'table' => 'skill_category',
                'type' => 'INNER',
                'conditions' => 'skill_category.id = skill_category_id'
            ]
        ])->order('skill_category.category_name ASC')->toArray();
        $category_with_skills = [];$result= [];
        if(!empty($getCategoryall)){
            foreach($getCategoryall as $cat){
            $category_with_skills[$cat['skill_category']['id']][$cat['skill_category']['category_name']][] = [
                'skill_id' => $cat['id'],
                'skill_name' => $cat['skill_name'],
            ];
            }
            
            foreach($category_with_skills as $catKey => $category){
               $catName = array_keys($category);
                $result[] = [
                    'category_id' => $catKey,
                    'category_name' => $catName[0],
                    'skills' =>$category[$catName[0]]
                ];
            }
            
        }
        if(empty($getCategoryall)){
                    echo json_encode(
                            [
                                'status' => 0,
                                'message' => "No category skills available!",
                            ]
                    );
                    exit;   
        }else{
            echo json_encode(
                            [
                                'status' => 1,
                                'message' => "Success!",
                                'result' => $result
                            ]
                    );
                    exit;  
        }
    }
    
    
    /*
     * Description: Local function to update the candidate skill set property dynamically
     * 
     */
    
    public function bhSkillSet($id = null) {
        $this->BullhornConnection->BHConnect();
        $skillSetText = '';
        $skillIds = TableRegistry::get('CandidateSkill')->find()->select(['skill_ids'])->where(['candidate_id' => $id])->toArray();
        if (isset($skillIds[0]['skill_ids'])) {
            $skills = TableRegistry::get('Skill')->find()->select(['skill_name'])->where(['id IN' =>explode(",",$skillIds[0]['skill_ids'])])->toArray();
            foreach($skills as $skill){
                    $skillSet[] = $skill['skill_name'];
                }
            $skillSetText = implode(',', $skillSet);
        }
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $id . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params = [
            'id' => $id,
            'skillSet' => $skillSetText
        ];
        $post_params = json_encode($params);
        $req_method = 'POST';
        $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
    }
    
    /*     * * * * * * * * *  * * * *    
     * Action Name   : category_skills
     * Description   : to get all category and skills from bullhorn.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 05-10-2016
     * Updated Date  : 05-10-2016
     * URL           : /entities/candidate_skills/category_skills?
     * Request input : []
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */


    public function category_skills(){
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $categories = implode(',', $this->getCategories());
        $result = $this->getSkills($categories,'category_id'); 
        if(isset($result['data']) && !empty($result['data'])){
                echo json_encode(
                        [
                            'status' => 1,
                            'data' => $result['data'] 
                      ]
                    );                      
                exit;
            }else{
                echo json_encode(
                        [
                            'status' => 0,
                            'data' => []
                      ]
                    );
                exit;
            }
    }
    
    public function getCategories(){
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
    
    
    /*     * * * * * * * * *  * * * *    
     * Action Name   : bhadd
     * Description   : to add primary skills for a candidate.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 05-10-2016
     * Updated Date  : 05-10-2016
     * URL           : /entities/candidate_skills/bhadd?
     * Request input : candidate_id, skill_ids
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    
    public function bhadd(){
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $params = $this->request->data; 
        if(!isset($params['candidate_id'])){
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "Candidate id is required!"
                    ]
            );
            exit;
        }
        if(isset($params['skill_ids']) && is_array($params['skill_ids'])){
        //pr($params);
            $skills = implode(',', $params['skill_ids']);
            $skillSetUpdate = "";
            list($categories,$skillsL,$catSkills) = $this->split_cat_skill($params['existingSkillSet']);
            $isDuplicated = array_count_values($skillsL);
                if(isset($params['existingSkillSet']) && isset($params['skillSet'])){
                    $skillArr = array_filter(explode(',',$params['existingSkillSet']));
                    $getIndex = array_search($params['skillSet'],$skillArr);
                    //pr($skillArr);
                    //echo $params['skillSet'];
                    if($getIndex == false){
                            $skillArr[] = $params['skillSet'];
                            }

                    $skillSetUpdate = implode(',',$skillArr);
                if(!isset($isDuplicated[$params['skill_ids'][0]])){
                    $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/'.$params['candidate_id'].'/primarySkills/'.$skills.'?BhRestToken=' . $_SESSION['BH']['restToken'];

                    $post_params = json_encode([]);
                    $req_method = 'PUT';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                }
             }
           /* if(isset($response['errors'])){ // if adding skill returns any errors
               echo json_encode([
                        'status' => 0,
                        'message' => $response    
                    ]); 
                exit; 
            }else{ */
               // $skillIds = []; 
                    $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['candidate_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];

                    $post_params = json_encode([
                                              'id' => $params['candidate_id'],
                                                'skillSet' => $skillSetUpdate
                                            ]);
                    $req_method = 'POST';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                    if(isset($response['data'])){
                        list($categories,$skills,$catSkills) = $this->split_cat_skill($response['data']['skillSet']);
                       /* foreach($response['data']['primarySkills']['data'] as $skill){
                            $skillIds[] = $skill['id'];
                        }*/
                        if(empty($catSkills)){
                            $result = $this->getSkills($skills); // returns duplicate skills with different category
                        }else{
                            $result = $this->getSkills($skills,'skill_id',$catSkills);
                        }
                        echo json_encode(
                                [
                                    'status' => 1,
                                    'data' => isset($result['data'])?$result['data']:[],
                                    'existingSkillSet' => $response['data']['skillSet'],
                              ]
                            );
                        exit;
                    }else{
                        echo json_encode(
                                [
                                    'status' => 0,
                                    'data' => $response
                              ]
                            );
                        exit;
                    }
            //}

        }else{
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "skill ids are required and also it need to be in array format!"
                    ]
            );
            exit;
        }
    }
    
     /*     * * * * * * * * *  * * * *    
     * Action Name   : bhdelete
     * Description   : to delete primary skills for a candidate.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 05-10-2016
     * Updated Date  : 05-10-2016
     * URL           : /entities/candidate_skills/bhdelete?
     * Request input : candidate_id, skill_ids
     * Request method: DELETE
     * Responses:
      1. success:
      2.fail:
     */

    
    public function bhdelete(){
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $params = $this->request->data;
        if(!isset($params['candidate_id'])){
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "Candidate id is required!"
                    ]
            );
            exit;
        }
        if(isset($params['skill_ids']) && is_array($params['skill_ids'])){
            list($categories,$skillsL,$catSkills) = $this->split_cat_skill($params['existingSkillSet']);
            $isDuplicated = array_count_values($skillsL);
            if(isset($isDuplicated[$params['skill_ids'][0]]) && $isDuplicated[$params['skill_ids'][0]] ==1){
                $skills = implode(',', $params['skill_ids']);
                $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/'.$params['candidate_id'].'/primarySkills/'.$skills.'?BhRestToken=' . $_SESSION['BH']['restToken'];

                $post_params = json_encode([]);
                $req_method = 'DELETE';
                $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
            }
            /*if(isset($response['errors'])){
                echo json_encode([
                        'status' => 0,
                        'message' => $response    
                    ]);
                 exit;
            }else{*/
                $skillSetUpdate = "";
                if(isset($params['existingSkillSet']) && isset($params['skillSet'])){
                    $skillArr = array_filter(explode(',',$params['existingSkillSet']));
                    $getIndex = array_search($params['skillSet'],$skillArr);
                    if($getIndex !== false){
                            unset($skillArr[$getIndex]);
                            }

                    $skillSetUpdate = implode(',',$skillArr);
                }
                    $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['candidate_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];

                    $post_params = json_encode([
                                              'id' => $params['candidate_id'],
                                                'skillSet' => $skillSetUpdate
                                            ]);
                    $req_method = 'POST';
                    $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);

               // $skillIds = [];
                    if(isset($response['data'])){ //index 1 candidate skills from bullhorn
                        list($categories,$skills,$catSkills) = $this->split_cat_skill($response['data']['skillSet']);
                        /*foreach($responseMulti[1]['data']['primarySkills']['data'] as $skill){
                            $skillIds[] = $skill['id'];
                        }*/
                        if(empty($catSkills)){
                            $result = $this->getSkills($skills); // returns duplicate skills with different category
                        }else{
                            $result = $this->getSkills($skills,'skill_id',$catSkills);
                        }
                        echo json_encode(
                                [
                                    'status' => 1,
                                    'data' => isset($result['data'])?$result['data']:[],
                                    'existingSkillSet' => $response['data']['skillSet'],
                              ]
                            );
                        exit;
                    }else{
                        echo json_encode(
                                [
                                    'status' => 0,
                                    'data' => $response
                              ]
                            );
                        exit;
                    }
            //}
        }else{
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "skill ids are required and also it need to be in array format!"
                    ]
            );
            exit;
        }
    }
    
    
     /*     * * * * * * * * *  * * * *    
     * Action Name   : bhall
     * Description   : to add primary skills for a candidate.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 05-10-2016
     * Updated Date  : 05-10-2016
     * URL           : /entities/candidate_skills/bhall
     * Request input : candidate_id
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */
    
        public function bhall(){
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        $params = $this->request->data;
        if(!isset($params['candidate_id'])){
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "Candidate id is required!"
                    ]
            );
            exit;
        }
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['candidate_id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=primarySkills[1000](id,name),skillSet';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        //$skillIds = [];
            if(isset($response['data'])){
                list($categories,$skills,$catSkills) = $this->split_cat_skill($response['data']['skillSet']);
                /*foreach($response['data']['primarySkills']['data'] as $skill){
                    $skillIds[] = $skill['id'];
                }*/
                if(empty($catSkills)){
                    $result = $this->getSkills($skills); // returns duplicate skills with different category
                }else{
                    $result = $this->getSkills($skills,'skill_id',$catSkills);
                }
                echo json_encode(
                        [
                            'status' => 1,
                            'data' => isset($result['data'])?$result['data']:[],
                            'existingSkillSet' => $response['data']['skillSet'],
                      ]
                    );
                exit;
            }else{
                echo json_encode(
                        [
                            'status' => 0,
                            'data' => $response
                      ]
                    );
                exit;
            }
        }
    
     /*
      * Function    : split_cat_skill
      * Description : Separate categories and skills from skillSet
      * Sample Param: 2000009-1000092,2000009-1000089 (category-skill)
      * Created By  : Sivaraj V
      * Created On  : 04-01-2017
      */
    public function split_cat_skill($skillSet = null){
        $categories = []; $skills = [];$catSkils = [];
        if($skillSet != null){
        $catSkil = array_filter(explode(',', $skillSet));
        if(!empty($catSkil)){
            foreach($catSkil as $split){
                 $split = explode('-', $split);
                $categories[] = (isset($split[0]) && !empty($split[0]))?$split[0]:"";
                $skills[] = (isset($split[1]) && !empty($split[1]))?$split[1]:"";
                $catSkils[$split[0]][] = $split[1];
            }
        }
        } 
        return [
           $categories,$skills,$catSkils 
        ];
    }
    
    /*     * * * * * * * * *  * * * *
     * Action Name   : skills_add
     * Description   : to add or remove skills for a candidate from website.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 09-11-2016
     * Updated Date  : 09-11-2016
     * URL           : /entities/candidate_skills/skills_add?
     * Request input : candidate_id, old_skill_ids, new_skill_ids
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function skills_add() {
        $this->autoRender = false;
        $params = $this->request->data;
        $message = [];
        if (!isset($params['candidate_id'])) {
            echo json_encode(
                    [
                        'status' => 0,
                        'message' => "candidate id is required!"
                    ]
            );
            exit;
        }
        if (isset($params['old_skill_ids']) && is_array($params['old_skill_ids'])) {
             $skills = implode(',', $params['old_skill_ids']);
             if($this->update_candidate_skills($params['candidate_id'],$skills,'DELETE'))
                $message[] = 'Deleted skill id:'.$skills;
        }
        if (isset($params['old_category_ids']) && is_array($params['old_category_ids'])) {
            $categories = implode(',', $params['old_category_ids']);
            if($this->update_candidate_categories($params['candidate_id'],$categories,'DELETE'))
                $message[] = 'Deleted category id:'.$categories;
        }
        if (isset($params['new_category_ids']) && is_array($params['new_category_ids'])) {
            $categories = implode(',', $params['new_category_ids']);
            if($this->update_candidate_categories($params['candidate_id'],$categories,'PUT'))
                $message[] = 'Added category id:'.$categories;
        }
        if (isset($params['new_skill_ids']) && is_array($params['new_skill_ids'])) {
            $skills = implode(',', $params['new_skill_ids']);
            if($this->update_candidate_skills($params['candidate_id'],$skills,'PUT'))
                $message[] = 'Added skill id:'.$skills;
        } 
        
        if(!empty($message)){
             $result = [
                    'status' => 1,
                    'message' => $message
                ];
                echo json_encode($result);
        }else{
             $result = [
                    'status' => 0,
                    'message' => $message
                ];
                echo json_encode($result);
        }
    }
    
    
    public function update_candidate_skills($id = '',$skills = '',$req_method = ''){
            $this->BullhornConnection->BHConnect();
            $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $id . '/primarySkills/' . $skills . '?BhRestToken=' . $_SESSION['BH']['restToken'];

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
            }else{
                return 1;
            }
    }
    
    public function update_candidate_categories($id = '',$categories = '',$req_method = ''){
            $this->BullhornConnection->BHConnect();
            $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $id . '/categories/' . $categories . '?BhRestToken=' . $_SESSION['BH']['restToken'];

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
            }else{
                return 1;
            }
    } 
        
}

?>

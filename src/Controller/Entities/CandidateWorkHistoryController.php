<?php

/* * ************************************************************************************
 * Class name      : CandidateWorkHistoryController
 * Description     : Candidate CRUD process
 * Created Date    : 01-09-2016 
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

class CandidateWorkHistoryController extends AppController {

    public function initialize() {
        parent::initialize();
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : add
     * Description   : To create the candidate work history
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 01-09-2016 
     * Updated Date  : 01-09-2016 
     * URL           : /peoplecaddie-api/entities/CandidateWorkHistory/?
     * Request input : {
      changedEntityType: "CandidateWorkHistory",
      changedEntityId: 4,
      changeType: "INSERT",
      data: {
      candidate: {
      id: "216"
      },
      companyName: "test Pop",
      title: "Software Engineer",
      comments: "New Millenniumt",
      dateAdded: 1472751176
      }
      }
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function add($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/CandidateWorkHistory?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
        //echo json_encode(array('result' => 1, 'error' => 'Sorry, We faced failure in contractor signup procss'));
    }

    /*     * * * * * * * * * *  * * * *    
     * Action Name   : view
     * Description   : To retrieve the candidate work history
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 01-09-2016 
     * Updated Date  : 01-09-2016 
     * URL           :/peoplecaddie-api/entities/CandidateWorkHistory/?id=52
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
        $fields='workHistories(id,companyName,title,startDate,endDate)';
        $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields='.$fields;        
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : update
     * Description   : To update the candidate work history.
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 01-09-2016 
     * Updated Date  : 01-09-2016 
     * URL           :/peoplecaddie-api/general/CandidateWorkHistory/?id=52
     * Request input : 
      companyName: "test Pop",
      title: "Software Engineer",
      comments: "New Millenniumt",
      dateAdded: 1472751176
     * Request method: POST
     * Responses:
      1. success:
     */

    public function update($params = null) {
        $this->autoRender = false;
        $params = $this->request->data;
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/CandidateWorkHistory/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        if (isset($params['id'])) {
            $status = $this->user_update($params['id'], $params, 'bullhorn_id');
            if ($status != 1 && is_array($status)) {
                $error_msg = $this->format_validation_message($status);
                echo json_encode(array('result' => 0, 'error' => $error_msg));
                exit;
            }
        }
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : delete
     * Description   : To create the candidate
     * Created by    : Akilan
     * Updated by    : Akilan
     * Created Date  : 01-09-2016 
     * Updated Date  : 01-09-2016 
     * URL           : /peoplecaddie-api/entities/CandidateWorkHistory/?id=52
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
        $params['isDeleted']=true;
        $url = $_SESSION['BH']['restURL'] . '/entity/CandidateWorkHistory/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

}

?>
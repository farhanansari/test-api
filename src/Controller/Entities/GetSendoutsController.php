<?php 

/* * ************************************************************************************
 * Class name      : GetSendouts Controller
 * Description     : Fetch sendouts for a job order
 * Created Date    : 01-09-2016 
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


class GetSendoutsController extends AppController {
    
        public function initialize() {
        parent::initialize();
        $this->loadComponent('RequestHandler');
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : index
     * Description   : To get sendout details for a specific job order.
     * Created by    : Sivaraj V
     * Updated by    : Sivaraj V
     * Created Date  : 01-09-2016
     * Updated Date  : 02-09-2016
     * URL           : /entities/get_sendouts/?
     * Request input : id => job id
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function index($params = null){
        $params = $this->request->data;
        $this->autoRender = false;
        $this->BullhornConnection->BHConnect();
        //$url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=sendouts(candidate)';
        $url = $_SESSION['BH']['restURL'] . '/entity/JobOrder/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=sendouts';       
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
        
    }
    
    
}

?>

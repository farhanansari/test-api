<?php
/* * ************************************************************************************
 * Class name      : NoteController
 * Description     : Note CRUD process
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

class NoteController extends AppController {

    public function initialize() {
        parent::initialize();
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : add
     * Description   : To set the note by commenting person to other person to whom the note is associated with.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/note/?
     * Request input : commentingPerson[id], comments, personReference[id]
     * Request method: PUT
     * Responses:
      1. success:
      2.fail:
     */

    public function add($params = null) {
        $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Note?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'PUT';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : view
     * Description   : To retrieve the comments sent by the commenting person.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/note/?id=3
     * Request input : id => ID of the note.
     * Request method: GET
     * Responses:
      1. success:
      2.fail:
     */

    public function view($params = null) {
        $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Note/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=*';
        $post_params = json_encode($params);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : update
     * Description   : To update the comments sent by the commenting person.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/note/?id=3
     * Request input : id => ID of the note. Params to be updated such as comments.
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function update($params = null) {
        $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Note/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['dateAdded'] = time();
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

    /*     * * * * * * * * *  * * * *    
     * Action Name   : delete
     * Description   : To delete the note sent by the particular commenting person.
     * Created by    : AKILAN
     * Updated by    : AKILAN
     * Created Date  : 23-08-2016
     * Updated Date  : 23-08-2016
     * URL           : /peoplecaddie-api/entities/note/delete_note/?id=4
     * Request input : id => ID of the note,  isDeleted = true
     * Request method: POST
     * Responses:
      1. success:
      2.fail:
     */

    public function delete($params = null) {
        $params = $this->request->data;
        $this->bh_connect = $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/Note/' . $params['id'] . '?BhRestToken=' . $_SESSION['BH']['restToken'];
        $params['isDeleted'] = 'true';
        $post_params = json_encode($params);
        $req_method = 'POST';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        echo json_encode($response);
    }

}

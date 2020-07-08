<?php

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

class NotifyController extends AppController {
    
    public function update() {   
        $this->autoRender = false;
        $this->loadModel('User');
        $userTable = TableRegistry::get('Users');
        $params = $this->request->data;        
        if(isset ($params['bullhorn_entity_id'])) {
            $query = $userTable->find()
                        ->where(['Users.bullhorn_entity_id' => $params['bullhorn_entity_id']]);        
            $row = $query->first();
            $user_id = $row->id;        
            $user_data = $userTable->get($user_id);        

            $current_count = $user_data->notify_count;
            if($current_count != 0 ) {
                $user_data->notify_count = $current_count-1;            
                $userTable->save($user_data);
                echo json_encode(array('result'=>1));
            }
        }
    }
}


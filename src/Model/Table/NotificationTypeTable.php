<?php

/* * ************************************************************************************
 * Class name      : NotificationTypeTable
 * Description     : get notification type details
 * Created Date    : 25-10-2016 
 * Created By      : Akilan 
 * ************************************************************************************* */

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;


class NotificationTypeTable extends Table {
    
    public function initialize(array $config) {
        $this->table('notification_type');
       // $this->displayField('username');
        $this->primaryKey('id');        
    }

    public function get_notification_type() {
        return $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => function ($row) {
                                    return [
                                        'id' => $row['id'],
                                        'type' => $row['type'],
                                        'mail_subject' => $row['mail_subject'],
                                        'mobile_notification' => $row['mobile_notification'],
                                        'message_text' => $row['message_text'],
                                        'sms_text' => $row['sms_text'],
                                    ];
                                }
                ])->toArray();
    }
    
}

?>
<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;

class BookmarksTable extends Table {

    public function initialize(array $config) {
        $this->table('bookmark');
       // $this->displayField('username');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator
                ->notEmpty('candidate_id', ('This field is required.'))
                ->notEmpty('joborder_id', ('This field is required.'));

        $validator->add('candidate_id', ['unique' => [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'Duplicate entry is not allowed for a candidate']
        ]);

        return $validator;
    }

}

?>

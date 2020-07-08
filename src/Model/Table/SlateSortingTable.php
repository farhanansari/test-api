<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;

class SlateSortingTable extends Table {

    public function initialize(array $config) {
        $this->table('slate_sorting');
       // $this->displayField('username');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator
                ->notEmpty('user_id', ('This field is required.'))
                ->notEmpty('candidate_id', ('This field is required.'));

        return $validator;
    }

}

?>
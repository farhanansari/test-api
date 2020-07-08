<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;

class CandidateSkillsTable extends Table {

    public function initialize(array $config) {
        $this->table('candidate_skill');
       // $this->displayField('username');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }


}

?>

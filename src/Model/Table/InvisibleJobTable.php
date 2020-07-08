<?php

/* * ************************************************************************************
 * Class name      : InvisibleJobTable
 * Description     : To make job invisible
 * Created Date    : 25-10-2016 
 * Created By      : Akilan 
 * ************************************************************************************* */

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;

class InvisibleJobTable extends Table {

    public function initialize(array $config) {
        $this->table('invisible_job');
        // $this->displayField('username');
        $this->primaryKey('id');
    }

    public function make_invisible($params) {
        $invisibleTable=$this;
        $getCandidate = $this->find()->select()->where(['candidate_id' => $params['candidate_id']])->toArray();
        if (empty($getCandidate)) {
            $saveVal = [
                'candidate_id' => $params['candidate_id'],
                'joborder_ids' => $params['joborder_id'],
            ];
            $invisible = $this->newEntity($saveVal);
            if ($invisibleTable->save($invisible)) {
                return json_encode(
                        [
                            'status' => 1,
                            'message' => "Made Invisible Success!"
                        ]
                );
                
            }
        }
        if (!empty($getCandidate)) {
            if (!in_array($params['joborder_id'], explode(',', $getCandidate[0]["joborder_ids"]))) {
                $invisible_id = $getCandidate[0]["id"];
                $invisible = $invisibleTable->get($invisible_id);
                $invisibleTable->patchEntity($invisible, $params);
                $invisible->id = $invisible_id;
                $invisible->candidate_id = $params['candidate_id'];
                $invisible->joborder_ids = $getCandidate[0]["joborder_ids"] . "," . $params['joborder_id'];
                $invisibleTable->save($invisible);

                return json_encode(
                        [
                            'status' => 1,
                            'message' => "Made Invisible Success!"
                        ]
                );
                ;
            } else {
//                $visible_id = $getCandidate[0]["id"];
//                $visible = $invisibleTable->get($visible_id);
//                $jobs = explode(",", $getCandidate[0]["joborder_ids"]);
//                unset($jobs[array_search($params['joborder_id'], $jobs)]);
//                if (!empty($jobs)) {
//                    $invisibleTable->patchEntity($visible, $params);
//                    $visible->id = $visible_id;
//                    $visible->candidate_id = $params['candidate_id'];
//                    $visible->joborder_ids = implode(",", $jobs);
//                    $invisibleTable->save($visible);
//                } else {
//                    $invisibleTable->delete($visible);
//                }

                return json_encode(
                        [
                            'status' => 1,
                            'message' => "Made Visible Success!"
                        ]
                );
               
            }
        }
    }
    
    protected function data_save(){
        
    }
}
?>
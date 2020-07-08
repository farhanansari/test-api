<?php

/* * ************************************************************************************
 * Class name      : SendoutTable
 * Description     : Model for sendout process
 * Created Date    : 06-10-2016
 * Created By      : Akilan 
 * ************************************************************************************* */

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;

class SendoutTable extends Table {

    public function initialize(array $config) {
        $this->table('sendout');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

    public function lists() {
        return $selected_client_det = $this->find('list', [
                    'keyField' => 'sendout_id',
                    'valueField' => 'selected_for_client'
                ])->where(['selected_for_client <'=>3])->toArray();
    }
    
    /***************************************************************************
     * Function name   : match_lists
     * Description     : Function for fetching the sendout details with candidate match value greater than 0%
     * Created Date    : 13-06-2017
     * Created By      : Balasuresh A
     **************************************************************************/
    public function match_lists() {
        return $selected_client_det = $this->find('list', [
                    'keyField' => 'sendout_id',
                    'valueField' => 'selected_for_client'
                ])->where(['selected_for_client <'=>3,'candidate_match !=' => '0%'])->toArray();
    }
    
    /***************************************************************************
     * Function name   : submission_lists
     * Description     : Function for fetching the sendout details
     * Created Date    : 17-02-2017
     * Created By      : Balasuresh A
     **************************************************************************/
    public function submission_lists($candidate_id,$needed_field) {
        
        return $selected_client_det = $this->find('list', [
                    'keyField' => 'sendout_id',
                    'valueField' => $needed_field
                ])->where(['candidate_id' => $candidate_id,'history_status' => 1])->toArray();
    }
    
    /***************************************************************************
     * Function name   : sendout_list
     * Description     : Function for fetching the sendot details
     * Created Date    : 05-01-2017
     * Created By      : Balasuresh A
     **************************************************************************/
    public function sendout_lists() { 
        return $selected_client_det = $this->find('list', array(
        'keyField' => 'sendout_id','valueField' => 'selected_for_client',
        'conditions' => array('selected_for_client !=' => 2)
        ))->toArray();      
    }
    
    /***************************************************************************
     * Function name   : sendout_lists_grade
     * Description     : Function for fetching the sendot details
     * Created Date    : 05-01-2017
     * Created By      : Balasuresh A
     **************************************************************************/
    public function sendout_lists_grade() { 
        return $selected_client_det = $this->find('list', array(
        'keyField' => 'sendout_id','valueField' => 'selected_for_client',
        'conditions' => array('selected_for_client !=' => 2,'placement_id IS NOT NULL')
        ))->toArray();      
    }
    
    public function get_appointment_status() {
        return $selected_client_det = $this->find('list', [
                    'keyField' => 'sendout_id',
                    'valueField' => 'appointment_id'
                ])->toArray();
    }
    
    /**
     * To fetch job order id based on sendout id data
     * @return type
     */
    public function get_joborder_data() {
        $sendout_data = $this->find('list', [
                    'keyField' => 'sendout_id',
                    'valueField' => 'joborder_id'
                ]);
        if(!empty($sendout_data))
            return $sendout_data->toArray();
    }

    public function detail($sendout_id, $needed_field) {
        $data= $this->find()->select([$needed_field])->where(['sendout_id' => $sendout_id]);
        if(!empty($data))
            return $data->toArray();
    }

    /*     * **************************************************************************************
     * Function name   : check_appointment_present
     * Description     : Function for checking appointment if appointment exists for sendout.
     * Created Date    : 08-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function check_appointment_present($sendout_id) {
        $sendout_data = $this->find()->where(['selected_appointment_id IS NULL', 'sendout_id' => $sendout_id])->toArray();
        return $sendout_data;
    }

    /***************************************************************************************
     * Function name   : sendout_update_fields
     * Description     : Function for updating sendout fields
     * Created Date    : 15-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */
    public function sendout_update_fields($update_field,$sendout_id) {
        $this->query()->update()->set($update_field)
                ->where(['sendout_id' => $sendout_id])
                ->execute();       
    }
    
    /***************************************************************************************
     * Function name   : get_interview_placement
     * Description     : get interview,placement id from table 
     * Created Date    : 21-11-2016
     * Created By      : AnanthJP
     * ************************************************************************************* */
    public function get_interview_placement($sendout_id) {
        if(isset($sendout_id)) {            
            $query = $this->find()->where(['sendout_id' => $sendout_id]);            
            $row = $query->first();            
            $interviewserid= isset($row->interviewer_id)?$row->interviewer_id:null;
            $placementid= isset($row->placement_coordinator_id)?$row->placement_coordinator_id:null;
            $value = array('interviewer_id'=> $interviewserid,'placement_coordinator_id'=>$placementid);
            return $value;
        }
    }
    
     /***************************************************************************************
     * Function name   : get_interview_placement
     * Description     : get interview,placement id from table 
     * Created Date    : 21-11-2016
     * Created By      : AnanthJP
     * ************************************************************************************* */
    public function get_candidate_sendout($candidate_bullhorn_id,$sendout_id){
        return $this->find()->select()->where(['sendout_id' => $sendout_id,'candidate_id'=>$candidate_bullhorn_id])->toArray();
    }
    
    
    
    
      /***************************************************************************************
     * Function name   : get_placement_sendoutid
     * Description     : get sendout id details based on placement
     * Created Date    : 30-12-2016
     * Created By      : Akilan
     * ************************************************************************************* */
    public function get_placement_sendoutid($joborder_id,$placement_id){  
        $data= $this->find()->where(['joborder_id' =>$joborder_id ,'placement_id'=>$placement_id])->first();     
        return $data;
    }
    
    /***************************************************************************************
     * Function name   : biddrate_calculate
     * Description     : get sendout id details based on placement
     * Created Date    : 01-03-2017
     * Created By      : Balasuresh A
     * ************************************************************************************* */
    public function biddrate_calculate($bidd_rate) {
        $bidd_rate_val = '';
        if(!empty($bidd_rate)) {
            if ($bidd_rate > 105) {
                    $highMarkup = $bidd_rate * (75 / 100);
                    $bidd_rate_val = round($bidd_rate + $highMarkup);
            } else if ($bidd_rate < 20) {
                    $highMarkup = $bidd_rate * (50 / 100);
                    $bidd_rate_val = round($bidd_rate + $highMarkup);
            } else {
                    $highMarkup = (0.002 * $bidd_rate) + 0.54;
                    $bidd_rate_val = round($bidd_rate + ($highMarkup * $bidd_rate));
            }
        }
        return $bidd_rate_val;
    }
    
    /***************************************************************************************
     * Function name   : tax_calculate
     * Description     : calculate tax, service and benefits value based on candidate pay rate
     * Created Date    : 19-04-2017
     * Created By      : Balasuresh A
     * **************************************************************************************/
    public function tax_calculate($bidd_rate) {
        $bidd_rate_val = $markupRate = '';
        if(!empty($bidd_rate)) {
            if ($bidd_rate > 105) {
                    $highMarkup = $bidd_rate * (75 / 100);
                    $bidd_rate_val = round($bidd_rate + $highMarkup);
            } else if ($bidd_rate < 20) {
                    $highMarkup = $bidd_rate * (50 / 100);
                    $bidd_rate_val = round($bidd_rate + $highMarkup);
            } else {
                    $highMarkup = (0.002 * $bidd_rate) + 0.54;
                    $markupRate = round((($highMarkup * 100) * $bidd_rate) / 100) ;
                    $bidd_rate_val = round($bidd_rate + ($highMarkup * $bidd_rate));
            }
            if(!empty($markupRate)) {
                $markupRate = $markupRate;
            } else {
                $markupRate = round(($highMarkup * 10) / 10 );
            }
        }
        return $markupRate;
    }
}


?>
<?php

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\ORM\Rule\IsUnique;

class UsersTable extends Table {

    public function initialize(array $config) {
        $this->table('user');
        $this->displayField('username');
        $this->primaryKey('id');
        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator) {
        $validator = new Validator();
        $validator
                ->notEmpty('firstName', ('This field is required.'))
                ->notEmpty('lastName', ('This field is required.'))
                ->notEmpty('username', ('This field is required.'))
                ->notEmpty('password', ('This field is required.'))
                ->notEmpty('email', ('This field is required.'));

        $validator->add('username', ['unique' => [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'This Username has been already registered']
        ]);
        $validator->add('email', ['unique' => [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'This email has been already registered']
        ]);
        return $validator;
    }

    public function get_email($id = null, $by = null) {
        if ($by == 'user_id') {
            return $this->find()->select(['email', 'firstName'])->where(['id' => $id])->toArray();
        } else if ($by == 'bullhorn_entity_id') {
            return $this->find()->select(['id', 'email', 'firstName', 'lastName'])->where(['bullhorn_entity_id' => $id])->toArray();
        }
    }

    public function get_owner_info($user_id = null) {
        $owner_id = $this->find()->select(['owner_id'])->where(['id' => $user_id])->toArray();
        return $this->find()->select(['id', 'email', 'firstName', 'lastName', 'bullhorn_entity_id'])->where(['bullhorn_entity_id' => $owner_id[0]['owner_id']])->toArray();
    }

    public function get_sales_rep($company_id = null) {
        return $this->find()->select(['bullhorn_entity_id'])->where(['company_id' => $company_id, 'role' => SALESREP_ROLE])->toArray();
    }

    /*
     * *************************************************************************************
     * Function name   : get_sales_rep_id
     * Description     : To get the bull horn and owner id based on the company id given
     * Created Date    : 08-11-2016
     * Created By      : Balasuresh A
     * *************************************************************************************
     */

    public function get_sales_rep_id($company_id = null) {
        $salesRepIDsql = $this->find('all')->select(['bullhorn_entity_id', 'owner_id'])->where(['OR' => [['role' => SALESREP_ROLE, 'company_id' => $company_id], ['owner_id <>' => 0, 'company_id' => $company_id]]])->first();
        if (!empty($salesRepIDsql)) {
            $salesRepID = $salesRepIDsql->toArray();
            $salesRepVal = !empty($salesRepID['owner_id']) ? $salesRepID['owner_id'] : $salesRepID['bullhorn_entity_id'];
            return $salesRepVal;
        }
    }

    /*     * *************************************************************************************
     * Function name   : full_name
     * Description     : concat the first_name and last_name
     * Created Date    : 21-11-2016
     * Created By      : AnanthJP
     * ************************************************************************************* */

    public function full_name($bullhorn_entity_id) {
        $name = '';
        if (isset($bullhorn_entity_id)) {
            $query = $this->find()->where(['bullhorn_entity_id' => $bullhorn_entity_id]);
            if(!empty($query)) {
            $row = $query->first();
            $name = $row->firstName . " " . $row->lastName;
            } 
            return $value = array('full_name' => $name);
        }
    }
    
        /* **************************************************************************************
     * Function name   : name_with_email
     * Description     : concat the first_name and last_name with email
     * Created Date    : 19-04-2016
     * Created By      : Balasuresh A
     * **************************************************************************************/
    public function name_with_email($bullhorn_entity_id) {
        $name = $email = '';
        if (isset($bullhorn_entity_id)) {
            $query = $this->find()->where(['bullhorn_entity_id' => $bullhorn_entity_id]);
            if(!empty($query)) {
            $row = $query->first();
            $name = $row->firstName . " " . $row->lastName;
            $email = $row->email;
            } 
            return $value = array('full_name' => $name,'email' => $email);
        }
    }

    /*     * *************************************************************************************
     * Function name   : get_companyadmin_id
     * Description     : get company admin id for corresponding company
     * Created Date    : 23-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function get_companyadmin_id($company_id) {
        $companyAdmindet = $this->find('all')->select(['bullhorn_entity_id'])
                        ->where(['role' => COMPANY_ADMIN_ROLE, 'company_id' => $company_id])->first()->toArray();
        if (!empty($companyAdmindet)) {
            $companyAdminID = !empty($companyAdmindet['bullhorn_entity_id']) ? $companyAdmindet['bullhorn_entity_id'] : "";
            return $companyAdminID;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : get_hiringmanager_id
     * Description     : To get the bull horn of hiring managers based on the company id given
     * Created Date    : 10-12-2016
     * Created By      : Balasuresh A
     * *************************************************************************************
     */

    public function get_hiringmanager_id($company_id) {
        $hiringManagerId = $this->find('all')->select(['bullhorn_entity_id'])
                ->where(['role' => HIRINGMANAGER_ROLE, 'company_id' => $company_id]);
        if (!empty($hiringManagerId)) {
            foreach ($hiringManagerId as $hiringManager) {
                $hiringID[] = $hiringManager->bullhorn_entity_id;
            }
            return $hiringID;
        }
    }

    /*
     * *************************************************************************************
     * Function name   : get_hiringmanager_list
     * Description     : To get hiring manager first & last name based on bullhorn entity id
     * Created Date    : 12-12-2016
     * Created By      : Balasuresh A
     * *************************************************************************************
     */

    public function get_hiringmanager_list($id) {
        return $users = $this->find('list', [
                    'keyField' => 'bullhorn_entity_id',
                    'valueField' => function ($e) {
                return $e->get('firstName') . " " . $e->get('lastName');
            }
                ])->where(['bullhorn_entity_id IN' => $id])->toArray();
    }

}

?>
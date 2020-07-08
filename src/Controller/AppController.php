<?php

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link      http://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Network\Session\DatabaseSession;
use Cake\Validation\Validator;
use Cake\Network\Http\Client;
use Cake\Datasource\EntityInterface;
use Cake\Mailer\Email;
use Cake\Core\Configure;

define('WEB_SERVER_ADDR', Configure::read('server_address')); // to set web server address
define('WEB_SERVER_ADDR_MARKETING', Configure::read('marketing_server_address')); // to set web server address
define('CANDIDATE_ROLE', 'contractor'); //contractor role constant variable
define('COMPANY_ADMIN_ROLE', 'companyAdmin');
define('HIRINGMANAGER_ROLE', 'hiringManager');
define('SALESREP_ROLE', 'salesRepresentative');
define('SUPER_ADMIN_ROLE', 'superadmin');
define('HIDDEN_COMPANY_TEXT','Confidential Company');
define('PUBLIC_SECRET_KEY', 'fLT715sCtldIB2xKlXwODylpbPVilZMhP'); //for public page access key
define('ACCEPT_STATUS', 1);
define('APPROVE_STATUS', 2);
define('REJECT_STATUS', 2);
define('PLACEMENT_REJECT_STATUS', 3);
define('NAVIGATION_JOB', 1);
define('NAVIGATION_APPOINTMENT', 2);
define('NAVIGATION_OFFER', 3);
define('NAVIGATION_APPOINTMENT_DETAIL', 4);
define('EMAIL_FROM_ADDRESS','admin@peoplecaddie.com'); /* for setting the email from address */

//define('DRIVE_REDIRECT_URL_LOCAL','http://localhost/peoplecaddie-api/users/driveFileUpload');
//define('DRIVE_REDIRECT_URL_DEV','http://dev.peoplecaddie.com/peoplecaddie-api/users/driveFileUpload');

/* Application Status */
define('APPLICATION_PENDING', 0);
define('INTERVIEW_REQUESTED', 1);
define('INTERVIEW_CONFIRMED', 2);
define('OFFER_RECEIVED', 3);
define('OFFER_REJECTED', 4);
define('ASSIGNMENT_CONFIRMED', 5);
define('APPLICATION_UNSUCCESSFUL', 6);
define('ASSIGNMENT_UNDERWAY', 7);
define('AWAITING_PERFORMANCE_RATING', 8);
define('ASSIGNMENT_CLOSED', 9);
define('FAILED_TO_SHOW', 10);

define('APPOINTMENT_SCREEN', 1);
define('REVIEW_CONTRACTOR', 1);
define('REVIEW_CONTRACTOR_REMINDER', 2);
define('REVIEW_CONTRACTOR_REMINDER_SR', 3);
define('INTERVIEW_REQUEST', 4); //contractor role constant variable
define('INTERVIEW_REQUEST_REMINDER_1', 5);
define('INTERVIEW_REQUEST_REMINDER_2', 6);
define('CONTRACTOR_UNRESPONSIVE_INTERVIEW_REQUEST', 7);
define('INTERVIEW_CONF_NOTIFICATION_A', 8);
define('INTERVIEW_CONF_NOTIFICATION_B', 9);
define('INTERVIEW_APP_REMINDER_1A', 10);
define('INTERVIEW_APP_REMINDER_1B', 11);
define('INTERVIEW_APP_REMINDER_2A', 12);
define('INTERVIEW_APP_REMINDER_2B', 13);
define('INTERVIEW_FOLLOW_UP_1', 14);
define('INTERVIEW_FOLLOW_UP_2', 15);
define('UNRESPONSIGN_INTERVIEW_1', 16);
define('UNRESPONSIGN_INTERVIEW_2', 17);
define('ASSIGNMENT_PENDING_APPLICANT_CONF', 18);
define('CONF_OF_ASSIGNMENT', 19);
define('ASSIGNMENT_AWAITING_REMINDER_1', 20);
define('ASSIGNMENT_AWAITING_REMINDER_2', 21);
define('CONTRACTOR_UNRESPONSIVE_CONF_REQUEST', 22);
define('FINAL_ASSIGNEMENT_CONF_A', 23);
define('FINAL_ASSIGNEMENT_CONF_B', 24);
define('FINAL_ASSIGNEMENT_CONF_C', 25);
define('CONTRACTOR_SENDOUT_REJECTION', 26); /* Notice of Unsuccessful Application */
define('CONTRACTOR_PRELIMINARY_MATCH', 27);
define('PROFILE_UPDATE_NOTIFICATION', 28);
define('STATUS_UPDATE_REMINDER', 29);
define('APPLICATION_RECEIVED_31', 31);
define('CLIENT_ADMIN_REGISTRATION', 32);
define('CLIENT_ADMIN_REGISTRATION_2HRS', 33);
define('CLIENT_ADMIN_REGISTRATION_8HRS', 34);
define('CLIENT_ADMIN_REGISTRATION_24HRS', 35);
define('CLIENT_ADMIN_REGISTRATION_REQUEST_RECEIVED', 36);
define('SALESREP_INITIAL_MATCH_LIST', 38);
define('APPLICATION_RECEIVED_39', 39);
define('CONTRACTOR_SLATE_READY', 40);
define('CONTRACTOR_SLATE_UPDATE_A', 41);
define('CONTRACTOR_SLATE_UPDATE_B', 42);
define('CONTRACTOR_EMAIL_VALIDATION_LINK', 43);
define('CONTRACTOR_EMAIL_VALIDATION_REMINDER_1', 44);
define('CONTRACTOR_EMAIL_VALIDATION_REMINDER_2', 45);
define('CONTRACTOR_EMAIL_VALIDATION_REMINDER_3', 46);
define('PROFILE_COMPLETION_REMINDER_1', 47);
define('PROFILE_COMPLETION_REMINDER_2', 48);
define('PROFILE_COMPLETION_REMINDER_3', 49);
define('HM_WELCOME', 51);
define('HM_WELCOME_REMINDER_1', 52);
define('HM_WELCOME_REMINDER_2', 53);
define('HM_FOLLOW_UP_1', 54);
define('HM_FOLLOW_UP_2', 55);
define('PERFORMANCE_RATING_REQUEST', 56);
define('PERFORMANCE_RATING_REMINDER_1', 57);
define('PERFORMANCE_RATING_REMINDER_2', 58);
define('PERFORMANCE_RATING_SR_FOLLOW_UP_1', 59);
define('PERFORMANCE_RATING_SR_FOLLOW_UP_2', 60);
define('PERFORMANCE_RATING_ADMIN_FOLLOW_UP', 61);

define('ALTER_INTERVIEW_SCHEDULE_SR',62);
define('ALTER_INTERVIEW_SCHEDULE_HM',63);
define('UNSUCCESSFUL_APPLICANTS',64);
/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link http://book.cakephp.org/3.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize() {
        parent::initialize();
        /* to get site base URL */
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? 'https' : 'http';
        $serverURL = $protocol . '://' . $_SERVER['HTTP_HOST'] . $this->request->webroot;
        
        define('SITE_BASEURL',$serverURL);
        define('DRIVE_REDIRECT_URL_DEV',$serverURL.'users/driveFileUpload');
        
        $this->loadComponent('Auth');
        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('BullhornConnection');
        $this->loadComponent('BullhornCurl');
    }

    public function beforeFilter(Event $event) {
        parent::beforeFilter($event);
        $this->AuthCheck();
    }

    public function AuthCheck($params = null) {
        $this->loadModel('User');
        $params = apache_request_headers();
        $exeception_pages = array(
            'Appointment/lists',
            'Notifications/polling',
            'Appointment/selective',
            'Appointment/update',
            'Placement/add',
            'Users/login',
            'Users/options',
            'Users/lists',
            'Users/contractors',
            'Users/companies',
            'Users/helpContact',
            'Users/appRedirect',
            'Users/options',
            'Users/metaData',
            'CompanyAdministrator/add',
            'HiringManager/update',
            'Candidate/add',
            'Candidate/view',
            'Candidate/update',
            'JobOrder/performance',
            'JobOrder/all',
            'JobOrder/view',
            'JobOrder/add',
            'JobOrder/update',
            'JobOrder/delete',
            'JobOrder/performance',
            'JobOrder/skills_add',
            'JobOrder/filter',
            'Candidate/test',
            'Sendout/view',
            'Sendout/update',
            'GetApplicants/index',
            'GetJobsubmissions/index',
            'GetSendouts/index',
            'CompanyAdministrator/add',
            'CandidateSkills/category_add',
            'CandidateSkills/category_all',
            'CandidateSkills/upload',
            'Users/forgotPassword',
            'Users/confirmation',
            'Users/resetPassword',
            'Notifications/index',
            'Notifications/test',
            'CandidateSkills/category_skills',
            'CandidateSkills/bhadd',
            'CandidateSkills/bhall',
            'CandidateSkills/bhdelete',
            'Users/checkuser',
            'Notifications/testsms',
            'Notifications/sendSms', 
            'Candidate/fileUpload', 
            'Candidate/fileUpload1',
            'Users/fetchCategory',
            'Users/fetchTitlesAll',
            'Users/fetchSkills',
            'Users/fetchAllTitles',
            'Users/fetchAllSkills',
            'Notifications/remove_notify',
            'Sendout/remove_sendout',
            'Users/driveFileDownload',
            'Notifications/sendEmail', 
        );
        $nonauthPages = array(
            'Users/appRedirect',
            'Notifications/index',
            'Notifications/test',
            'Notifications/jobmatch',
            'SalesRepresentative/update',
            'Users/fetchoptions',
            //'JobOrder/job_candidate_match',
            'Notifications/matchingCandidates',
            'Notifications/statusReminder',
            'Users/k2','Users/privacyPolicy',
            'Users/termsConditions',
            //'Candidate/add_reference',
            'Candidate/reference_confirmation',
            'JobOrder/reference_performance_add',
            'Users/driveFileUpload',
            'Candidate/category_update',
            'Candidate/skills_update',
            'Users/driveFileDownload',
            'CompanyAdministrator/add_temp_hm',
        );
        $access_pages = array(
            SUPER_ADMIN_ROLE => array(
                'Appointment/view',
                'Appointment/edit',
                'Appointment/delete',
                'Candidate/add',
                'Candidate/view',
                'Candidate/update',
                'Candidate/delete',
                'Candidate/submissions',
                'Candidate/slatView',
                'Candidate/slatView1',
                'Candidate/slateSort',
                'Candidate/candidate_performance',
                'Candidate/changePassword',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'JobOrder/add',
                'JobOrder/all',
                'JobOrder/view',
                'JobOrder/update',
                'JobOrder/delete',
                'JobOrder/jobclose',
                'JobOrder/invisible',
                'JobOrder/skills_add',
                'JobOrder/invite_contractors',
                'JobOrder/filter',
                'Note/add',
                'Note/view',
                'Note/edit',
                'Note/delete',
                'Sendout/add',
                'Sendout/view',
                'Sendout/update',
                'Sendout/delete',
                'HiringManager/add',
                'HiringManager/view',
                'HiringManager/update',
                'HiringManager/delete',
                'CompanyAdministrator/add',
                'CompanyAdministrator/view',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'SalesRepresentative/add',
                'SalesRepresentative/view',
                'SalesRepresentative/update',
                'SuperAdmin/add',
                'SuperAdmin/view',
                'SuperAdmin/update',
                'JobSubmission/add',
                'Jobsubmission/view',
                'Jobsubmission/update',
                'Jobsubmission/delete',
                'CandidateWorkHistory/view',
                'Users/selectAppointment',
                'Bookmarks/add',
                'Bookmarks/delete',
                'Bookmarks/all',
                'Candidate/fileUpload',
                'Candidate/file_download',
                'Candidate/file_delete',
                'Candidate/slatView',
                'Candidate/headshot_upload',
                'Candidate/headshot_delete',
                'CandidateSkills/add',
                'CandidateSkills/delete',
                'CandidateSkills/all',
                'CandidateSkills/category_add',
                'CandidateSkills/category_all',
                'CandidateSkills/skills_add',
                'Users/lists',
                'Users/companies',
                'Users/contractors',
                'Users/inviteFriends',
                'Applications/index',
                'SuperAdmin/view',
                'SuperAdmin/update',
                'CandidateSkills/category_skills',
                'CandidateSkills/bhadd',
                'CandidateSkills/bhall',
                'CandidateSkills/bhdelete',
                'Applications/client_dashboard',
                'Users/adminDashboard',
                'Users/adminApproval',
                'Notifications/polling',
                'Candidate/headshot_cropupload',
                'Users/fetchTitle',
                'Users/fetchCategory',
                'Users/fetchTitlesAll',
                'Users/fetchSkills',
                'Users/fetchTitleSkill',
                'Users/fetchAllTitles',
                'Users/fetchAllSkills',
                'Placement/delete',
            ),
            COMPANY_ADMIN_ROLE => array(
                'Appointment/add',
                'Appointment/view',
                'Appointment/edit',
                'Appointment/delete',
                'Candidate/add',
                'Candidate/view',
                'Candidate/update',
                'Candidate/delete',
                'Candidate/submissions',
                'Candidate/slatView',
                'Candidate/slatView1',
                'Candidate/changePassword',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'JobOrder/add',
                'JobOrder/all',
                'JobOrder/view',
                'JobOrder/update',
                'JobOrder/delete',
                'JobOrder/jobclose',
                'JobOrder/invisible',
                'JobOrder/skills_add',
                'JobOrder/invite_contractors',
                'JobOrder/filter',
                'Note/add',
                'Note/view',
                'Note/edit',
                'Note/delete',
                'Sendout/add',
                'Sendout/view',
                'Sendout/update',
                'Sendout/delete',
                'HiringManager/add',
                'HiringManager/view',
                'HiringManager/update',
                'HiringManager/delete',
                'CompanyAdministrator/add',
                'CompanyAdministrator/view',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'SalesRepresentative/add',
                'SalesRepresentative/view',
                'JobSubmission/add',
                'Jobsubmission/view',
                'Jobsubmission/update',
                'Jobsubmission/delete',
                'CandidateWorkHistory/view',
                'Users/selectAppointment',
                'Bookmarks/add',
                'Bookmarks/delete',
                'Bookmarks/all',
                'Candidate/fileUpload',
                'Candidate/file_download',
                'Candidate/file_delete',
                'Candidate/headshot_upload',
                'Candidate/headshot_delete',
                'CandidateSkills/add',
                'CandidateSkills/delete',
                'CandidateSkills/all',
                'CandidateSkills/category_add',
                'CandidateSkills/category_all',
                'CandidateSkills/skills_add',
                'Users/lists',
                'Users/companies',
                'Users/contractors',
                'Users/inviteFriends',
                'Applications/index',
                'CandidateSkills/category_skills',
                'CandidateSkills/bhadd',
                'CandidateSkills/bhall',
                'CandidateSkills/bhdelete',
                'Applications/client_dashboard',
                'Candidate/candidate_performance',
                'Notifications/polling',
                'Candidate/headshot_cropupload',
                'Users/fetchTitle',
                'Users/fetchCategory',
                'Users/fetchTitlesAll',
                'Users/fetchSkills',
                'Users/fetchTitleSkill',
                'Users/fetchAllTitles',
                'Users/fetchAllSkills',
                'Placement/delete',
            ),
            CANDIDATE_ROLE => array(
                'Appointment/lists',
                'Appointment/selective',
                'Appointment/appointment_view',
                'Candidate/view',
                'Candidate/update',
                'Candidate/ratings',
                'Candidate/submissions',
                'Candidate/submissions1',
                'Candidate/add_education',
                'Candidate/add_reference',
                'Candidate/file_check',
                'Candidate/reference_delete',
                'CandidateWorkHistory/add',
                'CandidateWorkHistory/delete',
                'CandidateWorkHistory/view',
                'JobOrder/all',
                'JobOrder/view',
                'JobOrder/invisible',
                'JobOrder/skills_add',
                'JobOrder/invite_contractors',
                'JobOrder/filter',
                'JobSubmission/add',
                'Jobsubmission/view',
                'Jobsubmission/update',
                'Jobsubmission/delete',
                'Placement/add',
                'Sendout/add',
                'Sendout/view',
                'Sendout/update',
                'Sendout/delete',
                'Users/selectAppointment',
                'Bookmarks/add',
                'Bookmarks/delete',
                'Bookmarks/all',
                'Candidate/fileUpload',
                'Candidate/file_download',
                'Candidate/file_delete',
                'Candidate/headshot_upload',
                'Candidate/headshot_delete',
                'CandidateSkills/add',
                'CandidateSkills/delete',
                'CandidateSkills/all',
                'CandidateSkills/category_add',
                'CandidateSkills/category_all',
                'CandidateSkills/upload',
                'Applications/index',
                'CandidateSkills/category_skills',
                'CandidateSkills/bhadd',
                'CandidateSkills/bhall',
                'CandidateSkills/bhdelete',
                'CandidateSkills/skills_add',
                'Applications/client_dashboard',
                'Users/inviteFriends',
                'Users/fetchoptions',
                'Candidate/candidateRatings',
                'Candidate/changePassword',
                'Notify/update',
                'Notifications/polling',
                'Candidate/headshot_cropupload', 'Candidate/fileUpload1',
                'Users/driveFileUpload',
                'Users/fetchTitle',
                'Users/fetchCategory',
                'Users/fetchTitlesAll',
                'Users/fetchSkills',
                'Users/fetchTitleSkill',
                'Users/fetchAllTitles',
                'Users/fetchAllSkills',
                'Placement/delete',
            ),
            HIRINGMANAGER_ROLE => array(
                'Appointment/add',
                'Appointment/view',
                'Appointment/edit',
                'Appointment/delete',
                'Appointment/hm_interview_data',
                'Candidate/add',
                'Candidate/view',
                'Candidate/update',
                'Candidate/delete',
                'Candidate/submissions',
                'Candidate/candidate_performance',
                'Candidate/slatView',
                'Candidate/slatView1',
                'Candidate/slate_login_update',
                'Candidate/changePassword',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'JobOrder/add',
                'JobOrder/all',
                'JobOrder/view',
                'JobOrder/update',
                'JobOrder/delete',
                'JobOrder/jobclose',
                'JobOrder/invisible',
                'JobOrder/skills_add',
                'JobOrder/invite_contractors',
                'JobOrder/filter',
                'JobSubmission/add',
                'Note/add',
                'Note/view',
                'Note/edit',
                'Note/delete',
                'Sendout/add',
                'Sendout/view',
                'Sendout/update',
                'Sendout/delete',
                'HiringManager/view',
                'HiringManager/update',
                'HiringManager/delete',
                'CompanyAdministrator/add',
                'CompanyAdministrator/view',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'SalesRepresentative/add',
                'SalesRepresentative/view',
                'JobSubmission/add',
                'Jobsubmission/view',
                'Jobsubmission/update',
                'Jobsubmission/delete',
                'CandidateWorkHistory/view',
                'Users/selectAppointment',
                'Bookmarks/add',
                'Bookmarks/delete',
                'Bookmarks/all',
                'Candidate/fileUpload',
                'Candidate/file_download',
                'Candidate/file_delete',
                'Candidate/headshot_upload',
                'Candidate/headshot_delete',
                'CandidateSkills/add',
                'CandidateSkills/delete',
                'CandidateSkills/all',
                'CandidateSkills/category_add',
                'CandidateSkills/category_all',
                'CandidateSkills/skills_add',
                'Users/lists',
                'Users/companies',
                'Users/contractors',
                'Users/inviteFriends',
                'Applications/index',
                'CandidateSkills/category_skills',
                'CandidateSkills/bhadd',
                'CandidateSkills/bhall',
                'CandidateSkills/bhdelete',
                'Applications/client_dashboard',
                'Notifications/polling',
                'Candidate/headshot_cropupload',
                'Users/fetchTitle',
                'Users/fetchCategory',
                'Users/fetchTitlesAll',
                'Users/fetchSkills',
                'Users/fetchTitleSkill',
                'Users/fetchAllTitles',
                'Users/fetchAllSkills',
                'Placement/delete',
            ),
            SALESREP_ROLE => array(
                'Appointment/add',
                'Appointment/view',
                'Appointment/edit',
                'Appointment/delete',
                'Candidate/add',
                'Candidate/view',
                'Candidate/update',
                'Candidate/delete',
                'Candidate/submissions',
                'Candidate/slatView',
                'Candidate/slatView1',
                'Candidate/slateSort',
                'Candidate/candidate_performance',
                'Candidate/changePassword',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'JobOrder/add',
                'JobOrder/all',
                'JobOrder/view',
                'JobOrder/update',
                'JobOrder/delete',
                'JobOrder/jobclose',
                'JobOrder/invisible',
                'JobOrder/skills_add',
                'JobOrder/invite_contractors',
                'JobOrder/filter',
                'JobSubmission/add',
                'Note/add',
                'Note/view',
                'Note/edit',
                'Note/delete',
                'Sendout/add',
                'Sendout/view',
                'Sendout/update',
                'Sendout/delete',
                'HiringManager/add',
                'HiringManager/view',
                'HiringManager/update',
                'HiringManager/delete',
                'CompanyAdministrator/add',
                'CompanyAdministrator/view',
                'CompanyAdministrator/update',
                'CompanyAdministrator/delete',
                'SalesRepresentative/add',
                'SalesRepresentative/view',
                'SalesRepresentative/update',
                'SuperAdmin/add',
                'SuperAdmin/view',
                'SuperAdmin/update',
                'JobSubmission/add',
                'Jobsubmission/view',
                'Jobsubmission/update',
                'Jobsubmission/delete',
                'CandidateWorkHistory/view',
                'Users/selectAppointment',
                'Bookmarks/add',
                'Bookmarks/delete',
                'Bookmarks/all',
                'Candidate/fileUpload',
                'Candidate/file_download',
                'Candidate/file_delete',
                'Candidate/headshot_upload',
                'Candidate/headshot_delete',
                'CandidateSkills/add',
                'CandidateSkills/delete',
                'CandidateSkills/all',
                'CandidateSkills/category_add',
                'CandidateSkills/category_all',
                'CandidateSkills/skills_add',
                'Users/lists',
                'Users/companies',
                'Users/contractors',
                'Users/inviteFriends',
                'Applications/index',
                'CandidateSkills/category_skills',
                'CandidateSkills/bhadd',
                'CandidateSkills/bhall',
                'CandidateSkills/bhdelete',
                'Applications/client_dashboard',
                'Users/adminDashboard',
                'Users/adminApproval',
                'Notifications/polling',
                'Candidate/headshot_cropupload',
                'Users/fetchTitle',
                'Users/fetchCategory',
                'Users/fetchTitlesAll',
                'Users/fetchSkills',
                'Users/fetchTitleSkill',
                'Users/fetchAllTitles',
                'Users/fetchAllSkills',
                'Placement/delete',
            )
        );
        $current_page = $this->request->params['controller'] . '/' . $this->request->params['action'];
        $token = '';
        if (isset($params['Authorization'])) {
            $token = $params['Authorization'];
            $userdata = $this->User->find('all', ['conditions' => ['access_token' => strval($token)]])->first();
        }

        //For allowing exception page with common public secret key & role based action allow permission
        if ((isset($userdata['isActive']) && $userdata['isActive'] && isset($userdata->role) && in_array($current_page, $access_pages[$userdata['role']])) || (in_array($current_page, $exeception_pages) && PUBLIC_SECRET_KEY == $token) || in_array($current_page, $nonauthPages)) {
            $this->Auth->allow();
        } else {
            echo json_encode(array('result' => 0, 'error' => 'You cannot access this endpoint'));
            return;
        }
    }

    /**
     * Before render callback.
     *
     * @param \Cake\Event\Event $event The beforeRender event.
     * @return void
     */
    public function beforeRender(Event $event) {
        if (!array_key_exists('_serialize', $this->viewVars) &&
                in_array($this->response->type(), ['application/json', 'application/xml'])
        ) {
            $this->set('_serialize', true);
        }
    }

    /*     * ************************************************************************************
     * Function name   : user_save
     * Description     : For storing user detail
     * Created Date    : 25-08-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function user_save($params, $role, $type) {
        $userTable = TableRegistry::get('Users');
        $user = $userTable->newEntity();
        $user->access_token = md5(uniqid(mt_rand(), true));
        if (!isset($params['username']))
            $user->username = $params['email'];
        else
            $user->username = $params['username'];
        if (isset($params['bullhorn_entity_id']) && !empty($params['bullhorn_entity_id']))
            $user->bullhorn_entity_id = $params['bullhorn_entity_id'];
        if (isset($params['company_id']) && !empty($params['company_id']))
            $user->company_id = $params['company_id'];
        else
            $user->company_id = 0;
        $user->password = $params['password'];
        $user->email = $params['email'];
        $user->firstName = $params['firstName'];
        $user->device_id = isset($params['device_id']) ? $params['device_id'] : "";
        $notify = isset($params['notify']) && $params['notify'] == 1 ? 1 : 0;
        $social = isset($params['social']) ? 1 : 0;
        if (isset($params['device_type']))
            $user->device_type = $params['device_type'];
        $user->lastName = $params['lastName'];
        $user->phone = isset($params['phone']) ? $params['phone'] : "";
        $user->role = $role;
        $user->headshot = isset($params['headshot']) ? $params['headshot'] : "";
        if (($role == COMPANY_ADMIN_ROLE && isset($params['pcwebsignup']) && $params['pcwebsignup'])) {
            $user->isActive = 0;
        } else {
            $user->isActive = 1;
        }
        $userTable->patchEntity($user, $params);
        if ($type == 'validate' && !empty($user->errors()))
            return $user->errors();

        if ($type == 'save' && $user_dt = $userTable->save($user)) {
            if (($notify == 1) || (!isset($params['notify']))) {
                $user_dt->social = $social;
                $user_dt->dup_password = $params['password']; // to send password in mail
                if (($role == COMPANY_ADMIN_ROLE && isset($params['pcwebsignup']) && $params['pcwebsignup']) || ($role == COMPANY_ADMIN_ROLE) || ($role == HIRINGMANAGER_ROLE) || ($role == CANDIDATE_ROLE)) {
                    if ($role == HIRINGMANAGER_ROLE && isset($params['createdByUserId'])) {
                        $createdByUser = $userTable->get($params['createdByUserId']);
                        $this->email($user_dt, $role, $createdByUser);
                    } else {
                        $this->email($user_dt, $role);
                    }
                } else {
                    $this->email($user_dt);
                }
            }
            return $user->access_token;
        } else {
            return $user->errors();
        }
    }

    /*     * ***************************************************************************************
     * Function name   : user_update
     * Description     : For updating user detail
     * Created Date    : 26-08-2016
     * Modified Date   : 29-08-2016
     * Created By      : Akilan
     * type => user_id => params['id'] is primary key id of user id
     * bullhorn_id => params['id'] is bullhorn it
     * ************************************************************************************* */

    public function user_update($id, $params, $where_type) {
        $userTable = TableRegistry::get('Users');
        switch ($where_type) {
            case 'user_id';
                $user_det = $userTable->get($id);
                $user_det->bullhorn_entity_id = $params;
                if ($userTable->save($user_det)) {
                    return $user_det;
                }
                break;
            case 'bullhorn_id':
                $user_id = 0;
                $user_det = $userTable->find()->select(['id'])->where(['bullhorn_entity_id' => $id])->toArray();
                if (!empty($user_det))
                    $user_id = $user_det[0]["id"];
                $user_det = $userTable->get($user_id);

                $user_det->id = $user_id;
                $params['id']=$user_id;
                if (!isset($params['username']) && empty($params['username'])) {
                    if (isset($params['email']) && !empty($params['email'])){
                        $user_det->username = $params['email'];
                        $params['username']=$params['email'];
                    }
                }
                else {
                    if (isset($params['username']) && !empty($params['username']))
                        $user_det->username = $params['username'];
                }
                if (isset($params['password']) && !empty($params['password']))
                    $user_det->password = $params['password'];
                if (isset($params['firstName']) && !empty($params['firstName']))
                    $user_det->firstName = $params['firstName'];
                if (isset($params['lastName']) && !empty($params['lastName']))
                    $user_det->lastName = $params['lastName'];
                if (isset($params['email']) && !empty($params['email']))
                    $user_det->email = $params['email'];
                if (isset($params['phone']) && !empty($params['phone']))
                    $user_det->phone = $params['phone'];
                if (isset($params['company_id']) && !empty($params['company_id']))
                    $user_det->company_id = $params['company_id'];
                if (isset($params['rating']) && !empty($params['rating']))
                    $user_det->rating = $params['rating'];
                if (isset($params['isActive']))
                    $user_det->isActive = $params['isActive'];
                if (isset($params['owner_id']))
                    $user_det->owner_id = $params['owner_id'];
                if (isset($params['status']))
                    $user_det->status = $params['status'];
                
                $userTable->patchEntity($user_det,$params);           
                $notify = (isset($params['notify']) && $params['notify'] == 1) ? 1 : 0;
                $params['id']=$id;
                if ($user=$userTable->save($user_det)) {
                    $email = new Email();
                    if ($user->role == COMPANY_ADMIN_ROLE && isset($params['isActive']) && $params['isActive']) {
                        $email->template('client_admin_approval_welcome', 'user')
                                ->emailFormat('html')
                                ->viewVars(['var' => ['firstName' => $user->firstName, 'lastName' => $user->lastName]])
                                ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                                ->to($user->email)
                                ->subject('Welcome Email to PeopleCaddie!')
                                ->send();
                    }
                    
                    if ($notify == 1 && isset($params['password']) && !empty($params['password'])){
                        $updatedBy = '';
                        if (isset($params['updatedByUserId'])) {
                           $updatedByUser = $userTable->get($params['updatedByUserId']);
                           $updatedBy = ' '.$updatedByUser->firstName.' '.$updatedByUser->lastName.' a '.$updatedByUser->role.'.';
                          }else{
                            $updatedBy = ' yourself.';  
                          }
                           $email->template('password_updated_by', 'user')
                               ->emailFormat('html')
                               ->viewVars(['var' => ['firstName' => $user->firstName, 'lastName' => $user->lastName, 'password' => $params['password'],'updatedBy' => $updatedBy ]])
                               ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                               ->to($user->email)
                               ->subject('Your password has been updated!')
                               ->send();
                    }else if($notify == 1){
                         $email->template('profile_updated_by', 'user')
                               ->emailFormat('html')
                               ->viewVars(['var' => ['firstName' => $user->firstName, 'lastName' => $user->lastName]])
                               ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                               ->to($user->email)
                               ->subject('Your profile has been updated!')
                               ->send();
                    }
                    
                    return 1;
                } else {                    
                    return $user_det->errors();
                }
                break;
        }
    }

    /**
     * Internal function : format_validation_message
     * Description       : For changing error message format
     * Created By        : Akilan
     * Created Date      : 30-08-2016
     * parameter         : error message array
     */
    public function format_validation_message($error_ar) {
        $error_msg = array();
        foreach ($error_ar as $errors) {
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    $error_msg[] = $error . ".";
                }
            } else {
                $error_msg[] = $errors . ".";
            }
        }

        if (!empty($error_msg))
            return "Please fix the following error(s):" . implode(",", $error_msg);
    }

    /**
     * Retrieved role based company ids
     * @param type $bullhorn_entity_id
     * @return array
     */
    function get_owned_company($role, $bullhorn_entity_id = null) {
        switch ($role) {
            case in_array($role, array(SUPER_ADMIN_ROLE, CANDIDATE_ROLE)):
                $company_det = $this->User->find('all')->select(['company_id']);
                break;
            case SALESREP_ROLE:
                $company_det = $this->User->find('all')->select(['company_id'])
                                ->where(['OR' => [['owner_id' => $bullhorn_entity_id], ['bullhorn_entity_id' => $bullhorn_entity_id]]])->toArray();
                break;
            case COMPANY_ADMIN_ROLE || HIRINGMANAGER_ROLE:
                $company_det = $this->User->find('all')->select(['company_id'])
                                ->where(['bullhorn_entity_id' => $bullhorn_entity_id])->toArray();
                break;
        }

        $company_ids = array();
        if (!empty($company_det)) {
            foreach ($company_det as $company_sgl) {
                if ($company_sgl['company_id'] != 0)
                    array_push($company_ids, $company_sgl['company_id']);
            }
        }
        return array_unique($company_ids);
    }

    function email($user, $role = null, $createdByUser = null) {
        $email = new Email();
        if ($role != null) { // if role set, then we need to send email to confirm email address as per notification process
            $token = md5(uniqid(mt_rand(), true));
            $subject='Your registration request has been received';
            $emailVerifyTable = TableRegistry::get('EmailVerification');
            $notificationsTable = TableRegistry::get('Notifications');
            $getToken = $emailVerifyTable->find()->select()->where(['user_id' => $user->id, 'is_verified' => 0, 'is_user_registr' => 1])->toArray();
            if (!empty($getToken) && isset($getToken[0]['token']) && !empty($getToken[0]['token'])) {
                $token = $getToken[0]['token'];
            } else {
                $token = md5(uniqid(mt_rand(), true));
                $everify = $emailVerifyTable->newEntity();
                $everify->user_id = $user->id;
                $everify->token = $token;
                $everify->is_verified = 0;
                $everify->is_user_registr = 1;
                $everify->created_at = strtotime(date('d-m-Y h:i a', time()));
                if ($verify = $emailVerifyTable->save($everify)) {
                    switch ($role) {
                        case COMPANY_ADMIN_ROLE:
                            $notify = [
                                'client_bullhorn_id' => $user->bullhorn_entity_id,
                                'super_or_sales_admin' => $user->owner_id,
                                'email_verify_id' => $verify->id,
                                'fixed_timestamp' => $verify->created_at,
                                'type' => 'client_registration_process_save'
                            ];
                            $subject = 'Your registration request has been received.!';
                            break;
                        case HIRINGMANAGER_ROLE:
                            if ($createdByUser != null && $createdByUser->role == COMPANY_ADMIN_ROLE) {
                            $companyAdminId = $createdByUser->bullhorn_entity_id;
                            } else {
                                $companyAdminId = $user->company_id;
                            }
                            $notify = [
                                'client_bullhorn_id' => $user->bullhorn_entity_id,
                                'company_admin' => $companyAdminId,
                                'email_verify_id' => $verify->id,
                                'fixed_timestamp' => $verify->created_at,
                                'type' => 'hiring_manager_registration'
                            ];
                            $subject = 'Your registration request has been received.!';
                            break;
                        case CANDIDATE_ROLE:
                            $user_id = $this->User->find('all')->select(['bullhorn_entity_id'])->where(['role' => 'superadmin']);
                            $notify = [
                                'client_bullhorn_id' => $user->bullhorn_entity_id,
                                'super_or_sales_admin' => $user_id->first()->bullhorn_entity_id, // super admin id
                                'email_verify_id' => $verify->id,
                                'fixed_timestamp' => $verify->created_at,
                                'type' => 'contractor_registration'
                            ];
                            $subject = 'Your registration request has been received.!';
                            break;
                    }
                    $notificationsTable->notification_data($notify);
                }
            }
            if (($user->device_type == 3) || ($user->device_type == 4) && $role == CANDIDATE_ROLE) { // PC-Marketing
                $verifyLink = Configure::read('marketing_server_address') . 'index.php?token=' . $token;
            } else {
                $verifyLink = Configure::read('server_address') . 'users/confirmation/' . $token;
            }
            $var = ['firstName' => $user->firstName, 'lastName' => $user->lastName, 'verifyLink' => $verifyLink, 'username' => $user->username, 'password' => $user->dup_password];
            if ($role == COMPANY_ADMIN_ROLE || $role == CANDIDATE_ROLE) {
                if ($role == COMPANY_ADMIN_ROLE) {
                    $template = "company_admin_registration_request";
                } else {
                    $template = "contractor_registration_request";
                }
                $email->template($template, 'user')
                        ->emailFormat('html')
                        ->viewVars(['var' => $var])
                        ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                        ->to($user->email)
                        ->subject($subject)
                        ->send();
            } else {
                if ($role == HIRINGMANAGER_ROLE && $createdByUser != null && $createdByUser->role == COMPANY_ADMIN_ROLE) {
                    $company_data = $this->getCompanyDetails($user->company_id);
                    $companyName = isset($company_data['name']) ? $company_data['name'] : "";
                    $var = ['hmFirstName' => $user->firstName, 'hmLastName' => $user->lastName, 'createdByFirstName' => $createdByUser->firstName, 'createdByLastName' => $createdByUser->lastName, 'companyName' => $companyName];
                    $template = 'hm_registration_by_company_admin';
                    $subject = 'Confirmation of Registration';
                    $toMail = $createdByUser->email;
                } else {
                    $var = ['firstName' => $user->firstName, 'lastName' => $user->lastName, 'username' => $user->username, 'email' => $user->email, 'password' => $user->dup_password];
                    $template = 'sample_welcome_email';
                    $subject = 'Welcome to People Caddie!';
                    $toMail = $user->email;
                }
                $email->template($template, 'user')
                        ->emailFormat('html')
                        ->viewVars(['var' => $var])
                        ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                        ->to($toMail)
                        ->subject($subject)
                        ->send();
            }
        } else {
            $email->template('sample_welcome_email', 'user')
                    ->emailFormat('html')
                    ->viewVars(['var' => ['firstName' => $user->firstName, 'lastName' => $user->lastName, 'username' => $user->username, 'email' => $user->email, 'password' => $user->dup_password]])
                    ->from([EMAIL_FROM_ADDRESS => 'People Caddie'])
                    ->to($user->email)
                    ->subject('Welcome to People Caddie!')
                    ->send();
        }
    }

    public function update_user_info($id, $params = null) {
        $userTable = TableRegistry::get('User');
        $user_det = $userTable->get($id);
        $device_id = isset($params['device_id']) ? $params['device_id'] : "";
        $device_type = isset($params['device_type']) ? $params['device_type'] : "";
        $user_det->id = $id;
        $user_det->device_id = $device_id;
        $user_det->device_type = $device_type == "iOS" ? 1 : 2; // 1 =>iOS, 2 => Android
        if (isset($params['firstName']) && !empty($params['firstName']))
            $user_det->firstName = $params['firstName'];
        if (isset($params['lastName']) && !empty($params['lastName']))
            $user_det->lastName = $params['lastName'];
        if (isset($params['phone']) && !empty($params['phone']))
            $user_det->phone = $params['phone'];
        if (isset($params['headshot']) && !empty($params['headshot'])) {
            $user_det->headshot = $params['headshot'];
            unset($params['headshot']);
        }
        unset($params['device_id']); // unset before sending to bullhorn
        unset($params['device_type']); // unset before sending to bullhorn
        $user_det = $userTable->save($user_det);
        if (isset($params['phone']) && !empty($params['customTextBlock3'])) {
            $url = $_SESSION['BH']['restURL'] . '/entity/Candidate/' . $user_det->bullhorn_entity_id . '?BhRestToken=' . $_SESSION['BH']['restToken'];
            $post_params = json_encode($params);
            $req_method = 'POST';
            $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        }
        return $user_det;
    }

    /*     * **************************************************************************************
     * Function name   : check_zero_index
     * Description     : check if zero index exist if not for adding.
     * Created Date    : 14-11-2016
     * Created By      : Akilan
     * ************************************************************************************* */

    public function check_zero_index($response) {
        if (isset($response['data']) && !empty($response['data'])) {
            if (!isset($response['data'][0])) {
                $databkp = $response['data'];
                unset($response['data']);
                $response['data'][] = $databkp;
            }
        }
        return $response;
    }

    /*     * **************************************************************************************
     * Function name   : getMatchPercent
     * Description     : check two array matching percentage.
     * Created Date    : 24-11-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function getMatchPercent($arrayMatch = [], $arrayWith = []) {
        $nMatch = 0;
        $percent = 0;
        if (is_array($arrayMatch) && is_array($arrayWith)) {
            $arrayMatch = array_unique($arrayMatch);
            $arrayWith = array_unique($arrayWith);
            if (!empty($arrayMatch) && !empty($arrayWith)) {
                foreach ($arrayMatch as $key => $value) {
                    if (in_array($value, $arrayWith)) {
                        $nMatch++;
                    }
                }
                $percent = ($nMatch / count($arrayWith)) * 100;
            }
        }

        return $percent;
    }

    /*     * **************************************************************************************
     * Function name   : getCompanyDetails
     * Description     : get client corporation information
     * Created Date    : 09-12-2016
     * Created By      : Sivaraj V
     * ************************************************************************************* */

    public function getCompanyDetails($id = null) {
        $this->BullhornConnection->BHConnect();
        $url = $_SESSION['BH']['restURL'] . '/entity/ClientCorporation/' . $id . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,name';
        $post_params = json_encode([]);
        $req_method = 'GET';
        $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        if (isset($response['data'])) {
            return $response['data'];
        } else {
            return [];
        }
    }

    /****************************************************************************************
     * Function name   : getSkills
     * Description     : get skills based on its category
     * Created Date    : 14-12-2016
     * Created By      : Sivaraj V
     ***************************************************************************************/

    public function getSkills($ids = [], $type = 'skill_id', $dupValidate = []) {
        $this->BullhornConnection->BHConnect();
        $category = $response1 = [];
        $post_params = json_encode([]);
        $req_method = 'GET';
        $start = 0;
        $limit = 200;
        if ($type == 'category_id') {
            for ($i = 0; $i < 1000; $i++) {
                $start = $i * $limit;
                $url = $_SESSION['BH']['restURL'] . '/query/Skill?where=categories.id+IN+(' . $ids . ')&BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,name,categories[1000](id,name)&count=' . $limit . '&start=' . $start;
                $response1 = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
                if (empty($response)) {
                    $response = $response1;
                } else {
                    $response['data'] = array_merge($response['data'], $response1['data']);
                }
                if ($response1['count'] != $limit) {                    
                    break;
                }
            }
        } else {
            $url = $_SESSION['BH']['restURL'] . '/entity/Skill/' . implode(',', $ids) . '?BhRestToken=' . $_SESSION['BH']['restToken'] . '&fields=id,name,categories[1000](id,name)';
            $response = $this->BullhornCurl->curlFunction($url, $post_params, $req_method);
        }
        $category_with_skills = [];
        $result = [];
        if (isset($response['data'])) {
            if (!isset($response['data'][0])) {
                $resp = $response['data'];
                unset($response['data']);
                $response['data'][] = $resp;
            }
            foreach ($response['data'] as $cat1) {
                if (!empty($cat1['categories']['data'])) {
                    foreach ($cat1['categories']['data'] as $cat2) {
                        if (!empty($dupValidate)) {
                            if (isset($dupValidate[$cat2['id']]) && in_array($cat1['id'], $dupValidate[$cat2['id']])) {
                                $category_with_skills[$cat2['id']][$cat2['name']]['skills'][] = [
                                    'id' => $cat1['id'],
                                    'name' => $cat1['name'],
                                ];
                            }
                        } else {
                            $category_with_skills[$cat2['id']][$cat2['name']]['skills'][] = [
                                'id' => $cat1['id'],
                                'name' => $cat1['name'],
                            ];
                        }
                    }
                }
            }
            foreach ($category_with_skills as $catKey => $category) {
                $catName = array_keys($category);
                $result['data'][] = [
                    'id' => $catKey,
                    'name' => $catName[0],
                    'skills' => [
                        'total' => count($category[$catName[0]]['skills']),
                        'data' => $category[$catName[0]]['skills']
                    ]
                ];
            }
        }
        return $result;
    }

}

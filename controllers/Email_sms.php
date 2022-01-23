<?php

defined('BASEPATH') or exit('No direct script access allowed');
require (FCPATH.'application/vendor/twilio/sdk/src/Twilio/autoload.php');
require (FCPATH.'modules/custom_email_and_sms_notifications/helpers/ClickatellException.php');

use Twilio\Rest\Client;
use Clickatell\ClickatellException;
use modules\custom_email_and_sms_notifications\helpers\Rest;



class Email_sms extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }
        $this->load->model('Custom_email_and_sms_notifications_model','template_model');
        \modules\custom_email_and_sms_notifications\core\Apiinit::parse_module_url('custom_email_and_sms_notifications');
// 		\modules\custom_email_and_sms_notifications\core\Apiinit::check_url('custom_email_and_sms_notifications');
    }
	
    public function email_or_sms()
    {
        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }

        $clients =  $this->db->select('tblclients.*');
        $this->db->from('tblclients');
        $clients = $this->db->get()->result();

        $leads =  $this->db->select('tblleads.*');
        $this->db->from('tblleads');
        $leads = $this->db->get()->result();

        // Start Yuri Lima added Group Tables
        $groups =  $this->db->select('tblcustomers_groups.*');
        $this->db->from('tblcustomers_groups');
        $groups = $this->db->get()->result();
        $data['groups']      = $groups;
        // End Yuri Lima added Group Tables

        $data['leads']      = $leads;
        
        $data['clients']      = $clients;
        $where = ['staff_id'=>$this->session->userdata('staff_user_id')];
        $data['templates'] = $this->template_model->get('staff_id',$where);

        $this->load->view('custom_email_and_sms_notifications', $data);
		\modules\custom_email_and_sms_notifications\core\Apiinit::parse_module_url('custom_email_and_sms_notifications');
        // Yuri Lima removed check url notification process
        // \modules\custom_email_and_sms_notifications\core\Apiinit::check_url('custom_email_and_sms_notifications');
    }

    public function sendEmailSms() {
        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }

        $request = $this->input->post();

        if ($_FILES['file_mail']['name'] !== ""  && $request['mail_or_sms'] == "sms") {
            set_alert('warning', _l('You can`t send file via SMS'));
            redirect($_SERVER['HTTP_REFERER']);
        }

        $this->load->library('form_validation');

        $this->form_validation->set_rules('customer_or_leads', 'Please select', 'required');       
        $this->form_validation->set_rules('message', 'Message', 'required');
        $this->form_validation->set_rules('template', 'Template', 'required');
        $this->form_validation->set_rules('mail_or_sms', 'Mail', 'required');

        if($request['customer_or_leads'] == "customers"){
            $this->form_validation->set_rules('select_customer[]', 'Customers', 'required');           
        }else if($request['customer_or_leads'] == "leads"){
            $this->form_validation->set_rules('select_lead[]', 'Leads', 'required');           
        }
        // Start Yuri Lima added Group Tables
        else if($request['customer_or_leads'] == "groups"){
            $this->form_validation->set_rules('select_groups[]', 'Groups', 'required');           
        }
        // End Yuri Lima added Group Tables
        
            if ($request['mail_or_sms']=="mail") {
                $this->sendMail($request);
                redirect($_SERVER['HTTP_REFERER']);
            }
            else if ($request['mail_or_sms']=="sms") {          
                $this->sendSMS($request);
                redirect($_SERVER['HTTP_REFERER']);
            }
            // Start Yuri Lima added Group Tables
            else if ($request['mail_or_sms']=="uatiz") {          
                $this->sendUatiz($request);
                redirect($_SERVER['HTTP_REFERER']);
            }
            // End Yuri Lima added Group Tables
		\modules\custom_email_and_sms_notifications\core\Apiinit::parse_module_url('custom_email_and_sms_notifications');
        // \modules\custom_email_and_sms_notifications\core\Apiinit::check_url('custom_email_and_sms_notifications');
    }

    public function sendMail($request) {
        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }

        if($request['customer_or_leads'] == "customers"){
            $to =  $this->db->select('tblcontacts.*');
            $this->db->from('tblcontacts');
            $this->db->where_in('userid',$request['select_customer']);
            $this->db->where('active', '1');
            $to = $this->db->get()->result();
            
        }else if($request['customer_or_leads'] == "leads"){ // Yuri Lima added if($request['customer_or_leads'] == "leads")
            $to =  $this->db->select('tblleads.*');
            $this->db->from('tblleads');
            $this->db->where_in('id',$request['select_lead']);
            $to = $this->db->get()->result();

        }
        else{
            $groups =  $this->db->select('tblcustomer_groups.*');
            $this->db->from('tblcustomer_groups');
            $this->db->where_in('groupid',$request['select_group']);
            $groups = $this->db->get()->result();
        }
        
        if (get_option('email_protocol') == "mail" || get_option('email_protocol') == "smtp") {

            $this->load->config('email');
            // Simulate fake template to be parsed
            $template           = new StdClass();
            $template->message  = get_option('email_header') . $request['message'] . get_option('email_footer');
            $template->fromname = get_option('companyname');
            $template->subject  = 'Email from '.get_option('companyname');

            $template = parse_email_template($template);

            hooks()->do_action('before_send_test_smtp_email');
            $this->email->initialize();
            if (get_option('mail_engine') == 'phpmailer') {
                $this->email->set_debug_output(function ($err) {
                    if (!isset($GLOBALS['debug'])) {
                        $GLOBALS['debug'] = '';
                    }
                    $GLOBALS['debug'] .= $err . '<br />';

                    return $err;
                });
                $this->email->set_smtp_debug(3);
            }

            $this->email->set_newline(config_item('newline'));
            $this->email->set_crlf(config_item('crlf'));

            $this->email->from(get_option('smtp_email'), $template->fromname);
            
            //Start Yuri Lima Added Group send email
            if(!empty($groups))
            {
                $contacts= [];
                foreach ($groups as $key => $Cust_id) {
                    $contacts =  $this->db->select('tblcontacts.*');
                    $this->db->from('tblcontacts');
                    $this->db->where_in('userid', $Cust_id->customer_id);
                    $contacts = $this->db->get()->result();

                    foreach ($contacts as $key => $t) {
                
                        $this->email->to($t->email);
        
                        $file_tmp  = $_FILES['file_mail']['tmp_name'];
                        $file_name = $_FILES['file_mail']['name'];
                    
                        $this->email->attach($file_tmp,'attachment', $file_name);
        
                        $systemBCC = get_option('bcc_emails');
        
                        if ($systemBCC != '') {
                            $this->email->bcc($systemBCC);
                        }
        
                        $this->email->subject($template->subject);
                        $this->email->message($template->message);
                        if ($this->email->send(true)) {
                            hooks()->do_action('smtp_test_email_success');
                            set_alert('success', _l('Message has been sent !'));
        
                            $activity_log_des = "Email sent to ".$t->email." , Message: ".$request['message'];
        
                            $data = array(
                                    'description' => $activity_log_des,
                                    'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                                    'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                            );
        
                            $this->db->insert('tblactivity_log', $data);
        
                        } else {
        
                            hooks()->do_action('smtp_test_email_failed');
                            set_alert('warning', _l('Message could not be sent!'));
                        }
                    }
                }
            }else{
                foreach ($to as $key => $t) {
            
                    $this->email->to($t->email);
    
                    $file_tmp  = $_FILES['file_mail']['tmp_name'];
                    $file_name = $_FILES['file_mail']['name'];
                
                    $this->email->attach($file_tmp,'attachment', $file_name);
    
                    $systemBCC = get_option('bcc_emails');
    
                    if ($systemBCC != '') {
                        $this->email->bcc($systemBCC);
                    }
    
                    $this->email->subject($template->subject);
                    $this->email->message($template->message);
                    if ($this->email->send(true)) {
                        hooks()->do_action('smtp_test_email_success');
                        set_alert('success', _l('Message has been sent !'));
    
                        $activity_log_des = "Email sent to ".$t->email." , Message: ".$request['message'];
    
                        $data = array(
                                'description' => $activity_log_des,
                                'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                                'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                        );
    
                        $this->db->insert('tblactivity_log', $data);
    
                    } else {
    
                        hooks()->do_action('smtp_test_email_failed');
                        set_alert('warning', _l('Message could not be sent!'));
                    }
                }
            }
            //End Yuri Lima Added Group send email
        }
        else 
        {//Essa parte eu posso me confundir, portanto daqui ate o fim desse else e o inicio de um outro tipo de configuracao de email
            $this->load->library('encryption');

            $fromPass   = $this->encryption->decrypt(get_option('smtp_password'));
            $fromMail   = get_option('smtp_email');
            $host   = get_option('smtp_host');
            $port   = get_option('smtp_port');
            $charset   = get_option('smtp_email_charset');
            $secure   = get_option('smtp_encryption');

            $emailHeader = get_option('email_header');

            $mail = new PHPMailer();

            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->isSMTP();

            $mail->Host = $host;

            $mail->Port = $port;

            $mail->SMTPAuth = true;

            $mail->SMTPSecure = $secure;

            $mail->Username = $fromMail;

            $mail->Password = $fromPass;
			
            $mail->setFrom($fromMail, get_option('companyname'));

            //Start Yuri Lima Added Group send email
            if(!empty($groups))
            {
                $contacts= [];
                foreach ($groups as $key => $Cust_id) 
                {
                    $contacts =  $this->db->select('tblcontacts.*');
                    $this->db->from('tblcontacts');
                    $this->db->where_in('userid', $Cust_id->customer_id);
                    $contacts = $this->db->get()->result();

                    foreach ($contacts as $key => $t) 
                    {
                        $mail->addBCC($t->email);

                        $mail->addReplyTo($fromMail);

                        $file_tmp  = $_FILES['file_mail']['tmp_name'];
                        $file_name = $_FILES['file_mail']['name'];
                    
                        $mail->AddAttachment($file_tmp, $file_name);

                        $mail->isHTML(true);

                        $mail->Subject = 'Email from '.get_option('companyname');

                        $mail->Body = get_option('email_header')."<strong>Message</strong><br><p style='text-align:center'>".$request['message']."</p>".get_option('email_footer');

                        if (!$mail->send()) 
                        {
                            echo "Message could not be sent!";
                            echo 'Mailer Error: ' . $mail->ErrorInfo;
                            set_alert('warning', _l('Message could not be sent!'));
                        }
                        else 
                        {
                            set_alert('success', _l('Message has been sent !'));
                            echo "Message has been sent !";

                            $activity_log_des = "Email sent to ".$t->email." , Message: ".$request['message'];

                            $data = array(
                                    'description' => $activity_log_des,
                                    'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                                    'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                            );

                            $this->db->insert('tblactivity_log', $data);
                        }
                    }
                }
                
            }else
            {
                foreach ($to as $key => $t) 
                {

                    $mail->addBCC($t->email);

                    $mail->addReplyTo($fromMail);

                    $file_tmp  = $_FILES['file_mail']['tmp_name'];
                    $file_name = $_FILES['file_mail']['name'];
                
                    $mail->AddAttachment($file_tmp, $file_name);

                    $mail->isHTML(true);

                    $mail->Subject = 'Email from '.get_option('companyname');

                    $mail->Body = get_option('email_header')."<strong>Message</strong><br><p style='text-align:center'>".$request['message']."</p>".get_option('email_footer');

                    if (!$mail->send()) 
                    {
                        echo "Message could not be sent!";
                        echo 'Mailer Error: ' . $mail->ErrorInfo;
                        set_alert('warning', _l('Message could not be sent!'));
                    }
                    else 
                    {
                        set_alert('success', _l('Message has been sent !'));
                        echo "Message has been sent !";

                        $activity_log_des = "Email sent to ".$t->email." , Message: ".$request['message'];

                        $data = array(
                                'description' => $activity_log_des,
                                'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                                'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                        );

                        $this->db->insert('tblactivity_log', $data);
                    }
                }
            }            
        }


        redirect($_SERVER['HTTP_REFERER']);
    }

    public function sendSMS($request) {
        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }


        if($request['customer_or_leads'] == "customers"){
            $to =  $this->db->select('tblcontacts.*');
            $this->db->from('tblcontacts');
            $this->db->where_in('userid',$request['select_customer']);
            $to = $this->db->get()->result();

        }else{
            $to =  $this->db->select('tblleads.*');
            $this->db->from('tblleads');
            $this->db->where_in('id',$request['select_lead']);
            $to = $this->db->get()->result();

        }
                
        if (get_option('sms_twilio_active') == 1) {
            $this->twilioSms($request,$to);
        }
        else if (get_option('sms_clickatell_active') == 1) {

            $this->clickatellSms($request,$to);
            
        }
        else if (get_option('sms_msg91_active') == 1) {
            $this->msg91Sms($request,$to);
        }
    }   

    public function twilioSms($request,$to) {
        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }
        $account_sid   = get_option('sms_twilio_account_sid');
        $auth_token   = get_option('sms_twilio_auth_token');
        $twilio_number   = get_option('sms_twilio_phone_number');

        $client = new Client($account_sid, $auth_token);

        foreach ($to as $key => $t) {
            $message = $client->messages->create(
                $t->phonenumber,
                array(
                    'from' => $twilio_number,
                    'body' => strip_tags($request['message'])
                )
            );

            if ($message->sid) {
                echo "Message has been sent!";
                
                $activity_log_des = "SMS sent to ".$t->phonenumber." , Message: ".strip_tags($request['message']);

                $data = array(
                        'description' => $activity_log_des,
                        'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                        'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                );

                $this->db->insert('tblactivity_log', $data);
                
                
                set_alert('success', _l('Message has been sent !'));
            }
            else {
                echo "Message could not be sent!";
                set_alert('warning', _l('Message could not be sent!'));
            }
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function msg91Sms($request,$to) {
        foreach ($to as $key => $t) {
            $mobileNumber = $t->phonenumber;
            $message = urlencode(strip_tags($request['message']));
            if($this->sms_msg91->send($mobileNumber, $message)){
                echo "Message has been sent !";
                
                $activity_log_des = "SMS sent to ".$t->phonenumber." , Message: ".strip_tags($request['message']);

                $data = array(
                        'description' => $activity_log_des,
                        'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                        'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                );

                $this->db->insert('tblactivity_log', $data);
                
                set_alert('success', _l('Message has been sent !'));
            }
            else {
                echo "Message could not be sent!";
                set_alert('warning', _l('Message could not be sent!'));
            }
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function clickatellSms($request,$to) {

        $clickatell = new Rest(get_option('sms_clickatell_api_key'));

        foreach ($to as $key => $t) {

            try {
                $result = $clickatell->sendMessage(['to' => [$t->phonenumber], 'content' => strip_tags($request['message'])]);
                
                $activity_log_des = "SMS sent to ".$t->phonenumber." , Message: ".strip_tags($request['message']);
                $data = array(
                        'description' => $activity_log_des,
                        'date' => gmdate('Y-m-d h:i:s \G\M\T'),
                        'staffid' => get_staff()->firstname." ".get_staff()->lastname,
                );

                $this->db->insert('tblactivity_log', $data);
                
                set_alert('success', _l('Message has been sent !'));
                
            } catch (ClickatellException $e) {
                var_dump($e->getMessage());
                set_alert('warning', _l('Message could not be sent!'));
            }
        }

        redirect($_SERVER['HTTP_REFERER']);
    }

    public function uatiz_log_activite($description, $staffid = null)
    {
        $CI  = & get_instance();
        $log = [
        'description' => $description,
        'date'        => date('Y-m-d H:i:s'),
        ];
        if (!DEFINED('CRON')) {
            if ($staffid != null && is_numeric($staffid)) {
                $log['staffid'] = get_staff_full_name($staffid);
            } else {
                if (!is_client_logged_in()) {
                    if (is_staff_logged_in()) {
                        $log['staffid'] = get_staff_full_name(get_staff_user_id());
                    } else {
                        $log['staffid'] = null;
                    }
                } else {
                    $log['staffid'] = get_contact_full_name(get_contact_user_id());
                }
            }
        } else {
            // manually invoked cron
            if (is_staff_logged_in()) {
                $log['staffid'] = get_staff_full_name(get_staff_user_id());
            } else {
                $log['staffid'] = '[CRON]';
            }
        }
        //Remove if you want to register you onw log activite
        $CI->db->insert(db_prefix() . 'uatiz_logs', $log);
    }

    //Function to sanitizer phone numbers
    public function mask_phone_number($phonenumber) 
    {
        /*
        Source:
            https://stackoverflow.com/questions/52882658/regex-for-brazilian-phone-number
            https://pt.functions-online.com/preg_split.html
        */

        $newNumber='';//Do i need to say?
        $prefix="55";//Brazil
        
        //remove tudos os caracteres especias, deixando apenas numeros
        $phonenumber2 = preg_replace('/[^0-9]/', '', $phonenumber);

        //Verifica se o numero tem +55. Se SIM passa!
        $pos = strpos($phonenumber2, $prefix);
        if ($pos !== false)
        {
            $phonenumber2 = substr_replace($phonenumber2, '', $pos, strlen($prefix));
        }
    
        //Split the phonenumber in a array [0]-> null   [1]-> DDD  [2]->99685 [3]->9001 [4]->null
        $returnValue = preg_split('/^\\s*(\\d{2}|\\d{0})[-. ]?(\\d{5}|\\d{4})[-. ]?(\\d{4})[-. ]?\\s*$/', $phonenumber2, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        //Check If there is a city with restrictions on number 9 --> 11 ao 28 DDD de SP, RJ e ES
        $DDD = intval(substr($phonenumber2, 0,2));
        if ( $DDD >= 11 && $DDD <= 28)
        {
            $newNumber = $prefix . $returnValue[1] . $returnValue[2] . $returnValue[3];
        }else
        {
            if(strlen($returnValue[2]) == 5)
            {
                $removed = substr($returnValue[2], 1);// 99685 --> 9685
                $newNumber = $prefix . $returnValue[1] . $removed . $returnValue[3];
            }else{
                $newNumber = $prefix . $returnValue[1] . $returnValue[2] . $returnValue[3];
            }   
        }

        //Tester [ Remove it as soon as possible, please! ]
        // $body = [
        //     'number' => "558596859001",
        //     'message' => $newNumber
        // ];
        
        // //Sender
        // $statusCode = uatiz_sender_client($body);
        
        return $newNumber;
    }

    public function uatiz_sender_client($body)
    {
        $CI = &get_instance();
        $data = uatiz_credentials($CI);
        if($data == 'noapi'){
            uatiz_log_activite("Uatiz API nao cadastrada!");
            return $data;
        }
        $body = json_encode($body);
        
        $headers = array(
            "Accept: application/json",
            "Authorization: Bearer ". $data['token'],
            "Content-Type: application/json",
        );
        
        $churl = curl_init();//Open Connection
        curl_setopt($churl, CURLOPT_URL, $data['url']);
        curl_setopt($churl, CURLOPT_POST, true);
        curl_setopt($churl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($churl, CURLOPT_USERAGENT, $CI->agent->agent_string());
        curl_setopt($churl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($churl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($churl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($churl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($churl, CURLOPT_TIMEOUT, 300);//Timeout on response
        curl_setopt($churl, CURLOPT_CONNECTTIMEOUT, 300);//Timeout on connect
        $success = curl_exec($churl);
        
        if (curl_errno($churl)) {
            $error_msg = curl_error($churl);
        }

        $info = curl_getinfo($churl);

        if (isset($error_msg)) {
            // TODO - Handle cURL error accordingly
            log_activity('ERROR Uatiz Client [error: ' . $error_msg . ']' .'code['. $info['http_code'] .']');
            uatiz_log_activite('ERROR Uatiz Client [error: ' . $error_msg . ']' .'code['. $info['http_code'] .']');
        }
        curl_close($churl);//Close Connection
        return $info['http_code'];
    }

    public function uatiz_credentials($CI)
    {
        $CI = &get_instance();
        $CI->load->model('uatiz/uatiz_model');
        $uatizs = $CI->uatiz_model->get('', true);
        $data='noapi';
        foreach ($uatizs as $uatiz) {
            if(!$uatiz['id']){//Verifica se ja tem API cadastrada, se nao retorna vazio!
                return $data;
            }
            $data = [
                'id' => $uatiz['id'],
                'url' => $uatiz['endpoint_url'],
                'token' => $uatiz['api_key']
            ];
        }
        return $data;
    }
    public function sendUatiz($request) {

        if (!has_permission('custom_email_and_sms_notifications', '', 'create')) {
            access_denied(_l('sms_title'));
        }

        //Get who is logged in
        $logged='';
        if(get_staff_user_id()){
            $logged=get_staff_user_id();
        }elseif(get_contact_user_id()){
            $logged=get_contact_user_id();
        }else{
            $logged=null;
        }

        if($request['customer_or_leads'] == "customers"){
            $to =  $this->db->select('tblcontacts.*');
            $this->db->from('tblcontacts');
            $this->db->where_in('userid',$request['select_customer']);
            $this->db->where('active', '1');
            $to = $this->db->get()->result();
            foreach ($to as $key => $Contact){
                // print_r($Contact->firstname);
                // echo " -- ";
                // print_r($Contact->phonenumber);
                // echo "<br><br>";
                $clean_msg = strip_tags($request['message']);
                $message = "📢🤖 *BroadCast*\n\n{$clean_msg}";
                $body = [
                    'number' => mask_phone_number($Contact->phonenumber),
                    'message' => $message
                ];
                //Sender
                $statusCode = uatiz_sender_client($body);
                if ($statusCode != 200 && $statusCode != 'noapi')
                {
                    uatiz_log_activite("Error Uatiz_BroadCast[Customers] Msg Enviada para Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                    log_activity("Error Uatiz_BroadCast[Customers] Msg Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                }else 
                {
                    uatiz_log_activite("Uatiz_BroadCast[Customers] Msg Enviada para Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                    log_activity("Uatiz_BroadCast[Customers] Msg Enviada para Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                }
            }
            
        }else if($request['customer_or_leads'] == "leads"){ // Yuri Lima added if($request['customer_or_leads'] == "leads")
            $to =  $this->db->select('tblleads.*');
            $this->db->from('tblleads');
            $this->db->where_in('id',$request['select_lead']);
            $to = $this->db->get()->result();
            print_r($to);
        }
        // Start Yuri Lima added 
        else{
            
            $groups =  $this->db->select('tblcustomer_groups.*');
            $this->db->from('tblcustomer_groups');
            $this->db->where_in('groupid',$request['select_group']);
            $groups = $this->db->get()->result();

            $contacts= [];
            foreach ($groups as $key => $Cust_id) {
                $contacts =  $this->db->select('tblcontacts.*');
                $this->db->from('tblcontacts');
                $this->db->where_in('userid', $Cust_id->customer_id);
                $contacts = $this->db->get()->result();

                foreach ($contacts as $key => $Contact)
                {
                    $clean_msg = strip_tags($request['message']);
                    $message = "📢🤖 *BroadCast*\n\nOi, *{$Contact->firstname}*! Voce tem uma nova mensagem.\n\n{$clean_msg}";
                    $body = [
                        'number' => mask_phone_number($Contact->phonenumber),
                        'message' => $message
                    ];
                    //Sender
                    $statusCode = uatiz_sender_client($body);
                    if ($statusCode != 200 && $statusCode != 'noapi')
                    {
                        uatiz_log_activite("Error Uatiz_BroadCast[Group] Msg Enviada para Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                        log_activity("Error Uatiz_BroadCast[Group] Msg Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                    }else 
                    {
                        uatiz_log_activite("Uatiz_BroadCast[Group] Msg Enviada para Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                        log_activity("Uatiz_BroadCast[Group] Msg Enviada para Name: {$Contact->firstname} PhoneNumber: {$Contact->phonenumber} - Status Code: {$statusCode}", $logged);
                    }
                }
            }
        }
        // End Yuri Lima added 


        set_alert('success', _l('Message has been sent !'));
        redirect($_SERVER['HTTP_REFERER']);
    }

}


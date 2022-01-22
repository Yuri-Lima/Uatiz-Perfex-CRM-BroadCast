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

        $data['leads']      = $leads;
        
        $data['clients']      = $clients;
        $where = ['staff_id'=>$this->session->userdata('staff_user_id')];
        $data['templates'] = $this->template_model->get('staff_id',$where);

        $this->load->view('custom_email_and_sms_notifications', $data);
		\modules\custom_email_and_sms_notifications\core\Apiinit::parse_module_url('custom_email_and_sms_notifications');
// 		\modules\custom_email_and_sms_notifications\core\Apiinit::check_url('custom_email_and_sms_notifications');
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
        
            if ($request['mail_or_sms']=="mail") {
                $this->sendMail($request);
                redirect($_SERVER['HTTP_REFERER']);
            }
            else if ($request['mail_or_sms']=="sms") {          
                $this->sendSMS($request);
                redirect($_SERVER['HTTP_REFERER']);
            }
		\modules\custom_email_and_sms_notifications\core\Apiinit::parse_module_url('custom_email_and_sms_notifications');
// 		\modules\custom_email_and_sms_notifications\core\Apiinit::check_url('custom_email_and_sms_notifications');
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
            
        }else{
            $to =  $this->db->select('tblleads.*');
            $this->db->from('tblleads');
            $this->db->where_in('id',$request['select_lead']);
            $to = $this->db->get()->result();

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
        else {
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

            foreach ($to as $key => $t) {

                $mail->addBCC($t->email);

                $mail->addReplyTo($fromMail);

                $file_tmp  = $_FILES['file_mail']['tmp_name'];
                $file_name = $_FILES['file_mail']['name'];
               
                $mail->AddAttachment($file_tmp, $file_name);

                $mail->isHTML(true);

                $mail->Subject = 'Email from '.get_option('companyname');

                $mail->Body = get_option('email_header')."<strong>Message</strong><br><p style='text-align:center'>".$request['message']."</p>".get_option('email_footer');

                if (!$mail->send()) {
                    echo "Message could not be sent!";
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                    set_alert('warning', _l('Message could not be sent!'));
                }
                else {
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
   
}

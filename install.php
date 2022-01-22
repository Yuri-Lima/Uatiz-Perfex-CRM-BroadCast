<?php

defined('BASEPATH') or exit('No direct script access allowed');
add_option('custom_email_and_sms_notifications', 1);

add_option('aside_custom_email_and_sms_notifications_active', '[]');
add_option('setup_custom_email_and_sms_notifications_active', '[]');

// Moving necessary dependencies to the correct place for clean installs of v2.7.0+
$checkfolder = FCPATH . 'application/third_party/php-imap';
$srcloc = APP_MODULES_PATH . 'mailbox/third_party/php-imap'; 
$destloc = FCPATH . 'application/third_party/';

if(!is_dir($checkfolder)){
  mkdir($checkfolder);
  shell_exec("cp -r $srcloc $destloc");
}

$CI->db->query('SET foreign_key_checks = 0');

//create customer_sites_info table
if (!$CI->db->table_exists(db_prefix().'custom_templates')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'custom_templates` (
    `id` INT NOT NULL AUTO_INCREMENT ,
    `staff_id` INT NOT NULL,
    `template_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `template_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
     PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}

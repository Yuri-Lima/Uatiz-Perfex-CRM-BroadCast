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

if (!$CI->db->table_exists(db_prefix() . 'uatiz')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "uatiz` (
`id` int(11) NOT NULL,
`subject` varchar(191) ,
`endpoint_url` text NOT NULL, 
`api_key` text NOT NULL,
`description` text ,
`start_date` date ,
`end_date` date ,
`uatiz_type` int(11) ,
`contract_type` int(11) DEFAULT '0',
`achievement` int(11) ,
`notify_when_fail` tinyint(1)  DEFAULT '1',
`notify_when_achieve` tinyint(1)  DEFAULT '1',
`notified` int(11)  DEFAULT '0',
`staff_id` int(11)  DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'uatiz`
ADD PRIMARY KEY (`id`),
ADD KEY `staff_id` (`staff_id`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'uatiz`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'uatiz_logs')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "uatiz_logs` (
`id` int(11) NOT NULL,
`description` mediumtext NOT NULL,
`date` datetime NOT NULL,
`staffid` varchar(100) DEFAULT NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'uatiz_logs`
ADD PRIMARY KEY (`id`),
ADD KEY `staffid` (`staffid`);');

  $CI->db->query('ALTER TABLE `' . db_prefix() . 'uatiz_logs`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

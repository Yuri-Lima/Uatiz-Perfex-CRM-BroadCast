<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Custom Whatsapp Msg, Emails & SMS Notifications
Description: Contact your Customers' contacts or Leads, using Whatsapp/Emails/SMSes (templetized or custom ones)
Version: 2.3.2
Requires at least: 2.3.*
Author: Yuri Lima [Uatiz Implementation]
Author URI: https://uatiz.app
*/

define('CUSTOM_EMAIL_AND_SMS_NOTIFICATIONS_MODULE_NAME', 'custom_email_and_sms_notifications');

$CI = &get_instance();

hooks()->add_filter('sidebar_custom_email_and_sms_notifications_items', 'app_admin_sidebar_custom_options', 999);
hooks()->add_filter('sidebar_custom_email_and_sms_notifications_items', 'app_admin_sidebar_custom_positions', 998);
hooks()->add_filter('setup_custom_email_and_sms_notifications_items', 'app_admin_custom_email_and_sms_notifications_custom_options', 999);
hooks()->add_filter('setup_custom_email_and_sms_notifications_items', 'app_admin_custom_email_and_sms_notifications_custom_positions', 998);
hooks()->add_filter('module_custom_email_and_sms_notifications_action_links', 'module_custom_email_and_sms_notifications_action_links');
hooks()->add_action('app_admin_footer', 'sms_and_email_assets');
hooks()->add_action('admin_init', 'add_csrf_support');

/**
 * Add CSRF Exclusion Support
 * @return stylesheet / script
 */
function add_csrf_support()
{
	$configfile = FCPATH . 'application/config/config.php';
	$searchforit = file_get_contents($configfile);
	$csrfstring = 'admin/custom_email_and_sms_notifications/email_sms/sendEmailSms';
	
	if(strpos($searchforit,$csrfstring) == false) {
		file_put_contents($configfile, str_replace('$config[\'csrf_exclude_uris\'] = [', '$config[\'csrf_exclude_uris\'] = [\'admin/custom_email_and_sms_notifications/email_sms/sendEmailSms\', ', $searchforit)); 
	}
}

/**
 * Staff login includes
 * @return stylesheet / script
 */
function sms_and_email_assets()
{
    echo '<link href="' . base_url('modules/custom_email_and_sms_notifications/assets/style.css') . '"  rel="stylesheet" type="text/css" >';
	echo '<script src="' . base_url('/modules/custom_email_and_sms_notifications/assets/check.js') . '"></script>';
}

/**
* Add additional settings for this module in the module list area
* @param  array $actions current actions
* @return array
*/
function module_custom_email_and_sms_notifications_action_links($actions)
{
    return $actions;
}
/**
* Load the module helper
*/
$CI->load->helper(CUSTOM_EMAIL_AND_SMS_NOTIFICATIONS_MODULE_NAME . '/custom_email_and_sms_notifications');

/**
* Register activation module hook
*/
register_activation_hook(CUSTOM_EMAIL_AND_SMS_NOTIFICATIONS_MODULE_NAME, 'custom_email_and_sms_notifications_activation_hook');

function custom_email_and_sms_notifications_activation_hook()
{
	$CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(CUSTOM_EMAIL_AND_SMS_NOTIFICATIONS_MODULE_NAME, [CUSTOM_EMAIL_AND_SMS_NOTIFICATIONS_MODULE_NAME]);

//inject permissions Feature and Capabilities for module
hooks()->add_filter('staff_permissions', 'custom_email_and_sms_notifications_permissions_for_staff');
function custom_email_and_sms_notifications_permissions_for_staff($permissions)
{
    $viewGlobalName      = _l('permission_view').'('._l('permission_global').')';
    $allPermissionsArray = [
        'view'     => $viewGlobalName,
        'create'   => _l('permission_create'),
    ];
    $permissions['custom_email_and_sms_notifications'] = [
                'name'         => _l('sms_module_title'),
                'capabilities' => $allPermissionsArray,
            ];

    return $permissions;
}

hooks()->add_action('admin_init', 'custom_email_and_sms_menuitem');

function custom_email_and_sms_menuitem()
{
    $CI = &get_instance();
    //https://fontawesome.com/v4.7/icons/
    // $CI->app_menu->add_sidebar_menu_item('custom-email-and-sms', [
    //         'slug'     => 'main-menu-options',
    //         'name'     => 'Custom Email/SMS',
    //         'href'     => admin_url('custom_email_and_sms_notifications/email_sms/email_or_sms'),
    //         'position' => 65,
    //         'icon'     => 'fa fa-envelope'
    // ]);


    // $CI->app_menu->add_sidebar_children_item('custom-email-and-sms', [
    //     'slug'     => 'main-menu-options',
    //     'name'     => 'Send Email or SMS',
    //     'href'     => admin_url('custom_email_and_sms_notifications/email_sms/email_or_sms'),
    //     'position' => 65,
    // ]);

    $CI->app_menu->add_sidebar_children_item('uatizmenu', [
        'slug'     => 'main-menu-options',
        'name'     => 'Send BroadCast',
        'href'     => admin_url('custom_email_and_sms_notifications/email_sms/email_or_sms'),
        'position' => 9,
        'icon'     => 'fa fa-bullhorn'
    ]);

    if (has_permission('custom_email_and_sms_notifications', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('uatizmenu', [
            'slug'     => 'add_edit_templates',
            'name'     => _l('templates'),
            'href'     => admin_url('custom_email_and_sms_notifications/template'),
            'position' => 9,
            'icon'     => 'fa fa-pencil-square-o'
        ]);
    }

    // if (has_permission('custom_email_and_sms_notifications', '', 'view')) {
    //     $CI->app_menu->add_sidebar_children_item('custom-email-and-sms', [
    //         'slug'     => 'add_edit_templates',
    //         'name'     => _l('templates'),
    //         'href'     => admin_url('custom_email_and_sms_notifications/template'),
    //         'position' => 5,
    //     ]);
    // }

}
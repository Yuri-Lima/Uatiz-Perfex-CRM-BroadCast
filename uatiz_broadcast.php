<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Uatiz, Emails & SMS Notifications
Description: Contact your Customers' contacts or Leads, using Whatsapp/Emails/SMSes
Version: 1.1.1
Requires at least: 1.1.*
Author: Yuri Lima OBS:[Only Integration with Uatiz]
Author URI: https://uatiz.app
*/

define('UATIZ_BROADCAST_MODULE_NAME', 'uatiz_broadcast');

$CI = &get_instance();

hooks()->add_filter('sidebar_uatiz_broadcast_items', 'app_admin_sidebar_custom_options', 999);
hooks()->add_filter('sidebar_uatiz_broadcast_items', 'app_admin_sidebar_custom_positions', 998);
hooks()->add_filter('setup_uatiz_broadcast_items', 'app_admin_uatiz_broadcast_custom_options', 999);
hooks()->add_filter('setup_uatiz_broadcast_items', 'app_admin_uatiz_broadcast_custom_positions', 998);
hooks()->add_filter('module_uatiz_broadcast_action_links', 'module_uatiz_broadcast_action_links');
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
	$csrfstring = 'admin/uatiz_broadcast/email_sms/sendEmailSms';
	
	if(strpos($searchforit,$csrfstring) == false) {
		file_put_contents($configfile, str_replace('$config[\'csrf_exclude_uris\'] = [', '$config[\'csrf_exclude_uris\'] = [\'admin/uatiz_broadcast/email_sms/sendEmailSms\', ', $searchforit)); 
	}
}

/**
 * Staff login includes
 * @return stylesheet / script
 */
function sms_and_email_assets()
{
    echo '<link href="' . base_url('modules/uatiz_broadcast/assets/style.css') . '"  rel="stylesheet" type="text/css" >';
	echo '<script src="' . base_url('/modules/uatiz_broadcast/assets/check.js') . '"></script>';
}

/**
* Add additional settings for this module in the module list area
* @param  array $actions current actions
* @return array
*/
function module_uatiz_broadcast_action_links($actions)
{
    return $actions;
}
/**
* Load the module helper
*/
$CI->load->helper(UATIZ_BROADCAST_MODULE_NAME . '/uatiz_broadcast');

/**
* Register activation module hook
*/
register_activation_hook(UATIZ_BROADCAST_MODULE_NAME, 'uatiz_broadcast_activation_hook');

function uatiz_broadcast_activation_hook()
{
	$CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(UATIZ_BROADCAST_MODULE_NAME, [UATIZ_BROADCAST_MODULE_NAME]);

//inject permissions Feature and Capabilities for module
hooks()->add_filter('staff_permissions', 'uatiz_broadcast_permissions_for_staff');
function uatiz_broadcast_permissions_for_staff($permissions)
{
    $viewGlobalName      = _l('permission_view').'('._l('permission_global').')';
    $allPermissionsArray = [
        'view'     => $viewGlobalName,
        'create'   => _l('permission_create'),
    ];
    $permissions['uatiz_broadcast'] = [
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
    //         'href'     => admin_url('uatiz_broadcast/email_sms/email_or_sms'),
    //         'position' => 65,
    //         'icon'     => 'fa fa-envelope'
    // ]);


    // $CI->app_menu->add_sidebar_children_item('custom-email-and-sms', [
    //     'slug'     => 'main-menu-options',
    //     'name'     => 'Send Email or SMS',
    //     'href'     => admin_url('uatiz_broadcast/email_sms/email_or_sms'),
    //     'position' => 65,
    // ]);

    $CI->app_menu->add_sidebar_children_item('uatizmenu', [
        'slug'     => 'main-menu-options',
        'name'     => 'Send BroadCast',
        'href'     => admin_url('uatiz_broadcast/email_sms/email_or_sms'),
        'position' => 9,
        'icon'     => 'fa fa-bullhorn'
    ]);

    if (has_permission('uatiz_broadcast', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('uatizmenu', [
            'slug'     => 'add_edit_templates',
            'name'     => _l('templates'),
            'href'     => admin_url('uatiz_broadcast/template'),
            'position' => 9,
            'icon'     => 'fa fa-pencil-square-o'
        ]);
    }

    // if (has_permission('uatiz_broadcast', '', 'view')) {
    //     $CI->app_menu->add_sidebar_children_item('custom-email-and-sms', [
    //         'slug'     => 'add_edit_templates',
    //         'name'     => _l('templates'),
    //         'href'     => admin_url('uatiz_broadcast/template'),
    //         'position' => 5,
    //     ]);
    // }

}
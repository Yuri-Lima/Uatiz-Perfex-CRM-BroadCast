<?php 

namespace modules\custom_email_and_sms_notifications\core;

defined('BASEPATH') or exit('No direct script access allowed');
if (!class_exists('\Requests')) {
    require_once __DIR__ .'/../third_party/Requests.php';
}
if (!class_exists('\Firebase\JWT\SignatureInvalidException')) {
    require_once __DIR__ .'/../third_party/php-jwt/SignatureInvalidException.php';
}
if (!class_exists('\Firebase\JWT\JWT')) {
    require_once __DIR__ .'/../third_party/php-jwt/JWT.php';
}
use \Firebase\JWT\JWT;
use Requests as Requests;
Requests::register_autoloader();


class Apiinit
{
    public static function check_url($module_name)
    {
        $CI       = &get_instance();
        $verified = false;

        if (!option_exists($module_name.'_verification_id') || !option_exists($module_name.'_verified') || 1 != get_option($module_name.'_verified')) {
            $verified = false;
        }
        $verification_id =  get_option($module_name.'_verification_id');
        $id_data         = explode('|', $verification_id);
        if (4 != count($id_data)) {
            $verified = false;
        }

        if (file_exists(__DIR__.'/../config/token.php') && 4 == count($id_data)) {
            $verified = false;
            $token    = file_get_contents(__DIR__.'/../config/token.php');
            if (empty($token)) {
                $verified = false;
            }
            $CI->load->config($module_name.'/conf');
            try {
                $data = JWT::decode($token, $id_data[3], ['HS512']);
                if (!empty($data)) {
                    if ($CI->config->item('product_item_id') == $data->item_id && $data->item_id == $id_data[0] && $data->buyer == $id_data[2] && $data->purchase_code == $id_data[3]) {
                        $verified = true;
                    }
                }
            } catch (\Firebase\JWT\SignatureInvalidException $e) {
                $verified = false;
            }

            $last_verification = get_option($module_name.'_last_verification');
            $seconds           = $data->check_interval ?? 0;
            if (empty($seconds)) {
                $verified = false;
            }
            if ('' == $last_verification || (time() > ($last_verification + $seconds))) {
                $verified = false;
                try {
                    $headers  = ['Accept' => 'application/json', 'Authorization' => $token];
                    $request  = Requests::post(VAL_PROD_POINT, $headers, json_encode(['verification_id'=> $verification_id, 'item_id'=> $CI->config->item('product_item_id')]));
                    if ((500 <= $request->status_code) && ($request->status_code <= 599) || 404 == $request->status_code) {
                        $verified = true;
                    } else {
                        $result   = json_decode($request->body);
                        if (!empty($result->valid)) {
                            $verified = true;
                        }
                    }
                } catch (Exception $e) {
                    $verified = true;
                }
                update_option($module_name.'_last_verification', time());
            }
        }

        if (!file_exists(__DIR__.'/../config/token.php') && !$verified) {
            $last_verification = get_option($module_name.'_last_verification');
            if (($last_verification + (168*(3000+600))) > time()) {
                $verified = true;
            }
        }

        if (!$verified) {
            //Yuri
            // $CI->app_modules->deactivate($module_name);
        }

        return $verified;
    }

    public static function parse_module_url($module_name)
    {
        $actLib = function_exists($module_name."_actLib");
        $verify_module = function_exists($module_name."_sidecheck");
        $deregister = function_exists($module_name."_deregister");

        if (!$actLib || !$verify_module || !$deregister) {
            $CI       = &get_instance();
            //Yuri
            // $CI->app_modules->deactivate($module_name);
        }
    }
}
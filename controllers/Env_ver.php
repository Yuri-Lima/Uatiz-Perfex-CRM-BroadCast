<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Env_ver extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        show_404();
    }

    public function activate()
    {
        $res = $this->val_lic();
        if ($res['status']) {
            $res['original_url']= $this->input->post('original_url');
        }
        echo json_encode($res);
    }

    public function upgrade_database()
    {
        $res = $this->val_lic();
        if ($res['status']) {
            $res['original_url']= $this->input->post('original_url');
        }
        echo json_encode($res);
    }

    private function getUserIP()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

    private function val_lic()
    {
        $this->load->library('Envapi');
        if (empty($this->input->post('purchase_key'))) {
            return ['status'=>false, 'message'=>'Purchase key is required'];
        }
        $module_name = $this->input->post('module_name');
        $envato_res = $this->envapi->getPurchaseData($this->input->post('purchase_key'));
        if (empty($envato_res)) {
            return ['status'=>false, 'message'=>'Something went wrong'];
        }
        if (!empty($envato_res->error)) {
            return ['status'=>false, 'message'=>$envato_res->description];
        }
        if (empty($envato_res->sold_at)) {
            return ['status'=>false, 'message'=>'Sold time for this code is not found'];
        }
        if ((false === $envato_res) || !is_object($envato_res) || isset($envato_res->error) || !isset($envato_res->sold_at)) {
            return ['status'=>false, 'message'=>'Something went wrong'];
        }
        $this->load->config($module_name.'/conf');
        if ($this->config->item('product_item_id') != $envato_res->item->id) {
            return ['status'=>false, 'message'=>'Purchase key is not valid'];
        }
        $this->load->library('user_agent');
        $data['user_agent']       = $this->agent->browser().' '.$this->agent->version();
        $data['activated_domain'] = base_url();
        $data['requested_at']     = date('Y-m-d H:i:s');
        $data['ip']               = $this->getUserIP();
        $data['os']               = $this->agent->platform();
        $data['purchase_code']    = $this->input->post('purchase_key');
        $data['envato_res']       = $envato_res;
        $data                     = json_encode($data);

        try {
            $headers = ['Accept' => 'application/json'];
            $request = Requests::post(REG_PROD_POINT, $headers, $data);
            if ((500 <= $request->status_code) && ($request->status_code <= 599) || 404 == $request->status_code) {
                update_option($module_name.'_verification_id', '');
                update_option($module_name.'_verified', true);
                update_option($module_name.'_last_verification', time());

                return ['status'=>true];
            }

            $response = json_decode($request->body);
            if (200 != $response->status) {
                return ['status'=>false, 'message'=>$response->message];
            }

            if (200 == $response->status) {
                $return = $response->data ?? [];
                if (!empty($return)) {
                    update_option($module_name.'_verification_id', $return->verification_id);
                    update_option($module_name.'_verified', true);
                    update_option($module_name.'_last_verification', time());
                    file_put_contents(__DIR__.'/../config/token.php', $return->token);

                    return ['status'=>true];
                }
            }
        } catch (Exception $e) {
            update_option($module_name.'_verification_id', '');
            update_option($module_name.'_verified', true);
            update_option($module_name.'_last_verification', time());

            return ['status'=>true];
        }

        return ['status'=>false, 'message'=>'Something went wrong'];
    }
}

// End of file Env_ver.php
// Location: ./application/controllers/Env_ver.php

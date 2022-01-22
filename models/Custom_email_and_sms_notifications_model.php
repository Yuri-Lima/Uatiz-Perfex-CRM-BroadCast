<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Custom_email_and_sms_notifications_model extends CI_Model {
	
	protected $table = '';
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix().'custom_templates';
    }

    public function get($id = '',$where=[])
    {
        if(!empty($where) || $where != ''){
            $this->db->where($where);
        }

        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        return $this->db->get($this->table)->result_array();
    }

    public function add($data){
        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }
	

}

/* End of file Custom_email_and_sms_notifications_model.php */

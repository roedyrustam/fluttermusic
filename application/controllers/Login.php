<?php
defined('BASEPATH') OR exit('No direct script access allowed');
error_reporting(0);

class Login extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->ci =& get_instance(); 
     
        date_default_timezone_set("Asia/Kolkata");

        if($this->session->userdata('admin_id'))
        {    
            redirect('admin');
        }
    }

    public function index($value='')
    {
        $this->load->view('login');
    }

    public function loginprocess($value='')
    {
        $user_name = $this->input->post('user_name');
        $pwd = $this->input->post('password');

        $result = $this->db->select('*')->where(array('admin_username'=>$user_name,'admin_password'=>md5($pwd)))->get('admin')->result();
        if($result)
        {
            $this->session->set_userdata('admin_name',$result[0]->admin_name);
            $this->session->set_userdata('admin_id',$result[0]->admin_id);
            
            redirect('admin');
        }
        else{
            $this->session->set_flashdata('error','true');
            redirect('login');
        }
        
    }
}

            
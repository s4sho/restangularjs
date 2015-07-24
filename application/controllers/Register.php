<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . "/libraries/REST_Controller.php";

class Register extends REST_Controller 
{
    public function __construct() 
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == "OPTIONS") {
            die();
        }
        
        parent::__construct();
            $this->load->model('users_model');
    }

    public function index_get()
    {
        $users = $this->users_model->get();

        if( ! is_null($users))
        {
                $this->response(array("response" => $users), 200);
        } else
        {
                $this->response(array("error" => "No users in the database."), 404);
        }
    }

    public function index_post()
    {
        if( ! $this->post("user"))
        {
                $this->response(NULL, 400);
        }

        $userId = $this->users_model->save($this->post("user"));

        if(! is_null($userId))
        {
                $this->response(array("response" => $userId), 200);
        } else
        {
                $this->response(array("error" => "There was an error."), 400);
        }
    }

    public function validate_email_get($email_address, $email_code)
    {
        $email_code = trim($email_code);

        $validated = $this->users_model->validate_email($email_address, $email_code);

        if($validated ===true)
        {
            //$this->load->view('version5/head');
            //$this->load->view('version5/header_xs');
            $this->load->view('registration/view_email_validated',array('email_address' => $email_address));
            //$this->load->view('version5/foot');
        }
        else
        {
            echo 'Error giving email activated confirmation, please contact ...';
            //echo $this->lang->line('error_email_activated_confir').$this->config->item('admin_email');
        }
    }
    
    function check_if_email_exists($email)
    {
        $this->db->where('email', $email);
        $result = $this->db->get('user');
        
        if ($result->num_rows() > 0)
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
	
    function check_if_username_exists($username)
    {
        $this->db->where('username', $username);
        $result = $this->db->get('user');
        
        if ($result->num_rows() > 0)
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
	
}
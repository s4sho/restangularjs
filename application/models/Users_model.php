<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {
    
    public function __construct()
    {
       parent::__construct();
    }
	
    public function get($id = NULL)
    {
        if( ! is_null($id))
        {
            $query = $this->db->select("*")->from("user")->where("user_id", $id)->get();
            if($query->num_rows() === 1)
            {
                return $query->row_array();
            }
            return NULL;
        }

        $query = $this->db->select("*")->from("user")->get();
        if($query->num_rows() > 0)
        {
            return $query->result_array();
        }
        return NULL;
    }

    public function save($user)
    {
        $this->db->set(
                $this->_setUser($user)
        )
        ->insert("user");

        if ($this->db->affected_rows() === 1)
        {
            $inserted_id = $this->db->insert_id();
            $this->set_session($user["username"], $user["email"]);
            //print_r($this->session->all_userdata()); // for testing purposes
            $this->send_validation_email();
            return $inserted_id;
        }
        else
        {
            // Notify the admin by email the user registration is not working
            $this->load-library('email');
            $this->email->from('bot_email', 'My Project');
            $this->email->to('admin_email');
            //$this->email->subject('Problem inserting user into database');
            $this->email->subject($this->lang->line('problem_inserting_user'));

            if (isset($user["email"]))
            {
                //$this->email->message('Unable to register and insert user with the email of '.$email.' to the database.');
                $this->email->message($this->lang->line('unable_register_insert_user_with_email_part1').$user["email"].$this->lang->line('unable_register_insert_user_with_email_part2'));
            }
            else
            {
                //$this->email->message('Unable to register and insert user to the database.');
                $this->email->message($this->lang->line('unable_register_insert_user'));
            }

            $this->email->send();
            return false;
        }
    }

    public function _setUser($user)
    {
            return array(
                    "username"	=>		$user["username"],
                    "email"     =>		$user["email"],
                    //"password"	=>		sha1($user["password"])
					"password"	=>		sha1($this->config->item('salt').$user["password"])
            );
    }

    public function set_session($username, $email)
    {
        $sql = "SELECT user_id, reg_time FROM user WHERE email = '".$email."' LIMIT 1";
        $result = $this->db->query($sql);
        $row = $result->row();

        $sess_data = array(
                    'user_id' => $row->user_id,
                    'username' => $username,
                    'email' => $email,
                    'logged_in' => 0
                    );

        $this->email_code = md5((string)$row->reg_time);
        $this->session->set_userdata($sess_data);
    }

    private function send_validation_email()
    {    

        $config = Array ( 
                'protocol' => 'smtp',
                'smtp_host' => 'ssl://smtp.googlemail.com',
                'smtp_port' => 465,
				// Insert your e-mail
                'smtp_user' => 'aleks4nd3r@gmail.com',
				// Insert your password
                'smtp_pass' => 'Fr33lanc3r'
        );

        $this->load->library('email',$config);
        $email = $this->session->userdata('email');
        $email_code = $this->email_code;
        $this->email->set_newline("\r\n");

        $this->email->set_mailtype('html');
		// Insert your e-mail
        $this->email->from('aleks4nd3r@gmail.com','My project');
        $this->email->to($email);
        $this->email->subject('Email validation');
        $langlang = $this->session->userdata('language');
        $message = '<!DOCTYPE>
                    <head>
                    </head><body>';
        $message .= '<p>Hello, '.$this->session->userdata('username').'!</p>';


        $message .= "<p>
                        <a href=".base_url().'register/validate_email/'.$email.'/'.$email_code.">Click to validate your e-mail</a>
                    </p>";


        // AngularJS cannot recognise this link. There is no such link in AngularJS routes
        //$message .= '<p>
                        //<a href="http://localhost:48080/dist/#/email_validated/'.$email.'/'.$email_code.'">Click to validate your e-mail</a>
                    //</p>';



        //$message .= "<p>".$this->lang->line('validation_email_part2').$this->session->userdata('username').",</p>";
        //$message .= "<p>"
                        //.$this->lang->line('validation_email_part3').
                        //"<strong>
                            //<a href=".base_url().'register/validate_email/'.$email.'/'.$email_code.">"
                                //.$this->lang->line('validation_email_part4').
                            //"</a>
                        //</strong>"
                        //.$this->lang->line('validation_email_part5').
                    //"</p>";
        //$message .= "<p>".$this->lang->line('validation_email_part6')."</p>";
        //$message .= "<p>".$this->lang->line('validation_email_part7')."</p>";
        $message .= "</body></html>";

        $this->email->message($message);


        if(!$this->email->send())
        {
            //show_error($this->email->print_debugger());
                        return 'error sending mail';
//                            return $this->lang->line('account_create_problem').$this->config->item('admin_email');
                        //die();
        }
        return true;
        //if($this->email->send())
        //{
            //echo 'Your e-mail has been send.';
        //}
        //else
        //{
            //echo 'print';
            //show_error($this->email->print_debugger());
        //}

    }

    public function validate_email($email_address, $email_code)
    {
        $sql = "SELECT email, reg_time, username FROM user WHERE email = '{$email_address}' LIMIT 1";
        $result = $this->db->query($sql);
        $row = $result->row();

        if ($result->num_rows() === 1 && $row->username)
        {
            if(md5((string)$row->reg_time) === $email_code)
            $result = $this->activate_account($email_address);
            if ($result === true)
            {
                return true;
            }
            else
            {
                echo 'There was an error when activating your account. Please contact ' . $this->config->item('admin_email');
                //echo $this->lang->line('error_activating_account').$this->config->item('admin_email');
                return false;
            }
        }
        else
        {
            echo 'There was an error when activating your account. Please contact ' . $this->config->item('admin_email');
            //echo $this->lang->line('error_activating_account').$this->config->item('admin_email');
        }
    }

    private function activate_account($email_address)
    {
        //$this->load->model('balance');
        $sql = "UPDATE user SET activated = 1 WHERE email = '{$email_address}' LIMIT 1";
        $this->db->query($sql);
                $user_id = $this->session->userdata('user_id');
                //$this->balance->create_db_new($user_id);
        if ($this->db->affected_rows() === 1)
        {
            //$this->balance->create_event($user_id, 'data');
            return true;
        }
        else
        {
            //echo 'Error when activating your account in the database, please contact ' . $this->config->item('admin_email');
            echo $this->lang->line('there_error_activating_account').$this->config->item('admin_email');
            return false;
        }
    }
    


}

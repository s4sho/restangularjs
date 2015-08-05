<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class Model_login extends CI_Model {
    
    public function login_user($email, $password)
    {
		/*
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        */
        $sql = "SELECT user_id, username, email, password, activated FROM user WHERE email = '".$email."' LIMIT 1";
        $result = $this->db->query($sql);
        $row = $result->row();
       
        if ($result->num_rows() === 1)
        {
            if ($row->activated == 1)
            {
                if ($row->password === sha1($this->config->item('salt').$password)) // sha1, salt
                {
                    $session_data = array(
                    'user_id'  => $row->user_id,
                    'username' => $row->username,
                    'email'    => $row->email
                    );
                    $this->set_session($session_data);
                    return 'logged_in';
                }
                else
                {
                    return 'incorrect_password';
                }
            }
            else
            {
                return 'not_activated';
            }
        }
        else
        {
            return 'email_not_found';
        }
    }
    
    private function set_session($session_data)
    {
        $sess_data = array(
            'user_id'   => $session_data['user_id'],
            'username'  => $session_data['username'],
            'email'     => $session_data['email'],
            'user_id'   => $session_data['user_id'],
            'logged_in' => 1
        );
        
        $this->session->set_userdata($sess_data);
    }
    
    public function email_exists($email)
    {
        $sql = "SELECT username, email FROM user WHERE email = '{$email}' LIMIT 1";
        $result = $this->db->query($sql);
        $row = $result->row();
        
        return ($result->num_rows() === 1 && $row->email) ? $row->username : false;
    }
    
    public function verify_reset_password_code($email, $code) 
    {
        $sql = "SELECT username, email FROM user WHERE email = '{$email}' LIMIT 1";
        $result = $this->db->query($sql);
        $row = $result->row();
        
        if ($result->num_rows() === 1)
        {
            return ($code == md5($this->config->item('salt').$row->username)) ? true: false; // md5 salt
        }
        else
        {
            return false;
        }
    }
    
    public function update_password()
    {
        $email = $this->input->post('email');
        $password = sha1($this->config->item('salt') . $this->input->post('password'));
        
        $sql = "UPDATE user SET password = '{$password}' WHERE email = '{$email}' LIMIT 1";
        $this->db->query($sql);
        
        if ($this->db->affected_rows() === 1)
        {
            return true;
        }
        else
        {
            return 'false';
        }
    }
    
    public function get_old_password ($user_id)
    {
        $sql = "SELECT password FROM user WHERE user_id = '".$user_id."'";
        $result = $this->db->query($sql);
        $row = $result->row();
        return $row->password;
    }
    
    public function change_password($user_id)
    {
        $password = sha1($this->config->item('salt') . $this->input->post('password_new'));
        
        $sql = "UPDATE user SET password = '{$password}' WHERE user_id = '{$user_id}'";
        $this->db->query($sql);
        
        if ($this->db->affected_rows() === 1)
        {
            return 'true';
        }
        else
        {
            return 'false';
        }
    }
}
<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class Login extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('model_login');
    }
    
    public function index()
    {
        $this->load->view('includes/head');
        $this->load->view('login/view_login_form');
        $this->load->view('includes/footer');
    }
    
    public function login_user()
    {
		//get the json posted and store it in an array
		$data = json_decode(file_get_contents('php://input'), TRUE);		
		//validate the error
        $this->load->library('form_validation');
        //set the data to be validated to be the json array
		$this->form_validation->set_data($data);
		$this->form_validation->set_error_delimiters($suffix='', $prefix='');
        $this->form_validation->set_rules('email', 'Email Address', 'trim|required|min_length[6]|max_length[50]|valid_email');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[50]');		      
		if ($this->form_validation->run() == FALSE)
        {
			$response = array('status'=>'invalid', 'message'=>$this->form_validation->error_string());			
            echo json_encode($response);
			return;
        }
		$result = $this->model_login->login_user($data['email'], $data['password']);      
		switch ($result)
		{
			case 'logged_in':
				$user = $this->session->username;
				//$this->session->userdata('username')
				$response = array('status'=>'logged_in', 'message'=>'Login successful', 'user'=>$user);			
				echo json_encode($response);				
				break;
			case 'incorrect_password':		
				$response = array('status'=>'incorrect_password', 'message'=>'Incorrect password');			
				echo json_encode($response);				
				break;
			case 'not_activated':
				$response = array('status'=>'not_activated', 'message'=>'Account not activated');			
				echo json_encode($response);				
				break;
			case 'email_not_found':
				$response = array('status'=>'email_not_found', 'message'=>'Email not found');			
				echo json_encode($response);				
				break;
		}
        return;        
    }
    
    function open_reset_password()   // public function?
    {
        $this->load->view('includes/head');
        $this->load->view('login/view_reset_password');
        $this->load->view('includes/footer');
    }
    
    public function reset_password()
    {
        if (isset($_POST['email']) && !empty($_POST['email']))
        {
            $this->load->library('form_validation');
            // first check if its a valid email or not
            $this->form_validation->set_rules('email', 'Email Address', 'trim|required|min_length[6]|max_length[50|valid_email|xss_clean');
            
            if($this->form_validation->run() == FALSE)
            {
                //email didn't validate, send back to reset password and show errors
                $this->load->view('includes/head');
                $this->load->view('login/view_login', array('error' => 'Please supply a valid email address.')); // broken
                $this->load->view('includes/footer');
            }
            else
            {
                $email = trim($this->input->post('email'));
                $result = $this->model_login->email_exists($email);
            
                if ($result)
                {
                    // if we found the email, $result is now their first name (or username!!??)
                    $this->send_reset_password_email($email, $result);
                    $this->load->view('includes/head');
                    $this->load->view('login/view_reset_password_sent', array('email' => $email));
                    $this->load->view('includes/footer');
                }
                else
                {
                    $this->load->view('includes/head');
                    $this->load->view('login/view_reset_password', array('error' => 'Email adress not registrated with MyProject.'));
                    $this->load->view('includes/footer');
                }
            }
        }
        else
        {
            $this->load->view('includes/head');
            $this->load->view('login/view_reset_password');
            $this->load->view('includes/footer');
        }
    }
    
    public function reset_password_form($email, $email_code)
    {
        if (isset($email, $email_code))
        {
            $email = trim($email);
            $email_hash = sha1($email.$email_code); // sha1 *************************************************************************
            $verified = $this->model_login->verify_reset_password_code($email, $email_code);
        
            if ($verified)
            {
                $this->load->view('includes/head');
                $this->load->view('login/view_update_password', array('email_hash' => $email_hash, 'email_code' => $email_code, 'email' => $email ));
                $this->load->view('includes/footer');
            }
            else
            {
                $this->load->view('includes/head');
                // send back to reset_password page, not update_password, if there was a problem
                $this->load->view('login/view_reset_password', array('error' => 'There was a problem with a link. Please click it again or request
                                                                     to reset your password again', 'email' => $email));
                $this->load->view('includes/footer');
            }
        }
    }
    
    private function send_reset_password_email($email, $username)
    {
        $config = Array ( 
                'protocol' => 'smtp',
                'smtp_host' => 'ssl://smtp.googlemail.com',
                'smtp_port' => 465,
                'smtp_user' => 'aleks4nd3r@gmail.com',
                'smtp_pass' => 'Fr33lanc3r',
        );
            
        $this->load->library('email',$config);
        $email_code = md5($this->config->item('salt').$username); // md5 salt ****************************************************
        
        $this->email->set_newline("\r\n");
        
        $this->email->set_mailtype('html');
        $this->email->from('aleks4nd3r@gmail.com','MyProject');
        $this->email->to($email);
        $this->email->subject('Please reset your password at MyProject');
        
        $message = "<!DOCTYPE html>
                    <head>
                    </head><body>";
        $message .= "<p>Dear '.$username.',</p>";
        $message .= "<p>
                        We want to help you reset your password. Please
                        <strong>
                            <a href=".base_url().'login/reset_password_form/'.$email.'/'.$email_code.">
                                click here
                            </a>
                        </strong>
                        to reset your password.
                    </p>";
        $message .= "<p>Thank you!</p>";
        $message .= "<p>MyProject team</p>";
        $message .= "</body></html>";
        
        $this->email->message($message);
		
        if(!$this->email->send())
        {
            //show_error($this->email->print_debugger());
			echo 'problem!';
			die();
        }
    }
    
    public function update_password()
    {
        if (!isset($_POST['email'], $_POST['email_hash']) || $_POST['email_hash'] !== sha1($_POST['email'] .$_POST['email_code']))
        {
            // Either a hacker or they changed their email in the email field, just die.
            die('Error updating your password TEST');
        }
        
        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('email_hash', 'Email Hash', 'trim|required');
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]|max_length[50]|matches[password_conf]|xss_clean');   
        $this->form_validation->set_rules('password_conf', 'Confirmed Password', 'trim|required|min_length[6]|max_length[50]|xss_clean');
        
        if ($this->form_validation->run() == FALSE)
        {
            //user didn't validate, send back ti update password form and show errors
            $this->load->view('includes/head');
            $this->load->view('login/view_update_password');
            $this->load->view('includes/footer');
        }
        else
        {
            // successful update
            // return user's username if successful
            $result = $this->model_login->update_password();
            
            if ($result)
            {
                $this->load->view('includes/head');
                $this->load->view('login/view_update_password_success');
                $this->load->view('includes/footer');
            }
            else
            {
                // this should never happen
                $this->load->view('includes/head');
                $this->load->view('login/view_update_password', array('error' => 'Problem updating your password. Please contact WITE EMAIL...'));
                $this->load->view('includes/footer');
            }
        }
    }
    
    function logout()  
    {  
        $this->session->sess_destroy();  
		echo json_encode("OK");
		return;
    }
	
	function check_logged_in()
	{
		if ($this->session->userdata('logged_in'))
        {
			//not logged in
			echo json_encode('logged_in');
        }
        else
        {
            //not logged in
			echo json_encode('not_logged_in');
        }
		
	}
    
/* CHANGE PASSWORD */
    
    function open_change_password()  
    {  
        $this->load->view('includes/head');
        $this->load->view('login/view_change_password');
        $this->load->view('includes/footer'); 
    }
    
    function change_password()  
    {  
        $this->load->model('model_login');
        $password_old = $_POST ['password_old'];
        $password_old_encrypt = sha1($this->config->item('salt').$password_old);
        $user_id = $this->session->userdata('user_id');
        $password = $this->model_login->get_old_password ($user_id);
        //echo $password_old_encrypt.'*'.$user_id.'*'.$password;
        if($password_old_encrypt == $password)
        {
            if($this->check_password())
            {
                $change = $this->model_login->change_password($user_id);
                if($change)
                {
                    echo 'Your password has been changed!';
                }
                else
                {
                    echo 'Changing of password failed';
                }
            }
            
        }
        else
        {
            echo'not OK';
        }
    }
    
    function check_password()
    {
        $this->load->library('form_validation');
        
        // rules for password
        $this->form_validation->set_rules('password_new', 'New Password', 'trim|required|min_length[6]|max_length[50]|matches[password_new_conf]|xss_clean');   
        $this->form_validation->set_rules('password_new_conf', 'New Password Confirmation', 'trim|required|min_length[6]|max_length[50]|xss_clean');
        
        if ($this->form_validation->run() == FALSE)
        {
            // New Password & New Password Confirmation do not match
            $this->load->view('includes/head');
            $this->load->view('login/view_change_password');
            $this->load->view('includes/footer'); 
        }
        else
        {
            return 'true';
        }
    }
    
    
}
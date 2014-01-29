<?php
class user extends SS_controller{
	
	function __construct(){
		parent::__construct();
	}
	
	function logout(){
		$this->user->sessionLogout();
	}
	
	function login(){
		
		if($this->input->get_post('username')){
			
			$user = $this->user->verify($this->input->get_post('username'), $this->input->get_post('password'));

			$this->session->set_userdata('user_id', intval($user['id']));
			$this->user->updateLoginTime();
			redirect();
		}
		
		$this->load->view('login', compact('alert'));
		
	}
	
	function signUp(){
		$this->output->title='新用户注册';
		$this->load->view('user/signup');
		$this->load->view('user/signup_sidebar',true,'sidebar');
	}
	
	function profile(){
		
		$people=array_merge_recursive($this->people->fetch($this->user->id),$this->input->sessionPost('people'));
		$people_meta=array_merge_recursive(array_column($this->people->getMeta($this->user->id),'content','name'),$this->input->sessionPost('people'));
		$this->load->addViewArrayData(compact('people','people_meta'));
		
		$this->output->title='用户资料';
		$this->load->view('user/profile');
		$this->load->view('user/profile_sidebar',true,'sidebar');
	}
	
}
?>
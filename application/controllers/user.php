<?php
class user extends LB_controller{
	
	function __construct(){
		parent::__construct();
	}
	
	function index($id=NULL){
		
		switch ($this->input->method) {
			case 'GET':
				if(is_null($id)){
					$this->getList();
				}
				else{
					$this->fetch($id);
				}
				break;
			
			case 'POST':
				$this->add();
				break;
			
			case 'PUT':
				$this->update($id);
				break;
			
			case 'DELETE':
				$this->remove($id);
				break;
		}
	}
	
	function fetch($id){
		
		$args=$this->input->get();
		
		$user = $this->user->fetch($id, $args);
		
		$this->output->set_output($user);
	}
	
	function getList(){
		
		$args=$this->input->get();
		
		$result=$this->user->getList($args);

		$this->output->set_output($result['data']);
		$this->output->set_status_header(200, 'OK, '.$result['total'].' Users in Total');
	}
	
	function add(){
		$data = $this->input->data();
		
		$user_id = $this->user->add($data);
		
		$this->fetch($user_id);
	}
	
	function remove($id){
		$this->user->remove($id);
	}
	
	function logout(){
		$this->user->sessionLogout();
		redirect();
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
	
	function updatePassword(){
		
	}
	
}
?>
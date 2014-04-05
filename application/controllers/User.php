<?php
class User extends LB_Controller{
	
	function __construct(){
		parent::__construct();
	}
	
	function index($id = NULL){
		
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
		
		$result = $this->user->getList($args);

		$this->output->set_output($result['data']);
		$this->output->set_status_header(200, 'OK, '.$result['total'].' Users in Total');
	}
	
	function add(){
		$user_id = $this->user->add($this->input->data(), $this->input->get());
		$this->fetch($user_id);
	}
	
	function update($id){
		$this->user->id = $id;
		$this->user->update($this->input->data());
		$this->fetch($id);
	}
	
	function remove($id){
		$this->user->id = $id;
		$this->user->remove();
	}
	
	function logout(){
		$this->user->sessionLogout();
		redirect();
	}
	
	function login(){
		
		if($this->input->post_get('username') && $this->input->post_get('password')){
			$user = $this->user->verify($this->input->get_post('username'), $this->input->get_post('password'));
			$this->user->sessionLogin($user['id']);
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
		
		$people=array_merge_recursive($this->people->fetch($this->user->session_id),$this->input->sessionPost('people'));
		$people_meta=array_merge_recursive(array_column($this->people->getMeta($this->user->session_id),'content','name'),$this->input->sessionPost('people'));
		$this->load->addViewArrayData(compact('people','people_meta'));
		
		$this->output->title='用户资料';
		$this->load->view('user/profile');
		$this->load->view('user/profile_sidebar',true,'sidebar');
	}
	
	function config($item = null){
		
		switch($this->input->method){
			case 'GET':
				break;
			
			case 'POST':
				$data = $this->input->data();
				
				if(is_array($data)){
					foreach($data as $key => $value){
						$this->user->config($key, $value);
					}
				}
				else{
					$this->user->config($item, $data);
				}
				
				break;
		}
		
		$this->output->set_output(is_null($item) ? $this->user->config($item) : array($item=>$this->user->config($item)));
		
	}
	
}
?>

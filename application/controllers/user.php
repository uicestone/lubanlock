<?php
class user extends SS_controller{
	
	function __construct(){
		parent::__construct();
	}
	
	function logout(){
		$this->user->sessionLogout();
	}
	
	function login(){
		
		if($this->input->post()){
			$user=$this->user->verify($this->input->get_post('username'), $this->input->get_post('password'));

			if($user){
				$this->session->set_userdata('user_id', intval($user['id']));
				$this->user->updateLoginTime();
				$this->output->set_output(json_encode($user));
			}
			else{
				$this->output->set_status_header(403, lang('login_info_error'));
			}
		}
		
		$this->load->view('login');
		
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
	
	function submit($submit){
		
		$this->load->library('form_validation');
		
		try{
			
			if($submit=='profile'){
				if($this->input->post('password_new')){
					if($this->user->verify($this->user->name, $this->input->post('password'))===false){
						throw new Exception('当前密码错误');
					}
					if($this->input->post('password_new')!==$this->input->post('password_new_confirm')){
						throw new Exception('两次密码输入不一致');
					}
					$this->user->updatePassword($this->user->id, $this->input->post('password_new'));
					$this->output->message('用户名/密码修改成功');
				}
				
				if($this->input->post('username') && $this->input->post('username')!==$this->user->name){
					$this->form_validation->set_rules('username','用户名','is_unique[user.name]');
					if($this->form_validation->run()!==false){
						$this->user->updateUsername($this->user->id, $this->input->post('username'));
					}else{
						throw new Exception(validation_errors());
					}
				}
				
				$people=$this->input->sessionPost('people');
				$meta=$this->input->sessionPost('people_meta');
				
				$this->people->update($this->user->id,$people);
				$this->people->updateMetas($this->user->id, $meta);
				
				$this->output->message($this->output->title.' 已保存');
			}

			elseif($submit=='signup'){

				$this->form_validation->set_rules('username','用户名','required|is_unique[user.name]')
						->set_rules('password','密码','required')
						->set_rules('password_confirm','确认密码','matches[password]')
						->set_message('matches','两次密码输入不一致');

				if($this->form_validation->run()===false){
					$this->output->message(validation_errors(),'warning');
					throw new Exception;
				}
				
				$data=array(
					'name'=>$this->input->post('username'),
					'password'=>$this->input->post('password')
				);
				
				$user_id=$this->user->add($data);
				
				$this->session->set_userdata('user/id',$user_id);
				$this->user->__construct($user_id);
				
				$this->output->status='redirect_href';
				$this->output->data='';
			}
		}
		catch (Exception $e){
			if($e->getMessage()){
				$this->output->message($e->getMessage(), 'warning');
			}
			$this->output->status='failed';
		}
		
		if(is_null($this->output->status)){
			$this->output->status='success';
		}
	}
}
?>
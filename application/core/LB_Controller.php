<?php
class LB_Controller extends CI_Controller{
	
	var $default_method='index';
	
	/**
	 * 当前控制器允许的用户组
	 * array()为登录即可
	 * true为不限制
	 * 包含子数组时，按照独立方法区分权限，子数组的键名是方法名
	 * @var bool or array 
	 */
	var $permission=array();
	
	function __construct(){
		parent::__construct();
		
		$this->user->initialize();
		
		if($this->input->accept('application/json')){
			$this->output->set_content_type('application/json');
		}
		
	}
	
	function _output($output){
		
		if($this->input->accept('application/json')){
			echo json_encode($output);
		}
		elseif(is_string($output)){
			echo $output;
		}
		else{
			echo var_export($output);
		}
		
	}
	
}
?>
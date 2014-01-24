<?php
class SS_Controller extends CI_Controller{
	
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
		/*
		 * 处理$class和$method，并定义为常量
		 */
		global $class,$method;
		
		//使用controller中自定义的默认method
		if($method=='index'){
			$method=$this->default_method;
		}
		
		//定义$class常量，即控制器的名称
		define('CONTROLLER',$class);
		define('METHOD',$method);
		
		//CONTROLLER !=='index' && $this->output->enable_profiler(TRUE);
		
		/*
		 * 自动载入的资源，没有使用autoload.php是因为后者载入以后不能起简称...
		 */
		$this->load->model('company_model','company');
		$this->load->model('object_model','object');
		$this->load->model('group_model','group');
		$this->load->model('user_model','user');
		$this->load->model('tag_model','tag');
		$this->load->model('message_model','message');
		
		$this->user->initialize();
		
		if($this->input->accept('application/json')){
			$this->output->set_content_type('application/json');
		}
		
		if(file_exists(APPPATH.'language/chinese/'.$this->company->syscode.'_lang.php')){
			$this->load->language($this->company->syscode);
		}
		
	}
	
	function _output($output){
		
		if($this->input->accept('application/json')){
			echo json_encode($output);
		}
		else{
			echo $output;
		}
		
	}
	
}
?>
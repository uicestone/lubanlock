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
		$this->load->model('people_model','people');
		$this->load->model('group_model','group');
		$this->load->model('user_model','user');
		$this->load->model('tag_model','tag');
		$this->load->model('message_model','message');
		
		$this->user->initialize();
		
		if($this->input->is_ajax_request()){
			$this->output->set_content_type('application/json');
		}
		
		if(file_exists(APPPATH.'language/chinese/'.$this->company->syscode.'_lang.php')){
			$this->load->language($this->company->syscode);
		}
		
		/*
		 * 页面权限判断
		 */
/*
		if(isset($this->permission[METHOD])){
			$this->permission=$this->permission[METHOD];
		}
		
		 if($this->permission===array() && !$this->user->isLogged()){
			if($this->output->as_ajax){
				$this->output->set_header('Location: /login');
				$this->output->set_status_header(301, lang('Login Required'));
				return;
			}else{
				redirect('login');
			}
		}
		
		if(is_array($this->permission) && $this->permission && (!$this->user->groups || !array_intersect(array_keys($this->user->groups),$this->permission))){
			if($this->output->as_ajax){
				$this->output->set_status_header(401, lang('No Permission'));
				return;
			}else{
				show_error('No Permission');
			}
		}
		 */
		
	}
	
	function _output($output){
		if($this->input->is_ajax_request()){
			echo json_encode($output);
		}
		else{
			echo $output;
		}
	}
	
}
?>
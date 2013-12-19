<?php
class Index extends SS_Controller{
	function __construct() {
		parent::__construct();
	}
	
	function index(){
		$this->load->view('index');
	}
	
	function robots(){
		$this->output->set_output($this->config->user_item('robots'));
	}
	
	function favicon(){
		
		$this->output->set_content_type('ico');
		
		foreach(array(
			APPPATH.'../web/images/favicon_'.$this->company->syscode.'.ico',
			APPPATH.'../web/images/favicon_'.$this->company->type.'.ico',
			APPPATH.'../web/images/favicon.ico',
		) as $path){
			if(file_exists($path)){
				readfile($path);
			}
		}
	}
}

?>

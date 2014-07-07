<?php
class Index extends LB_Controller{
	function __construct() {
		parent::__construct();
	}
	
	function index(){
		
		if(!$this->user->isLogged()){
			redirect('login');
		}
		
		$this->load->view('index');
	}
	
	function robots(){
		$this->output->set_output($this->company->config('robots'));
	}
	
}

?>

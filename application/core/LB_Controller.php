<?php
class LB_Controller extends CI_Controller{
	
	function __construct(){
		parent::__construct();
		
		$this->user->initialize();
		
		if($this->input->accept('application/json')){
			$this->output->set_content_type('application/json');
		}
		
		$this->output->set_header('X-UA-Compatible: IE=edge');
		
	}
	
	function _output($output){
		
		if($this->input->accept('application/json')){
			echo json_encode($output);
		}
		elseif(is_string($output)){
			echo $output;
		}
		else{
			var_export($output);
		}
		
	}
	
}
?>
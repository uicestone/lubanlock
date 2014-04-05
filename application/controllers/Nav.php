<?php
class Nav extends LB_Controller{
	function __construct() {
		parent::__construct();
		$this->load->model('nav_model','nav');
	}
	
	function index($id = NULL){
		switch($this->input->method){
			case 'GET':
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
		
		$this->get($id);

	}
	
	function get(){
		$this->output->set_output($this->nav->get());
	}
	
	function add(){
		$this->nav->add($this->input->data());
	}
	
	function update($id){
		$this->nav->update($this->input->data, $id);
	}
	
	function remove($id){
		$this->nav->remove($id);
	}
}

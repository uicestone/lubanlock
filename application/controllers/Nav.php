<?php
class Nav extends LB_Controller{
	function __construct() {
		parent::__construct();
		$this->load->model('nav_model','nav');
		$this->output->set_content_type('application/json');
	}
	
	function index($id=NULL){
		switch($this->input->method){
			case 'GET':
				$this->get();
				break;
			case 'POST' && is_null($id):
			case 'PUT':
				$this->add();
			case 'POST':
				$this->update($id);
			case 'DELETE':
				$this->remove($id);
		}
	}
	
	function get(){
		$this->output->set_output($this->nav->get());
	}
	
	function add(){
		$this->nav->add($this->input->data());
	}
	
	function update($id){
		
	}
	
	function remove($id){
		
	}
}

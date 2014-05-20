<?php
class Nav extends LB_Controller{
	function __construct() {
		parent::__construct();
		$this->load->model('nav_model','nav');
	}
	
	function index($name = NULL){
		switch($this->input->method){
			case 'GET':
				$this->get();
				break;
			
			case 'POST':
				$this->add();
				break;
				
			case 'PUT':
				$this->update($name);
				break;
				
			case 'DELETE':
				$this->remove($name);
				break;
		}
		
	}
	
	function get($name = null){
		$this->output->set_output(is_null($name) ? $this->nav->get() : $this->nav->fetch($name));
	}
	
	function add(){
		$this->nav->add($this->input->data());
		$this->output->set_output($this->nav->fetch($this->input->data('name')));
	}
	
	function update($name){
		$this->nav->update($this->input->data, array('name'=>$name));
	}
	
	function remove($name){
		$this->nav->remove(array('name'=>$name));
	}
}

<?php
class Object extends SS_Controller{
	
	function __construct() {
		parent::__construct();
		$this->load->model('object_model','object');
		$this->output->set_content_type('application/json');
	}
	
	function single($id=NULL){
		switch ($this->input->method) {
			case 'GET':
				$this->fetch($id);
				break;
			
			case 'POST':
				$this->update($id);
				break;
			
			case 'PUT':
				$this->add();
				break;
			
			default:
				show_error('input method error: '.$this->input->method);
		}
	}
	
	function fetch($id){
		$args=$this->input->get();
		
		if($args===false){
			$args=array();
		}
		
		$this->output->set_output(json_encode($this->object->fetch($id,$args)));
	}
	
	function update($id){
		$this->object->update($this->input->post());
	}
	
	function add(){
		$insert_id = $this->object->add($this->input->put());
		$this->output->set_output($insert_id);
	}
	
	function getList(){
		$args=$this->input->get();

		if($args===false){
			$args=array();
		}
		
		$this->output->set_output(json_encode($this->object->getList($args)));
	}
	
	function removeTag($item_id){
		
		$controller=CONTROLLER;
		
		$label_name=$this->input->post('label');
		
		$this->$controller->removeTag($item_id, $label_name);
	}
	
	function addTag($item_id){
		$controller=CONTROLLER;
		
		$label_name=$this->input->post('label');
		
		$this->$controller->addTag($item_id, $label_name);
	}
	
}

?>

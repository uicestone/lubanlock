<?php
class Object extends SS_Controller{
	
	function __construct() {
		parent::__construct();
		$this->load->model('object_model','object');
	}
	
	function index($id=NULL){
		
		switch ($this->input->method) {
			case 'GET':
				if(is_null($id)){
					$this->getList();
				}
				else{
					$this->fetch($id);
				}
				break;
			
			case 'POST':
				$this->add();
				break;
			
			case 'PUT':
				$this->update($id);
				break;
			
		}
	}
	
	function fetch($id){
		
		$args=$this->input->get();
		
		$this->output->set_output($this->object->fetch($id,$args));
	}
	
	function update($id){
		
		$this->object->id=$id;
		
		$this->object->update($this->input->data());
		
		$this->fetch($this->object->id);
	}
	
	function add(){
		$data = $this->input->data();
		
		$this->object->id = $this->object->add($data);
		
		$this->fetch($this->object->id);
	}
	
	function getList(){
		
		$args=$this->input->get();
		
		$result=$this->object->getList($args);

		$this->output->set_output($result['data']);
		$this->output->set_status_header(200, 'OK, '.$result['total'].' Objects in Total');
	}
	
	function meta($object_id){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				$this->output->set_output($this->object->getMeta());
				break;
			
			case 'PUT':
			case 'POST' && $this->input->data('id') === false:
				$meta_id=$this->object->addMeta($this->input->data());
				$this->output->set_output($this->object->getMeta(array('id'=>$meta_id)));
				break;
			
			case 'POST':
				$this->object->updateMeta($this->input->data());
				$this->output->set_output($this->object->getMeta(array('id'=>$this->input->data('id'))));
				break;
			
			case 'DELETE':
				$this->object->removeMeta($this->input->get());
				break;
		}
	}
	
	function relative($object_id){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				$this->output->set_output($this->object->getRelative());
				break;
			
			case 'PUT':
			case 'POST' && $this->input->data('id') === false:
				$relative_id=$this->object->addRelative($this->input->data());
				$this->output->set_output($this->object->getRelative(array('id'=>$relative_id)));
				break;
			
			case 'POST':
				$this->object->updateRelative($this->input->data());
				$this->output->set_output($this->object->getRelative(array('id'=>$this->input->data('id'))));
				break;
			
			case 'DELETE':
				$this->object->removeRelative($this->input->get());
				break;
		}
	}
	
	function status($object_id){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				$this->output->set_output($this->object->getStatus());
				break;
			
			case 'PUT':
			case 'POST' && $this->input->data('id') === false:
				$status_id=$this->object->addStatus($this->input->data());
				$this->output->set_output($this->object->getStatus(array('id'=>$status_id)));
				break;
			
			case 'POST':
				$this->object->updateStatus($this->input->data());
				$this->output->set_output($this->object->getStatus(array('id'=>$this->input->data('id'))));
				break;
			
			case 'DELETE':
				$this->object->removeStatus($this->input->get());
				break;
		}
	}
	
	function tag($object_id){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				$this->output->set_output($this->object->getTag());
				break;
			
			case 'PUT':
			case 'POST':
				$tag_id=$this->object->addTag($this->input->data());
				$this->output->set_output($this->object->getTag(array('id'=>$tag_id)));
				break;
			
			case 'DELETE':
				$this->object->removeTag($this->input->get());
				break;
		}
	}
	
}

?>

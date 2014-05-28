<?php
class Object extends LB_Controller{
	
	function __construct() {
		parent::__construct();
	}
	
	function index($id = NULL){
		
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
			
			case 'DELETE':
				$this->remove($id);
				break;
		}
	}
	
	function fetch($id){
		
		$args = $this->input->get();
		
		$object = $this->object->fetch($id, $args);
		
		//meta在后台使用array，在前端使用Object表示，因此输出时要转化
		array_key_exists('meta', $object) && $object['meta'] = (object)$object['meta'];
		array_key_exists('tag', $object) && $object['tag'] = (object)$object['tag'];
		
		$this->output->set_output($object);
	}
	
	function getList(){
		
		$args=$this->input->get();
		
		$result=$this->object->getList($args);

		$this->output->set_output($result['data']);
		$this->output->set_status_header(200, 'OK, '.$result['info']['total'].' Objects in Total, '.$result['info']['from'].' - '.$result['info']['to'].' Listed');
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
	
	function remove($id){
		$this->object->id = $id;
		$this->object->remove();
	}
	
	function meta($object_id, $key = null){
		
		$this->object->id=$object_id;
		
		$key = urldecode($key);
		
		switch ($this->input->method) {
			case 'GET':
				break;
			
			case 'POST':
				$this->object->addMeta($key, $this->input->data(), $this->input->get('unique'));
				break;
			
			case 'PUT':
				$this->object->updateMeta($key, $this->input->data(), $this->input->get('prev_value') ? $this->input->get('prev_value') : null);
				break;
			
			case 'DELETE':
				$this->object->removeMeta($key, $this->input->get('value') ? $this->input->get('value') : null);
				break;
		}
		
		$this->output->set_output((object)$this->object->getMeta());
		
	}
	
	function relative($object_id, $relation = null){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				break;
			
			case 'POST':
				
				$data = $this->input->data();
				
				$num = '';
				$meta = array();
				$is_on = true;
				
				if(!is_array($data)){
					$relative = $data;
				}
				else{
					array_key_exists('relative', $data) && $relative = $data['relative'];
					array_key_exists('num', $data) && $num = $data['num'];
					array_key_exists('meta', $data) && $meta = $data['meta'];
					array_key_exists('is_on', $data) && $is_on = $data['is_on'];
				}
				
				$this->object->setRelative($relation, $relative, $num, $meta, $is_on, $this->input->get());
				break;
			
			case 'DELETE':
				$this->object->removeRelative($relation, $relative);
				break;
		}
		
		$this->output->set_output($this->object->getRelative($this->input->get()));
	}
	
	function status($object_id, $name = null){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				break;
			
			case 'POST':
				
				$data = $this->input->data();
				
				if(!is_array($data)){
					$date = $data;
					$comment = null;
				}
				else{
					$comment = array_key_exists('comment', $data) ? $data['comment'] : null;
					$date = array_key_exists('date', $data) ? $data['date'] : null;
				}
				
				$this->object->addStatus($name, $date, $comment);
				
				break;
			
			case 'PUT':
				
				$this->object->updateStatus($name, 
					$this->input->data('date') ? $this->input->data('date') : null, 
					$this->input->data('comment') ? $this->input->data('comment') : null, 
					$this->input->get('prev_date') ? $this->input->get('prev_date') : null
				);
				
				break;
			
			case 'DELETE':
				$this->object->removeStatus($name, $this->input->get('date') ? $this->input->get('date') : null);
				break;
		}
		
		$this->output->set_output($this->object->getStatus($this->input->get()));
	}
	
	function tag($object_id, $taxonomy = null){
		
		$this->object->id=$object_id;
		
		switch ($this->input->method) {
			case 'GET':
				break;
			
			case 'POST':
				$this->object->setTag($this->input->data(), $taxonomy, $this->input->get('append') ? $this->input->get('append') : false);
				break;
			
		}
		
		$this->output->set_output((object)$this->object->getTag());
	}
	
	function permission($object_id, $type = 'authorize', $name = 'read'){
		$this->object->id = $object_id;
		
		switch ($this->input->method){
			case 'GET':
				break;
			
			case 'POST':
				$this->object->authorize(array($name=>$type === 'authorize'), $this->input->data());
		}
		
		$this->output->set_output($this->object->getPermission($this->input->get()));
	}
	
}

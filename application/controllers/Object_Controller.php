<?php
class Object_Controller extends LB_Controller{
	
	function __construct() {
		parent::__construct();
	}
	
	function index($id = null){
		
		switch ($this->input->method) {
			case 'GET':
				if(is_null($id)){
					$this->query();
				}
				else{
					$this->get($id);
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
	
	function get($id){
		
		$args = $this->input->get();
		
		$object = new Object($id, $args);
		
		$this->output->set_output($object);
	}
	
	function query(){
		
		$result = $this->object->query($this->input->get());

		if(is_array($result) && array_key_exists('data', $result) && array_key_exists('info', $result)){
			$this->output->set_output($result['data']);
			$this->output->set_header('List-Total: ' . $result['info']['total']);
			$this->output->set_header('List-From: ' . $result['info']['from']);
			$this->output->set_header('List-To: ' . $result['info']['to']);
			$this->output->set_status_header(200, 'OK, '.$result['info']['total'].' Objects in Total, '.$result['info']['from'].'-'.$result['info']['to'].' Listed');
		}
		else{
			$this->output->set_output($result);
		}
		
	}
	
	function type(){
		$object = new Object();
		$types = $object->getTypes();
		$this->output->set_output($types);
	}
	
	function update($id){
		$object = new Object($id, array('get_data'=>false));
		$object->update($this->input->data());
		$this->get($object->id);
	}
	
	function add(){
		$data = $this->input->data();
		
		if(!is_array($data)){
			throw new Exception('Object data must be array.', 400);
		}
		
		$object = new Object($data);
		$this->get($object->id);
	}
	
	function remove($id){
		$object = new Object($id, array('get_data'=>false));
		$object->remove();
	}
	
	function meta($object_id, $key = null){
		
		$object = new Object($object_id, array('get_data'=>false));
		
		$key = urldecode($key);
		
		switch ($this->input->method) {
			case 'GET':
				break;
			
			case 'POST':
				$object->addMeta($key, $this->input->data(), $this->input->get('unique'));
				break;
			
			case 'PUT':
				$object->updateMeta($key, $this->input->data(), $this->input->get('prev_value') ? $this->input->get('prev_value') : null);
				break;
			
			case 'DELETE':
				$object->removeMeta($key, $this->input->get('value') ? $this->input->get('value') : null);
				break;
		}
		
		$this->output->set_output($object->getMeta());
		
	}
	
	function relative($object_id, $relation = ''){
		
		$object = new Object($object_id, array('get_data'=>false));
		
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
				
				$object->setRelative($relation, $relative, $num, $meta, $is_on, $this->input->get());
				break;
			
			case 'DELETE':
				$object->removeRelative($relation, $this->input->get('relative') ? $this->input->get('relative') : null);
				break;
		}
		
		$this->output->set_output($object->getRelative($this->input->get()));
	}
	
	function parents($object_id, $relation = ''){
		
		$object = new Object($object_id, array('get_data'=>false));
		
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
				
				$object->setParent($relation, $relative, $num, $meta, $is_on, $this->input->get());
				break;
			
			case 'DELETE':
				$object->removeParent($relation, $this->input->get('relative') ? $this->input->get('relative') : null);
				break;
		}
		
		$this->output->set_output($object->getRelative($this->input->get()));
	}
	
	function status($object_id, $name = null){
		
		$object = new Object($object_id, array('get_data'=>false));
		
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
				
				$object->addStatus($name, $date, $comment);
				
				break;
			
			case 'PUT':
				
				$data = $this->input->data();
				
				if(!is_array($data)){
					$date = $data;
					$comment = null;
				}
				else{
					$comment = array_key_exists('comment', $data) ? $data['comment'] : null;
					$date = array_key_exists('date', $data) ? $data['date'] : null;
				}
				
				$prev_date = $this->input->get('prev_date') ? $this->input->get('prev_date') : null;
				
				$object->updateStatus($name, $date, $comment, $prev_date);
				
				break;
			
			case 'DELETE':
				$object->removeStatus($name, $this->input->get('date') ? $this->input->get('date') : null);
				break;
		}
		
		$this->output->set_output($object->getStatus($this->input->get()));
	}
	
	function tag($object_id, $taxonomy = null){
		
		$object = new Object($object_id, array('get_data'=>false));
		
		switch ($this->input->method) {
			case 'GET':
				break;
			
			case 'POST':
				$object->setTag($this->input->data(), $taxonomy, $this->input->get('append') ? $this->input->get('append') : false);
				break;
			
		}
		
		$this->output->set_output($object->getTag());
	}
	
	function permission($object_id, $type = 'authorize', $name = 'read'){
		
		$object = new Object($object_id, array('get_data'=>false));
		
		switch ($this->input->method){
			case 'GET':
				break;
			
			case 'POST':
				$object->authorize(array($name=>$type === 'authorize'), $this->input->data());
		}
		
		$this->output->set_output($object->getPermission($this->input->get()));
	}
	
}

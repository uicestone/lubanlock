<?php
class Meta extends LB_Controller{
	function __construct() {
		parent::__construct();
	}
	
	function keys($type = null){
		
		if($this->input->method === 'GET'){
			$object = new Object();
			$keys = $object->getMetaKeys(array_merge(is_null($type) ? array() : array('type'=>$type), $this->input->get()));
			$this->output->set_output($keys);
		}
	}
	
}


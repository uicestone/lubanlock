<?php

class Message extends LB_Controller {
	
	function __construct() {
		parent::__construct();
	}
	
	function index() {
		switch($this->input->method){
			case 'POST':
				$this->send();
				break;
		}
	}
	
	function send(){
		
		$data = $this->input->data();
		
		$dialogs = array();
		
		$dialogs[] = new Object(array(
			'type'=>'对话',
			'user'=>$this->session->user_id,
			'name'=>$data['title']
		));
		
		$dialogs[0]->update(array('num'=>$dialogs[0]->id));
		
		foreach($data['receivers'] as $receiver_id){
			$dialogs[] = new Object(array(
				'type'=>'对话',
				'num'=>$dialogs[0]->id,
				'user'=>$receiver_id,
				'name'=>$data['title']
			));
		}
		
		$message = new Object(array(
			'type'=>'消息',
			'name'=>str_get_summary($data['content']),
			'meta'=>array(
				'content'=>$data['content']
			),
			'relative'=>array(
				'attachment'=>$data['attachments']
			),
			'parents'=>array(
				'message'=>array_map(function($item){
					return $item->id;
				}, $dialogs)
			)
		));
		
		$this->output->set_output($message);
		
	}
	
}

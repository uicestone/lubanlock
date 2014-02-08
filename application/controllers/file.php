<?php

class File extends LB_Controller{
	function __construct() {
		parent::__construct();
	}
	
	function upload(){
		$config=array(
			'upload_path'=>'../uploads/',
			'allowed_types'=>'*',
			'encrypt_name'=>true
		);

		$this->load->library('upload', $config);

		if (!$this->upload->do_upload('file')) {
			throw new Exception($this->upload->display_errors(), 500);
		}

		$file_info = $this->upload->data();

		$file_info['mail_name']=substr($file_info['client_name'], 0, -strlen($file_info['file_ext']));

		$this->object->add(array(
			'type'=>'file',
			'name'=>$file_info['mail_name'],
			'meta'=>$file_info
		));
		
	}
}

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

		$file_id = $this->object->add(array(
			'type'=>'file',
			'name'=>$file_info['mail_name'],
			'meta'=>$file_info
		));
		
		$this->output->set_output($this->object->fetch($file_id));
		
	}
	
	function download($id){
		
		$file = $this->object->fetch($id);
		
		$path = end($file['meta']['full_path']);
		
		header('Content-Type: ' . end($file['meta']['file_type']));
		header('Content-Length: ' . end($file['meta']['file_size']) * 1024);
		header('Content-Disposition: attachment; filename="'.end($file['meta']['orig_name']).'"');
		header('Expires: 0');
		
		
		readfile($path);

	}
}

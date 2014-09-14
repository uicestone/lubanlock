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
		
		$file_meta = array();
		foreach($file_info as $file_info_key => $file_info_value){
			$file_meta[] = array('key'=>$file_info_key, 'value'=>$file_info_value, 'visibility'=>0);
		}
		
		$object = new Object(array(
			'type'=>'file',
			'name'=>$file_info['mail_name'],
			'meta'=>$file_info
		));
		
		if(!$this->input->accept('application/json')){
			show_error('文件已经上传，但由于你的浏览器太旧，无法为你正常跳转，请手动<a href="javascript:history.back();">返回</a>', 400);
		}else{
			$this->output->set_output($this->object->get($object->id));
		}
		
	}
	
	function download($id){
		
		$file = new Object($id, array('with_meta'=>array('visibility'=>'>0')));
		
		$path = get_meta($file, 'full_path');
		
		header('Content-Type: ' . get_meta($file, 'file_type'));
		header('Content-Length: ' . round(get_meta($file, 'file_size') * 1024));
		header('Content-Disposition: attachment; filename="' . get_meta($file, 'orig_name') . '"');
		header('Expires: 0');
		
		readfile($path);

	}
}

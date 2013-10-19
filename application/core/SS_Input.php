<?php
class SS_input extends CI_Input{
	
	var $method;
	
	function __construct(){
		parent::__construct();
		$this->method=$this->server('REQUEST_METHOD');
	}
	
	/**
	 * 继承post方法，处理post数组
	 * 现可如下访问：
	 * $this->input->post('submit/newcase')
	 */
	function post($index = NULL, $xss_clean = FALSE){

		if(is_null($index)){
			return parent::post($index, $xss_clean);
		
		}else{
			
			if(parent::post($index, $xss_clean)!==false){
				return parent::post($index, $xss_clean);
			}
			
			$index_array=explode('/',$index);
			
			$post=parent::post($index_array[0], $xss_clean);
			
			for($i=1;$i<count($index_array);$i++){
				if(isset($post[$index_array[$i]])){
					$post=$post[$index_array[$i]];
				}else{
					return false;
				}
				
			}
			
			return $post;
		}
	}
	
	function put(){
		
		if($this->server('REQUEST_METHOD')!=='PUT'){
			return false;
		}
		
		$data=file_get_contents('php://input');
		
		$headers=$this->request_headers();

		if(array_key_exists('Content-type', $headers)){
			if(
				strpos($headers['Content-type'],'application/x-www-form-urlencoded')===0
				|| strpos($headers['Content-type'],'multipart/form-data')===0){
				parse_str($data,$data);
			}

			if($headers['Content-type']==='application/json'){
				$data=json_decode($data,JSON_OBJECT_AS_ARRAY);
			}
		}

		return $data;

	}
	
	function delete(){
		
		if($this->server('REQUEST_METHOD')==='DELETE'){
			return true;
		}
		
		return false;
	}
	
	function _clean_input_keys($str){   
		$config = &get_config('config');   
		if( ! preg_match("/^[".$config['permitted_uri_chars']."]+$/i", rawurlencode($str))){   
		   exit('Disallowed Key Characters.');   
		}   
		return $str;   
	}
	
}
?>
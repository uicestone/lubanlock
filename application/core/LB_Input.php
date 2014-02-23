<?php
class LB_input extends CI_Input{
	
	var $method;
	
	function __construct(){
		parent::__construct();
		$this->method=$this->server('REQUEST_METHOD');
	}
	
	/**
	 * return the parsed request body, or a key value of it
	 */
	function data($index = NULL){
		$data=file_get_contents('php://input');
		
		$headers=$this->request_headers();

		//parse as form data
		if(array_key_exists('Content-type', $headers) && (
			strpos($headers['Content-type'],'application/x-www-form-urlencoded')===0
			|| strpos($headers['Content-type'],'multipart/form-data')===0)
		){
			parse_str($data,$data);
		}
		//parse as json
		elseif((array_key_exists('Content-type', $headers) && $headers['Content-type']==='application/json') || json_decode($data)){
			$data=json_decode($data,JSON_OBJECT_AS_ARRAY);
		}

		if(!is_null($index)){
			
			if(array_key_exists($index, $data)){
				return $data[$index];
			}
			
			return false;
		}

		return $data;
	}
	
	/**
	 * parse query string and json in query string
	 */
	function get($index = NULL, $xss_clean = FALSE){
		
		$get_temp = $_GET;
		unset($_GET['query']);
		$get = parent::get($index, $xss_clean);
		
		if($get === FALSE && isset($get_temp['query'])){
			$_GET = json_decode($get_temp['query'],JSON_OBJECT_AS_ARRAY);
			$get = parent::get($index, $xss_clean);
		}
		
		$_GET = $get_temp;
		
		if(is_null($index) && $get===false){
			$get=array();
		}
		
		return $get;
	}
	
	function accept($content_type){
		$accepts = explode(',', $this->get_request_header('Accept'));
		
		if($accepts && in_array($content_type, $accepts)){
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
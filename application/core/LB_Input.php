<?php
class LB_input extends CI_Input{
	
	var $method;
	var $data;
	
	function __construct(){
		parent::__construct();
		$this->method=$this->server('REQUEST_METHOD');
		$this->data = file_get_contents('php://input');
	}
	
	/**
	 * return the parsed request body, or a key value of it
	 */
	function data($index = NULL){
		
		$data = $this->data;
		
		$headers=$this->request_headers();

		//parse as form data
		if(array_key_exists('Content-Type', $headers) && (
			strpos($headers['Content-Type'],'application/x-www-form-urlencoded') === 0
			|| strpos($headers['Content-Type'],'multipart/form-data') === 0)
		){
			parse_str($data,$data);
		}
		//parse as json
		elseif((array_key_exists('Content-Type', $headers) && $headers['Content-Type']==='application/json') || !is_null(json_decode($data))){
			
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
		
		if(is_array($get)){
			array_walk($get, function(&$value){
				$decoded = json_decode($value, JSON_OBJECT_AS_ARRAY);
				!is_null($decoded) && $value = $decoded;
			});
		}
		else{
			$decoded = json_decode($get, JSON_OBJECT_AS_ARRAY);
			!is_null($decoded) && $get = $decoded;
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
	
}
?>
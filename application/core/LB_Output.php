<?php

class LB_Output extends CI_Output {
	function __construct() {
		parent::__construct();
		$this->final_output = '';
	}
	
	function set_status_header($code = 200, $text = 'OK') {
		return parent::set_status_header($code, str_replace('"', '', json_encode($text)));
	}
}
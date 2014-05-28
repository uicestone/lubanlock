<?php

class LB_Output extends CI_Output {
	function __construct() {
		parent::__construct();
		$this->final_output = '';
	}
	
	function set_status_header($code = 200, $text = '') {
		$this->set_header('Status-Text: '.json_encode($text));
		parent::set_status_header($code, $text);
	}
}
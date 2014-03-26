<?php

class LB_Output extends CI_Output {
	function __construct() {
		parent::__construct();
	}
	
	function set_status_header($code = 200, $text = '') {
		$this->set_header('Status-Text: '.$text);
		parent::set_status_header($code, $text);
	}
}
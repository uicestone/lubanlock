<?php
class Test extends SS_Controller{
	function __construct() {
		parent::__construct();
		$this->load->library('unit_test');
	}
	
	function index(){
		
	}
	
	function session(){
		print_r($this->session->all_userdata());
	}
}
?>

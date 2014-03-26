<?php
class Test extends LB_Controller{
	function __construct() {
		parent::__construct();
		$this->load->library('unit_test');
	}
	
	function index(){
		
	}
	
	function session(){
		print_r($this->session->all_userdata());
		print_r($this->user);
	}
}
?>

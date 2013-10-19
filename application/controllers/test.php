<?php
class Test extends SS_Controller{
	function __construct() {
		parent::__construct();
	}
	
	function session(){
		print_r($this->session->all_userdata());
	}
}
?>

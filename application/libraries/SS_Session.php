<?php
class SS_Session extends CI_Session{
	
	var $db;
	
	function __construct($params = array()) {
		parent::__construct($params);
		$CI=&get_instance();
		$this->db=$CI->db;
	}
}

?>

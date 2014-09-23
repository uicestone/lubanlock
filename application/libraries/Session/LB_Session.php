<?php
/**
 * Extend the default Session driver
 * just to add a few properties.
 */
class LB_Session extends CI_Session {
	
	var $user, $user_id, $user_name, $user_object_name, $user_roles = array(), $group_ids = array(), $groups = array(), $company_id;
	
	function __construct($params = array()) {
		parent::__construct($params);
	}
	
	function sess_destroy() {
		$this->user = $this->user_id = $this->company_id = null;
		$this->user_roles = $this->groups = $this->group_ids = array();
		return parent::sess_destroy();
	}
	
}

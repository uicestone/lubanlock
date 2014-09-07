<?php
/**
 * Extend the default Session driver
 * just to add a few properties.
 */
class LB_Session extends CI_Session {
	
	var $user, $user_id, $user_roles = array(), $group_ids = array(), $groups = array(), $company_id;
	
	function __construct($params = array()) {
		parent::__construct($params);
	}
}

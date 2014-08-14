<?php
/**
 * Extend the default Session driver
 * just to add a few properties.
 */
class LB_Session extends CI_Session {
	
	var $user_id, $user_roles, $group_ids, $groups;
	
	function __construct($params = array()) {
		parent::__construct($params);
	}
}

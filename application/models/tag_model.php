<?php
class Tag_model extends CI_Model{
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * 
	 * @param int|string $name
	 * @param string $taxonomy
	 * @param array $args
	 *	alias_of
	 *	description
	 *	parent
	 *	slug
	 */
	function add($name, $taxonomy, array $args = array()){
		
	}
	
	function update($id, $taxonomy, array $args = array()){
		
	}
	
	function remove($id, $taxonomy, array $args = array()){
		
	}
	
}

?>

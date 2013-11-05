<?php
class Nav_model extends CI_Model{
	
	static $fields;
	
	function __construct() {
		parent::__construct();
		
		self::$fields=array(
			'user' => $this->user->id,
			'name' => '',
			'href' => '',
			'parent' => NULL,
			'order' => 0
		);
		
	}
	
	
	function add(array $data){
		
		$this->db->duplicate_insert('nav',
			array_merge(
				self::$fields,
				array_intersect_key($data, self::$fields)
			)
		);
		
		return $this->db->insert_id();
	}
	
	function update(array $data, $id){
	
		return $this->db->update('nav', array_intersect_key($data, self::$fields), array('id'=>$id));
	}
	
	function get(){
		
		$result = $this->db->from('nav')
			->where_in('user', $this->user->groups)
			->get()->result_array();
		
		$nav_items=array();
		
		foreach($result as $nav_item){
			$nav_items[$nav_item['id']] = $nav_item;
		}
		
		foreach($nav_items as $id => $nav_item){
			if(!is_null($nav_item['parent'])){
				$nav_items[$nav_item['parent']]['sub'][]=$nav_item;
				unset($nav_items[$id]);
			}
		}
		
		return array_values($nav_items);
		
	}
	
}

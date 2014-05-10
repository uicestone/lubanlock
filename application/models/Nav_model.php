<?php
class Nav_model extends CI_Model{
	
	static $fields;
	
	function __construct() {
		parent::__construct();
		
		self::$fields=array(
			'user' => $this->user->session_id,
			'name' => '',
			'template'=>'',
			'params' => '',
			'parent' => NULL,
			'order' => 0
		);
		
	}
	
	function add(array $data){
		
		if(array_key_exists('params', $data)){
			$data['params'] = json_encode($data['params']);
		}
		
		$this->db->upsert('nav',
			array_merge(
				self::$fields,
				array_intersect_key($data, self::$fields)
			)
		);
		
		return $this->db->insert_id();
	}
	
	function update(array $data, $id){
	
		if(array_key_exists('params', $data)){
			$data['params'] = json_encode($data['params']);
		}
		
		return $this->db->update('nav', array_intersect_key($data, self::$fields), array('id'=>$id));
	}
	
	function get(){
		
		$result = $this->db->from('nav')
			->where_in('user', $this->user->group_ids)
			->or_where('user', null)
			->get()->result_array();
		
		$nav_items=array();
		
		foreach($result as $nav_item){
			$nav_item['params'] = json_decode($nav_item['params'], JSON_OBJECT_AS_ARRAY);
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
	
	function remove($id){
		return $this->db->delete('nav', array('id'=>$id));
	}
	
}

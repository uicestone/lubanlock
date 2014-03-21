<?php
class Tag_model extends CI_Model{
	function __construct() {
		parent::__construct();
	}
	
	/**
	 * 获得分类的详细信息，不存在则先添加
	 * @param string $tag_name
	 * @param string $taxonomy
	 * @param array $args
	 *	description
	 *	parent
	 * @return array
	 */
	function get($tag_name, $taxonomy, array $args = array()){
		
		$tag_id = $this->getTagID($tag_name);
		
		$tag_taxonomy = $this->db->from('tag_taxonomy')->where(array('tag'=>$tag_id, 'taxonomy'=>$taxonomy))->get()->row();
		
		if(is_null($tag_taxonomy)){
			return $this->add($tag_name, $taxonomy, $args);
		}
		
		return $tag_taxonomy->id;
		
	}
	
	/**
	 * 添加一个分类
	 * @param int|string $tag_name
	 * @param string $taxonomy
	 * @param array $args
	 *	description
	 *	parent
	 */
	function add($tag_name, $taxonomy, array $args = array()){
		
		$tag_id = $this->getTagID($tag_name);
		
		$this->db->insert('tag_taxonomy',
			array_merge(
				array('tag'=>$tag_id, 'taxonomy'=>$taxonomy),
				array_intersect_key($args, array('description'=>'', 'parent'=>0))
			)
		);
		
		return $this->db->insert_id();
	}
	
	/**
	 * 更新一个分类
	 * @param int $tag_id
	 * @param string $taxonomy
	 * @param array $args
	 *	description
	 *	parent
	 */
	function update($tag_id, $taxonomy, array $args = array()){
		return $this->db->update('tag_taxonomy',
			array_merge(
				array('tag'=>$tag_id, 'taxonomy'=>$taxonomy),
				array_intersect_key($args, array('description'=>'', 'parent'=>0))
			),
			array('tag'=>$tag_id, 'taxonomy'=>$taxonomy)
		);
	}
	
	/**
	 * 删除一个分类
	 * @param int $id
	 * @param string $taxonomy
	 * @param array $args
	 */
	function remove($tag_id, $taxonomy){
		return $this->db->delete('tag_taxonomy', array('tag'=>$tag_id, 'taxonomy'=>$taxonomy));
	}
	
	/**
	 * 根据名称返回tag id，不存在则先添加
	 * @param string $tag_name
	 */
	function getTagID($tag_name){
		
		$tag = $this->db->from('tag')->where('name', $tag_name)->get()->row();
		
		if(is_null($tag)){
			$this->db->insert('tag', array('name'=>$tag_name));
			return $this->db->insert_id();
		}
		
		return $tag->id;
		
	}
	
}

?>

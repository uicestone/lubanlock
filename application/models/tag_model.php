<?php
class Tag_model extends CI_Model{
	function __construct() {
		parent::__construct();
	}
	
	function fetch($id){
		$this->db->from('tag')
			->where('id',$id);
		
		return $this->db->get()->row();
	}
	
	/**
	 * 测试一个标签名
	 * 如果存在则返回id
	 * 如果不存在则添加后返回id
	 * @param string $name
	 */
	function match($name){
		
		$name=urldecode($name);
		
		$row=$this->db->get_where('tag', array('name'=>$name))->row();
		
		if($row){
			return $row->id;
		}
		else{
			$this->db->insert('tag',array('name'=>$name));
			return $this->db->insert_id();
		}
	}
	
	function getList(array $args=array()){
		$args['company']=$args['display']=false;
		return parent::getList($args);
	}
	
	/**
	 * 接受一个tag name，返回与其相关的tag的id和name构成的数组
	 * @param type $tag
	 * @param type $relation
	 */
	function getRelatives($tag,$relation=NULL){
		
		$this->db->select('relative.id,relative.name')
			->from('tag_relationship')
			->join('tag','tag.id=tag_relationship.tag','inner')
			->join('tag relative','relative.id=tag_relationship.relative','inner')
			->or_where(array('tag.name'=>$tag,'tag.id'=>$tag));

		return array_column($this->db->get()->result_array(),'name','id');
	}
}

?>

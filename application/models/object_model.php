<?php
class Object_model extends CI_Model{
	
	var $id;
//	var $name;
//	var $type;
//	var $num;
//	var $display;
//	var $company;
//	var $uid;
//	var $time;
//	var $time_insert;
	var $data;//具体对象数据
	var $meta;//具体对象的元数据
	var $mod=false;
	var $relative;
	var $relative_mod_list=array(//相关对象开关参数意义
		1=>'read',
		2=>'write',
		4=>'distribute'
	);
	var $status;
	var $tags;//具体对象的标签
	
	var $table='object';//具体对象存放于数据库的表名
	
	static $fields;//存放对象的表结构
	static $fields_meta;
	static $fields_relationship;
	static $fields_status;
	static $fields_tag;
	

	function __construct() {
		parent::__construct();
		
		$CI=&get_instance();
		
		self::$fields=array(
			'name'=>NULL,
			'type'=>'',
			'num'=>NULL,
			'display'=>true,
			'company'=>isset($CI->company)?$CI->company->id:NULL,
			'uid'=>isset($CI->user)?$CI->user->id:NULL,
			'time_insert'=>time(),
			'time'=>time()
		);
		
		self::$fields_meta=array(
			'object'=>NULL,
			'key'=>'',
			'value'=>NULL,
			'comment'=>NULL,
			'uid'=>isset($CI->user)?$CI->user->id:NULL,
			'time'=>time()
		);
		
		self::$fields_relationship=array(
			'object'=>NULL,
			'relative'=>NULL,
			'relation'=>NULL,
			'mod'=>0,
			'weight'=>NULL,
			'num'=>NULL,
			'uid'=>isset($CI->user)?$CI->user->id:NULL,
			'time'=>time()
		);
		
		self::$fields_status=array(
			'object'=>NULL,
			'name'=>'',
			'type'=>NULL,
			'date'=>date('Y-m-d H:i:s',time()),
			'content'=>NULL,
			'comment'=>NULL,
			'group'=>NULL,
			'uid'=>isset($CI->user)?$CI->user->id:NULL,
			'time'=>time()
		);
		
		self::$fields_tag=array(
			'object'=>NULL,
			'tag'=>NULL,
			'tag_name'=>'',
			'type'=>NULL,
			'uid'=>isset($CI->user)?$CI->user->id:NULL,
			'time'=>time()
		);
		
	}
	
	/**
	 * 
	 * @throws Exception 'not_found'
	 */
	function fetch($id=NULL, array $args=array()){
		
		if(is_null($id)){
			$id=$this->id;
		}else{
			$this->id=$id;
		}
		
		$this->db
			->from('object')
			->where(array(
				'object.id'=>$this->id,
				'object.company'=>$this->company->id,
				'object.display'=>true
			));
		
		if($this->table!=='object'){
			$this->db->join($this->table,"object.id = {$this->table}.id",'inner');
		}

		//验证读权限
		if($this->mod && !$this->user->isLogged($this->table.'admin')){
			//TODO
		}

		$object=$this->db->get()->row_array();
		
		if(!$object){
			throw new Exception(lang($this->table).' '.$this->id.' '.lang('not_found'), 404);
		}
		
		foreach(array('meta','relative','status','tag') as $field){
			if(!array_key_exists('with_'.$field,$args) || $args['with_'.$field]){
				$object[$field]=call_user_func(array($this,'get'.$field));
			}
		}
		
		return $object;

	}
	
	function add(array $data){
		
		$data+=array(
			'company'=>$this->company->id,
			'user'=>$this->user->id,
			'time'=>time(),
			'time_insert'=>time()
		);
		
		$this->db->insert('object',array_merge(self::$fields,array_intersect_key($data,self::$fields)));
		
		$this->id=$this->db->insert_id();
		
		$this->addRelative(array(
			'relative'=>$this->user->id,
			'relation'=>lang('author'),
			'mod'=>7
		));

		if(isset($data['meta'])){
			$this->addMetas($data['meta']);
		}
		
		if(isset($data['mod'])){
			$this->updateMods($data['mod']);
		}
		
		if(isset($data['relative'])){
			$this->addRelatives($data['relative']);
		}
		
		if(isset($data['status'])){
			$this->addStatuses($data['status']);
		}
		
		if(isset($data['tag'])){
			$this->addTags($data['tag']);
		}
		
		return $this->id;
	}
	
	function update(array $data, $condition=NULL){

		$data=array_intersect_key($data, self::$fields);
		
		if(empty($data)){
			return false;
		}
		
		if(isset($condition)){
			$this->db->where($condition);
		}else{
			$this->db->where('id',$this->id);
		}
		
		$this->db->set($data)->update('object');
		
		return $this->db->affected_rows();
	}
	
	function remove($condition=NULL){

		$this->db->start_cache();
		
		if(isset($condition)){
			$this->db->where($condition);
		}else{
			$this->db->where('id',$this->id);
		}
		
		$this->db->stop_cache();
		
		if($this->table!=='object'){
			$this->db->delete($this->table);
		}
		
		$this->db->delete('object');
		
		$this->db->flush_cache();
		
	}
	
	/**
	 * 根据部分名称返回匹配的id、名称和类别列表
	 * @param $part_of_name
	 * @return array
	 */
	function match($part_of_name){
		
		$this->db
			->from('object')
			->where('object.company',$this->company->id)
			->like('object.name', $part_of_name);
		
		if($this->table!=='object'){
			$this->db->join($this->table,"object.id = {$this->table}.id",'inner');
		}
		
		return $this->db->get()->result_array();
	}
	
	/**
	 * 根据部分名称，返回唯一的id
	 * @param type $part_of_name
	 * @return type
	 * @throws Exception 'not_found','duplicated_matches'
	 */
	function check($part_of_name){
		$result=$this->db
			->from('object')
			->where('object.company',$this->company->id)
			->where('object.display',true)
			->like('name',$part_of_name)
			->get();

		if($result->num_rows()>1){
			throw new Exception(lang('duplicated_matches').' '.$part_of_name);
		}
		elseif($result->num_rows===0){
			throw new Exception($part_of_name.' '.lang('not_found'));
		}
		else{
			return $result->row()->id;
		}
	}
	
	function _parse_criteria($args, $field='`object`.`id`', $logical_operator = 'AND'){

		if(empty($args)){
			return 'TRUE';
		}
		
		if(!is_array($args)){
			return $field.' = '.$this->db->escape($args);
		}
		
		//如果参数数组全是数字键，则作in处理
		if(array_reduce(array_keys($args), function($result, $item){
			return $result && is_integer($item);
		}, true)){
			$args = array('in'=>$args);
		}
		
		$where = array();
		
		foreach($args as $arg_name => $arg_value){
			
			if($arg_name === 'or'){
				return $this->_parse_criteria($arg_value, $field, 'OR');
			}
			
			if($arg_name === 'gt'){
				$where[] = $field.' > '.$this->db->escape($arg_value);
			}
			elseif($arg_name === 'gte'){
				$where[] = $field.' >= '.$this->db->escape($arg_value);
			}
			elseif($arg_name === 'ne'){
				$where[] = $field.' != '.$this->db->escape($arg_value);
			}
			elseif($arg_name === 'lt'){
				$where[] = $field.' < '.$this->db->escape($arg_value);
			}
			elseif($arg_name === 'lte'){
				$where[] = $field.' <= '.$this->db->escape($arg_value);
			}
			elseif($arg_name === 'in'){
				$where[] = $field." IN ( \n".implode(', ', array_map(array($this->db, 'escape'), $arg_value)).' )';
			}
			elseif($arg_name === 'nin'){
				$where[] = $field." NOT IN ( \n".implode(', ', array_map(array($this->db, 'escape'), $arg_value)).' )';
			}
			
			elseif($arg_name === 'meta'){
				foreach($arg_value as $key => $value){
					$where[] = "$field IN ( \nSELECT `object` FROM `object_meta` WHERE `key` = {$this->db->escape($key)} AND {$this->_parse_criteria($value, '`value`')} )";
				}
			}
			elseif($arg_name === 'status'){
				foreach($arg_value as $name => $date){
					$where[] = "$field IN ( \nSELECT `object` FROM `object_status` WHERE `name` = {$this->db->escape($name)} AND {$this->_parse_criteria($date, '`value`')} )";
				}
			}
			elseif($arg_name === 'tag'){
				foreach($arg_value as $taxonomy => $tag){
					$taxonomy_criteria = is_integer($taxonomy) ? ' ' : " AND `taxonomy` = {$this->db->escape($taxonomy)}";
					$where[] = "$field IN ( \nSELECT `object` FROM `object_tag` WHERE `tag_taxonomy` IN ( \nSELECT `id` FROM `tag_taxonomy` WHERE `tag` = ( \nSELECT `id` FROM `tag` WHERE  {$this->_parse_criteria($tag, '`name`')} \n)$taxonomy_criteria\n) \n)";
				}
			}
			
			elseif($arg_name === 'is_relative_of'){
				foreach($arg_value as $relation => $relative_args){
					$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($relation);
					$where[] = "$field IN ( \nSELECT `relative` FROM `object_relationship` WHERE $relation_criteria AND ".$this->_parse_criteria($relative_args, '`object_relationship`.`object`')." )";
				}
			}
			elseif($arg_name === 'has_relative_like'){
				foreach($arg_value as $relation => $relative_args){
					$where[] = "$field IN ( \nSELECT `object` FROM `object_relationship` WHERE ".$this->_parse_criteria($relative_args, '`object.relationship`.`relative`')." )";
				}
			}
			
			elseif(in_array($arg_name,array('name','type'))){
				$where[] = $this->_parse_criteria($arg_value, '`object`.'.$arg_name);
			}
			
		}

		return "( \n".implode("\n$logical_operator\n", $where)." \n)";

	}
	
	/**
	 * 
	 * @param array $args
	 *	recursive args
	 *		name
	 *			recursive args
	 *		type
	 *			recursive args
	 *		meta
	 *			[key => ]value
	 *				recursive args
	 *		status
	 *			name
	 *			name => date
	 *				recursive args
	 *		tag
	 *			[taxonomy => ]tag
	 *				recursive args
	 * 
	 *		is_relative_of
	 *			[role => ]recursive args
	 *		has_relative_like
	 *			[role => ]recursive args
	 * 
	 *		and, or
	 *			recursive args
	 * 
	 *		gt, gte, lt, lte, ne
	 *			value
	 *		in, nin
	 *			array of value
	 * 
	 *	static args
	 *		orderby string or array
	 *		limit string, array
	 *		page int
	 *		perpage int
	 * @return array
	 */
	function getList(array $args=array()){

		$this->db->found_rows();
		
		$this->db->from('object');
		
		$this->db->where('object.company', $this->company->id);
		
		$this->db->where($this->_parse_criteria($args), null, false);
		
		if(!array_key_exists('order_by', $args)){
			$args['order_by'] = 'object.id desc';
		}
		
		if(array_key_exists('order_by',$args) && $args['order_by']){
			if(is_array($args['order_by'])){
				foreach($args['order_by'] as $orderby){
					$this->db->order_by($orderby[0],$orderby[1]);
				}
			}else{
				$this->db->order_by($args['order_by']);
			}
		}
		
		//使用两种方式来对列表分页
		if(array_key_exists('per_page',$args) && array_key_exists('page', $args)){
			//页码-每页数量方式，转换为sql limit
			$args['limit']=array($args['per_page'],($args['per_page']-1)*$args['page']);
		}
		
		if(!array_key_exists('limit', $args)){
			//默认limit
			$args['limit']=25;//$this->config->user_item('per_page');
		}
		
		if(is_array($args['limit'])){
			//sql limit方式
			call_user_func_array(array($this->db,'limit'), $args['limit']);
		}
		elseif(count(preg_split('/,\s*/',$args['limit'])) === 2){
			$args['limit'] = preg_split('/,\s*/',$args['limit']);
			call_user_func_array(array($this->db,'limit'), $args['limit']);
		}
		else{
			call_user_func(array($this->db,'limit'), $args['limit']);
		}
		
		$result_array=$this->db->get()->result_array();
		
		$result = array();
		$result['total'] = $this->db->query('SELECT FOUND_ROWS() rows')->row()->rows;
		
		foreach(array('meta','mod','relative','status','tag') as $field){
			if(array_key_exists('with_'.$field,$args) && $args['with_'.$field]){
				array_walk($result_array,function(&$row, $index, $field){
					$this->id = $row['id'];
					$row[$field]=call_user_func(array($this,'get'.$field));
				},$field);
			}
		}

		$result['data'] = $result_array;
		
		return $result;
	}
	
	
	function getArray(array $args=array(),$keyname='name',$keyname_forkey='id'){
		return array_column($this->getList($args),$keyname,$keyname_forkey);
	}
	
	function getRow(array $args=array()){
		!array_key_exists('limit',$args) && $args['limit']=1;
		$result=$this->getList($args);
		if(isset($result[0])){
			return $result[0];
		}else{
			return array();
		}
	}
	
	/**
	 * 返回一个对象的资料项列表
	 * @return array
	 */
	function getMeta(array $args = array()){
		
		$this->db->select('object_meta.*')
			->from('object_meta')
			->where("object_meta.object",$this->id);
		
		if(array_key_exists('id', $args)){
			$this->db->where('object_meta.id',$args['id']);
			return $this->db->get()->row_array();
		}
		
		return $this->db->get()->result_array();
		
	}
	
	/**
	 * 给当前对象添加一个资料项
	 * @return Object_model
	 */
	function addMeta(array $data){
		
		$data['object']=$this->id;
		
		$data=array_merge(
			self::$fields_meta,
			array_intersect_key($data, self::$fields_meta)
		);
		
		$this->db->insert('object_meta',$data);
		
		return $this->db->insert_id();
	}
	
	function addMetas(array $data){
		
		foreach($data as $row){
			$this->addMeta($row);
		}
		
		return $this;
	}
	
	/**
	 * 更新对象的单条meta，须已知object_meta.id
	 * @param array $data
	 * @return Object_model
	 */
	function updateMeta($data, array $args = array()){
		
		$this->db->update('object_meta',array_merge(
			array('uid'=>$this->user->id,'time'=>time()),
			array_intersect_key($data, self::$fields_meta)
		),$args?$args:array('id'=>$data['id']));
		
		return $this;
	}
	
	/**
	 * 为指定对象写入一组资料项
	 * 遇不存在的meta name则插入，遇存在的meta name则更新
	 * 虽然一个对象可以容纳多个相同meta name的content
	 * 但使用此方法并遇到存在的meta name时进行更新操作
	 * @param array $meta: array($name=>$content,...)
	 */
	function updateMetas(array $data){
		
		foreach($data as $row){
			$this->updateMeta($row);
		}
	}
	
	/**
	 * 删除对象元数据
	 * @param int $meta_id
	 * @return Object_model
	 */
	function removeMeta(array $args = array()){
		$this->db->delete('object_meta',array('id'=>$args['id']));
		return $this;
	}
	
	/**
	 * add a group of mods to an object
	 * @param type $mods_array
	 */
	function updateMods($mods_array){
		foreach($mods_array as $mods){
			foreach($mods as $mod_name => $mod_on){
				if(!in_array($mod_name, array('id','user','username'))){
					if($mod_on){
						$this->addMod($mod_name, $mods['user']);
					}
					else{
						$this->removeMod($mod_name, $mods['user']);
					}
				}
			}
		}
		return $this;
	}
	
	function getRelative(array $args = array()){
		
		$this->db->select('object.*, object_relationship.*')
			->from('object_relationship')
			->join('object','object.id = object_relationship.relative','inner')
			->where('object_relationship.object',$this->id);
		
		if(array_key_exists('relation', $args)){
			$this->db->where('object_relationship.relation',$args['relation']);
		}
		
		if(array_key_exists('mods', $args)){
			$positive=$negative=0;
			foreach($args['mod_set'] as $relative_type => $mods){
				
				if(!array_key_exists($relative_type, $this->relative_mod_list)){
					log_message('error','relation type not found: '.$relative_type);
					continue;
				}
				
				foreach($mods as $mod_name => $status){
					
					if(!array_key_exists($mod_name, $this->relative_mod_list[$relative_type])){
						log_message('error','mod name not found: '.$mod_name);
						continue;
					}
					
					$mod=$this->relative_mod_list[$relative_type][$mod_name];
					$status?($positive|=$mod):($negative|=$mod);
				}
				
			}
			
			$this->db->where("(object_relationship.mod & $positive) = $positive AND (object_relationship.mod & $negative) = 0",NULL,false);
			
		}
		
		if(array_key_exists('id', $args)){
			$this->db->where('object_relationship.id',$args['id']);
			return $this->db->get()->row_array();
		}
		
		return $this->db->get()->result_array();
		
	}
	
	function addRelative(array $data){
		
		$data['object']=$this->id;

		$this->db->insert('object_relationship',array_merge(
			self::$fields_relationship,
			array_intersect_key($data, self::$fields_relationship)
		));
		
		return $this->db->insert_id();
	}
	
	function addRelatives(array $data){
		
		foreach($data as $row){
			$this->addRelative($row);
		}
		
		return $this;
	}
	
	function updateRelative(array $data, array $args=array()){
		
		$this->db->update('object_relationship',array_merge(
				array('uid'=>$this->user->id,'time'=>time()),
				array_intersect_key($data, self::$fields_relationship)
			),$args?$args:array('id'=>$data['id']));
		
		return $this;
	}
	
	function updateRelatives(array $data){
		
		foreach($data as $row){
			$this->updateRelative($row);
		}
		
		return $this;
	}
	
	function removeRelative(array $args = array()){
		
		$this->db->where('id',$args['id'])->delete('object_relationship');
		return $this;
	}
	
	function getRelativeMod(array $args = array()){
		
		$this->db->select_sum('mod')
			->from('object_relationship')
			->where('object',$this->id);
		
		if(array_key_exists('id', $args)){
			$this->db->where('object_relationship.id',$args['id']);
		}
		
		if(array_key_exists('relative', $args)){
			$this->db->where('object_relationship.id',$args['relative']);
		}
		
		$mod = $this->db->get()->row()->mod;
		
		$mod_names = array();
		
		foreach($this->relative_mod_list as $mod_value => $mod_name){
			if($mod & $mod_value === $mod_value){
				$mod_names[]=$mod_name;
			}
		}
		
		return $mod_names;
	}
	
	/**
	 * 给当前对象与一个相关对象的关系设定一个开关参数
	 * @param int $relationship_id
	 * @param string $mod_name
	 * @param string $relative_type
	 * @return boolean | Object_model
	 */
	function addRelativeMod($name, array $args){
		
		foreach($this->relative_mod_list as $mod_value => $mod_name){
			if($name === $mod_name){
				$mod = $mod_value;
				break;
			}
		}
		
		if(empty($mod)){
			throw new Exception('mod_name_not_found', 404);
		}
		
		//已知关系id，那么更新之
		if(array_key_exists('id', $args)){
			$this->db->where('id',$args['id'])
				->set('mod',"`mod` | $mod",false)
				->update('object_relationship');
			
			return;
		}
		
		//已知关联对象id，新增一条新关系
		if(array_key_exists('relative', $args)){
			$this->db->insert('object_relationship',array_merge(
				self::$fields_relationship,
				array('object'=>$this->id, 'relative'=>$args['relative'], 'mod'=>$mod)
			));

			return $this->db->insert_id();
		}
		
	}
	
	/**
	 * 给当前对象与一个相关对象的关系去除一个开关参数
	 * @param int $relationship_id
	 * @param string $mod_name
	 * @param string $relative_type
	 * @return boolean | Object_model
	 */
	function removeRelativeMod($relationship_id, $mod_name, $relative_type='_self'){

		if(!array_key_exists($relative_type, $this->relative_mod_list) || !array_key_exists($mod_name, $this->relative_mod_list[$relative_type])){
			log_message('error','relation type/mod name not found: '.$relation_type.' '.$mod_name);
			return false;
		}
		
		$this->db->where('id',$relationship_id)
			->set('mod',"`mod` & ~ {$this->relative_mod_list[$relative_type][$mod_name]}",false)
			->update('object_relationship');
		
		return $this;
	}
	
	/**
	 * 更新关系对象的关系开关参数
	 * @param int $relationship_id
	 * @param array $set
	 *	array(
	 *		'in_todo_list'=>true,
	 *		'deleted'=>false
	 *	)
	 * @param string $relative_type
	 * @return boolean|\Object_model
	 */
	function updateRelativeMod($relationship_id, $set, $relative_type='_self'){
		
		if(
			!array_key_exists($relative_type,$this->relative_mod_list)
			|| array_diff_key($set,$this->relative_mod_list[$relative_type])
		){
			log_message('error','not all relation type/mod name found: '.$relative_type.': '.implode(', ',array_keys($set)));
			return false;
		}
		
		$add=$remove=0;
		
		foreach($set as $mod_name => $status){
			$mod=$this->relative_mod_list[$relative_type][$mod_name];
			$status?($add|=$mod):($remove|=$mod);
		}
		
		$this->db->where('id',$relationship_id)
			->set('mod',"( `mod` | $add ) & ~ $remove",false)
			->update('object_relationship');
		
		return $this;
		
	}
	
	/**
	 * 获得对象的当前状态或者状态列表
	 */
	function getStatus(array $args = array()){
		
		$this->db->select('object_status.*')
			->select('UNIX_TIMESTAMP(date) timestamp')
			->select('DATE(date) date')
			->from('object_status')
			->where('object',$this->id);
		
		if(array_key_exists('id', $args)){
			$this->db->where('object_status.id',$args['id']);
			return $this->db->get()->row_array();
		}
		
		return $this->db->get()->result_array();
	}

	function addStatus(array $data){
		
		$data['object']=$this->id;
		
		foreach(array('timestamp','time','datetime','date') as $date_field){
			if(array_key_exists($date_field, $data)){
				$data['date']=$data[$date_field];
			}
		}
		
		if(array_key_exists('date',$data) && !$data['date']){
			unset($data['date']);
		}
		
		if(array_key_exists('date',$data) && is_integer($data['date'])){
			if($data['date'] >= 1E12){
				$data['date'] = $data['date']/1000;
			}
			$data['date'] = date('Y-m-d H:i:s',$data['date']);
		}
		
		$this->db->insert('object_status',array_merge(
			self::$fields_status,
			array_intersect_key($data, self::$fields_status)
		));
		
		return $this->db->insert_id();
	}
	
	function addStatuses(array $data){
		
		foreach($data as $row){
			$this->addStatus($row);
		}
		
		return $this;
	}
	
	function updateStatus(array $data, array $args = array()){
		
		$this->db->update('object_status',array_merge(
			array('uid'=>$this->user->id,'time'=>time()),
			array_intersect_key($data, self::$fields_status)
		),$args?$args:array('id'=>$data['id']));
		
		return $this;
	}
	
	function removeStatus(array $args = array()){
		
		$this->db->delete('object_status',array('id'=>$args['id']));
		
		return $this;
	}
	
	/**
	 * 获得一个对象的所有标签
	 * @param string $type
	 * @return array([type=>]name,...)
	 */
	function getTag(array $args = array()){
		
		$this->db->from('object_tag')
			->join('tag_taxonomy','tag_taxonomy.id = object_tag.tag_taxonomy')
			->join('tag','tag.id = tag_taxonomy.tag')
			->where('object_tag.object', $this->id)
			->select('tag.name, tag_taxonomy.taxonomy');
		
		$result = $this->db->get()->result_array();
		
		$tags = array_column($result, 'name', 'taxonomy');
		
		return $tags;
	}
	
	/**
	 * 为一个对象添加标签一个标签
	 * 不在tag表中将被自动注册
	 * 重复标签被将忽略
	 * 同type标签将被更新
	 * @param string $name
	 * @param string $type default: NULL 标签内容在此类对象的应用的意义，如案件的”阶段“等
	 */
	function addTag(array $data){
		
		$data['object']=$this->id;
		
		if(array_key_exists('id',$data)){
			$data['tag']=$data['id'];
			$data['tag_name']=$this->tag->fetch($data['tag'])->name;
		}
		
		if(!array_key_exists('id',$data) && array_key_exists('name', $data)){
			$data['tag']=$this->tag->match($data['name']);
			$data['tag_name']=$data['name'];
		}
		
		$this->db->upsert('object_tag',array_merge(
			self::$fields_tag,
			array_intersect_key($data, self::$fields_tag)
		));
		
		return $this->db->insert_id();
	}
	
	/**
	 * 为一个对象添加一组标签
	 * @param array $tags
	 */
	function addTags(array $tags){
		foreach($tags as $tag){
			$this->addTag($tag);
		}
		
		return $this;
	}
	
	/**
	 * @deprecated
	 * 
	 * 为一个对象更新一组带类型的标签
	 * 不存在的标签将被添加
	 * @param array $tags
	 * array(
	 *	[type=>]name,
	 *	...
	 * )
	 * @param bool $delete_other default: false 将输入数组作为所有标签，删除其他标签
	 */
	function updateTags(array $tags, $delete_other=false){
		
		//按类别更新标签
		foreach($tags as $type => $name){
			if(is_integer($type)){
				continue;
			}
			$tag_id=$this->tag->match($name);
			$set=array('tag'=>$tag_id,'tag_name'=>$name);
			$where=array('object'=>$this->id,'type'=>$type);
			$this->db->update('object_tag',array_merge(self::$fields_tag,$set,$where),$where);
		}
		
		$origin_tags=$this->getTag($this->id);
		
		//添加新的标签
		$this->addTags(array_diff($origin_tags,$tags));
		
		//删除其他标签
		if($delete_other){
			$other_tags=array_diff($origin_tags,$tags);
			$this->db->where_in('tag_name',$other_tags)->delete('object_tag');
		}
		
		return $this;
		
	}
	
	function removeTag(array $args = array()){
		
		$args['object']=$this->id;
		
		if(array_key_exists('id', $args)){
			$args['tag']=$args['id'];
		}
		
		if(array_key_exists('name', $args)){
			$args['tag_name']=$args['name'];
		}
		
		$this->db->delete('object_tag',array_intersect_key($args, self::$fields_tag));
		
		return $this;
	}
	
	/**
	 * 返回当前类型的对象中，包含$tags标签的对象，所包含的其他标签
	 * @param array $tags
	 * @param string $type
	 * @todo 按匹配度（具有尽量多相同的标签）和匹配量（匹配对象的数量）排序
	 */
	function getRelatedTags(array $tags, $type=NULL){
		
		$this->db->from('object_tag')
			->where("object IN (SELECT object FROM object_tag WHERE tag_name{$this->db->escape_array($tags)})",NULL,false)
			->group_by('tag');
		
		if(!is_null($type)){
			$this->db->where('type',$type);
		}
		
		return array_column($this->db->get()->result_array(),'tag_name');
	}
	
}
?>

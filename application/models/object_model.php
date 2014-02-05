<?php
class Object_model extends CI_Model{
	
	var $id;
	var $data;//具体对象数据
	var $meta;//具体对象的元数据
	var $relative;
	var $status;
	var $tag;//具体对象的标签
	
	static $fields=array(
		'name'=>NULL,
		'type'=>'',
		'num'=>'',
		'company'=>NULL,
		'user'=>NULL,
		'time'=>NULL
	);
	
	static $fields_relationship=array(
		'object'=>NULL,
		'relative'=>NULL,
		'relation'=>NULL,
		'num'=>'',
		'user'=>NULL,
		'time'=>NULL
	);
	
	static $fields_status=array(
		'object'=>NULL,
		'name'=>'',
		'date'=>NULL,
		'content'=>NULL,
		'comment'=>NULL,
		'user'=>NULL,
		'time'=>NULL
	);
	
	static $fields_tag=array(
		'object'=>NULL,
		'tag_taxonomy'=>NULL,
		'user'=>NULL,
		'time'=>NULL
	);

	function __construct() {
		parent::__construct();
	}
	
	/**
	 * 
	 * @throws Exception 'not_found'
	 */
	function fetch($id=NULL, array $args=array(), $permission_check = true){
		
		if(is_null($id)){
			$id=$this->id;
		}
		elseif(!array_key_exists('set_id', $args) || $args['set_id']){
			$this->id=$id;
		}
		
		if($permission_check && !$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		$this->db
			->from('object')
			->where(array(
				'object.id'=>$id,
				'object.company'=>$this->company->id,
			));
		
		$object=$this->db->get()->row_array();
		
		if(!$object){
			throw new Exception(lang('object').' '.$id.' '.lang('not_found'), 404);
		}
		
		foreach(array('meta','relative','status','tag') as $field){
			if(!array_key_exists('with_'.$field,$args) || $args['with_'.$field]){
				$property_args = array_key_exists('with_'.$field,$args) && is_array($args['with_'.$field]) ? $args['with_'.$field] : array();
				$object[$field]=call_user_func(array($this,'get'.$field), $property_args);
			}
		}
		
		return $object;

	}
	
	function add(array $data){
		
		$data['company']=$this->company->id;
		$data['user']=$this->user->id;
		$data['time_insert']=date('Y-m-d H:i:s');
		
		$this->db->insert('object',array_merge(self::$fields,array_intersect_key($data,self::$fields)));
		
		$this->id=$this->db->insert_id();
		
		$this->authorize(array('read'=>true,'write'=>true,'grant'=>true), $this->user->id, false);
		
		if(isset($data['meta'])){
			$this->addMetas($data['meta']);
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
		
		if(empty($data) || !$this->allow('write')){
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
		
		return $this->db->get()->result_array();
	}
	
	/**
	 * 判断一个对象对于一些用户或组来说是否具有某种权限
	 * 权限表中没有此对象，默认有权限
	 * @param string $permission	read | write | grant
	 * @param array|int $users	默认为$this->user->group_ids，即当前用户和递归所属组
	 * @return boolean
	 * @throws Exception	argument_error
	 */
	function allow($permission = 'read', $users = null){
		
		if(!in_array($permission, array('read', 'write', 'grant'))){
			throw new Exception('permission_name_error', 400);
		}
		
		if(is_null($users)){
			$users = $this->user->group_ids;
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		$result = $this->db->from('object_permission')->where('object',$this->id)->where_in('user',$users)->get()->row();
		
		if($result === array() || (bool)$result->$permission === true){
			return true;
		}
		
		return false;
	}
	
	/**
	 * 对某用户或组赋予/取消赋予一个对象某种权限
	 * @param array|string $permission	可选健包括array('read'=>true,'write'=>true,'grant'=>true)，为string时自动转换成array(string=>true)
	 * @param array|int $users	默认为$this->user->id，即当前用户
	 * @param boolean $permission_check 授权时是否检查当前用户的grant权限
	 * @throws Exception no_permission_to_grant
	 */
	function authorize($permission = array('read'=>true), $users = null, $permission_check = true){
		
		if(!is_array($permission)){
			$permission = array($permission => true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true,'write'=>true,'grant'=>true));
		
		if(is_null($users)){
			$users = array($this->user->id);
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		if($permission_check && !$this->allow('grant')){
			throw new Exception('no_permission_to_grant', 403);
		}
		
		foreach($users as $user){
			$this->db->upsert('object_permission', array('user'=>$user, 'object'=>$this->id) + $permission);
		}
		
	}
	
	/**
	 * 检测某一用户或组对当前对象的某一元数据是否有某种权限
	 * @param string $key 键名
	 * @param string $permission 权限值 read|write|grant
	 * @param array|int $users 要检测的用户或组，默认为$this->user->group_ids，即当前用户和递归所属组
	 */
	function allow_meta($key, $permission = 'read', $users = null){
		
		if(!in_array($permission, array('read', 'write', 'grant'))){
			throw new Exception('permission_name_error', 400);
		}
		
		if(is_null($users)){
			$users = $this->user->group_ids;
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		if(empty($users)){
			return false;
		}
		
		$result = $this->db->from('object_meta_permission')->where('object', $this->id)->where('key', $key)->where_in('user', $users)->get()->row();
		
		if($result === array() || (bool)$result->$permission === true){
			return true;
		}
		
		return false;
		
	}
	
	/**
	 * 就当前对象的某一元数据，授予某些用户或组某些权限
	 * @param string $key 键名
	 * @param array $permission 权限值 array(read|write|grant => true|false)
	 * @param array|int $users 要授权的用户或组，默认为$this->user->id，即当前用户
	 */
	function authorize_meta($key, array $permission = array(), $users = null, $permission_check = true){
		
		if(!is_array($permission)){
			$permission = array($permission => true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true,'write'=>true,'grant'=>true));
		
		if(is_null($users)){
			$users = array($this->user->id);
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		if($permission_check && (!$this->allow('grant') || !$this->allow_meta($key, 'grant'))){
			throw new Exception('no_permission_to_grant', 403);
		}
		
		foreach($users as $user){
			$this->db->upsert('object_meta_permission', array('object'=>$this->id, 'key'=>$key, 'user'=>$user) + $permission);
		}
		
	}
	
	function _parse_criteria($args, $field='`object`.`id`', $logical_operator = 'AND'){
		
		if(!is_array($args)){
			return $field.' = '.$this->db->escape($args);
		}
		
		//如果参数数组不为空，且全是数字键，则作in处理
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
				if($arg_value === array()){
					$where[] = ' FALSE';
				}
				else{
					$where[] = $field." IN ( \n".implode(', ', array_map(array($this->db, 'escape'), $arg_value)).' )';
				}
			}
			elseif($arg_name === 'nin'){
				$where[] = $field." NOT IN ( \n".implode(', ', array_map(array($this->db, 'escape'), $arg_value)).' )';
			}
			
			elseif($arg_name === 'meta'){
				foreach($arg_value as $key => $value){
					$meta_criteria = is_integer($key) ? " {$this->_parse_criteria($value, '`key`')} " : " `key` = {$this->db->escape($key)} AND {$this->_parse_criteria($value, '`value`')} " ;
					$where[] = "$field IN ( \nSELECT `object` FROM `object_meta` WHERE$meta_criteria)";
				}
			}
			elseif($arg_name === 'status'){
				foreach($arg_value as $name => $date){
					if(is_integer($name)){
						$name = $date;
						$date = false;
					}
					$status_criteria = !$date ? " {$this->_parse_criteria($name, '`name`')} " : " `name` = {$this->db->escape($name)} AND {$this->_parse_criteria($date, '`date`')} ";
					$where[] = "$field IN ( \nSELECT `object` FROM `object_status` WHERE$status_criteria)";
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
					$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($relation).' AND ';
					$where[] = "$field IN ( \nSELECT `relative` FROM `object_relationship` WHERE $relation_criteria".$this->_parse_criteria($relative_args, '`object_relationship`.`object`')." \n)";
				}
			}
			elseif($arg_name === 'has_relative_like'){
				foreach($arg_value as $relation => $relative_args){
					$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($relation).' AND ';
					$where[] = "$field IN ( \nSELECT `object` FROM `object_relationship` WHERE $relation_criteria".$this->_parse_criteria($relative_args, '`object_relationship`.`relative`')." \n)";
				}
			}
			
			elseif(in_array($arg_name,array('name','type','user','time'))){
				if($field === '`object`.`id`'){
					$where[] = $this->_parse_criteria($arg_value, '`object`.'.$arg_name);
				}else{
					$where[] = "$field IN ( \nSELECT id FROM object WHERE  ".$this->_parse_criteria($arg_value, '`object`.'.$arg_name)." \n)";
				}
				
			}
			
		}

		return empty($where) ? '1 = 1' : ("( \n".implode("\n$logical_operator\n", $where)." \n)");

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
		
		$permission_condition = '`object`.`id` NOT IN ( SELECT `object` FROM `object_permission` )';
		
		if(is_array($this->user->group_ids) && !empty($this->user->group_ids)){
			$permission_condition .= ' OR `object`.`id` IN ( SELECT `object` FROM `object_permission` WHERE `read` = TRUE AND `user` IN ( '.implode(', ',$this->user->group_ids).' ) )';
		}
		
		$this->db->where('( '.$permission_condition.' )');
		
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
		
		foreach(array('meta','relative','status','tag') as $field){
			if(array_key_exists('with_'.$field,$args) && $args['with_'.$field]){
				array_walk($result_array,function(&$row, $index, $field, $args){
					$this->id = $row['id'];
					$property_args = is_array($args['with_'.$field]) ? $args['with_'.$field] : array();
					$row[$field] = call_user_func(array($this,'get'.$field), $property_args);
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
		if(isset($result['data'][0])){
			return $result['data'][0];
		}else{
			return array();
		}
	}
	
	/**
	 * 返回一个对象的资料项列表
	 * @return array
	 */
	function getMeta(array $args = array()){
		
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		$this->db->select('object_meta.*')
			->from('object_meta')
			->where("`object_meta`.`object`",$this->id);
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args)){
			return $result;
		}
		
		$meta = array();
		
		foreach($result as $row){
			if($this->allow_meta($row['key'])){
				$meta[$row['key']][] = $row['value'];
			}
		}
		
		return $meta;
		
	}
	
	/**
	 * 给当前对象添加一个元数据
	 * 即使键已经存在，仍将添加，除非$unique为true，那样的话不执行任何写入
	 * @param string $key
	 * @param string $value
	 * @param boolean $unique
	 * @return boolean
	 */
	function addMeta($key, $value, $unique = false){
		
		if(!$this->allow('write') || !$this->allow_meta($key, 'write')){
			throw new Exception('no_permission', 403);
		}
		
		if($unique){
			$metas = $this->getMeta();
			if(array_key_exists($key, $metas)){
				return false;
			}
		}
		
		$this->db->insert('object_meta', array(
			'object'=>$this->id,
			'key'=>$key,
			'value'=>$value,
			'company'=>$this->company->id,
			'user'=>$this->user->id
		));
		
		$meta_id = $this->db->insert_id();
		
		return $meta_id;
	}
	
	/**
	 * 更新对象元数据
	 * 首先检查键名是否存在，如果不存在则执行addMeta()
	 * @param string $key
	 * @param string $value
	 * @param string $prev_value optional 如果不为null，则只更新原来值为$prev_value的记录
	 * @return boolean
	 */
	function updateMeta($key, $value, $prev_value = null){
		
		if(!$this->allow('write') || !$this->allow_meta($key, 'write')){
			throw new Exception('no_permission', 403);
		}
		
		$metas = $this->getMeta();
		
		if(!array_key_exists($key, $metas)){
			return $this->addMeta($key, $value);
		}
		
		$condition = array('key'=>$key);
		
		if(!is_null($prev_value)){
			$condition += array('value'=>$prev_value);
		}
		
		return $this->db->update('object_meta', array('value'=>$value), $condition);
	}
	
	/**
	 * 删除对象元数据
	 * @param string $key
	 * @param string $value optional
	 * @return boolean
	 */
	function removeMeta($key, $value = null){
		
		if(!$this->allow('write') || !$this->allow_meta($key, 'write')){
			throw new Exception('no_permission', 403);
		}
		
		$condition = array('key'=>$key);
		
		if(!is_null($value)){
			$condition += array('value'=>$value);
		}
		
		return $this->db->delete('object_meta', $condition);
	}
	
	function getRelative(array $args = array()){
		
		$this->db
			->from('object_relationship')
			->where('object_relationship.object',$this->id);
		
		if(array_key_exists('relation', $args)){
			$this->db->where('object_relationship.relation',$args['relation']);
		}
		
		if(array_key_exists('id', $args)){
			$this->db->where('object_relationship.id',$args['id']);
			return $this->db->get()->row_array();
		}
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args)){
			return $result;
		}
		
		$relatives = array();
		
		foreach($result as $row){
			$relatives[$row['relation']][] = $this->fetch($row['relative'], array('with_meta'=>false, 'with_relative'=>false, 'with_status'=>false, 'with_tag'=>false, 'set_id'=>false));
		}
		
		return $relatives;
		
	}
	
	function addRelative(array $data){
		
		$data['object']=$this->id;
		$data['user']=$this->user->id;
		
		$this->db->insert('object_relationship',array_merge(
			self::$fields_relationship,
			array_intersect_key($data, self::$fields_relationship)
		));
		
		return $this->db->insert_id();
	}
	
	function updateRelative(array $data, array $args=array()){
		
		$this->db->update('object_relationship',array_merge(
				array('user'=>$this->user->id),
				array_intersect_key($data, self::$fields_relationship)
			),$args?$args:array('id'=>$data['id']));
		
		return $this;
	}
	
	function removeRelative(array $args = array()){
		
		$this->db->where('id',$args['id'])->delete('object_relationship');
		return $this;
	}
	
	/**
	 * 获得对象的当前状态或者状态列表
	 */
	function getStatus(array $args = array()){
		
		$this->db->select('object_status.*')
			->select('UNIX_TIMESTAMP(`date`) `timestamp`', false)
			->select('date')
			->from('object_status')
			->where('object',$this->id)
			->order_by('date');
		
		if(array_key_exists('id', $args)){
			$this->db->where('object_status.id',$args['id']);
			return $this->db->get()->row_array();
		}
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args) && $args['as_rows']){
			return $result;
		}
		
		$status = array();
		
		foreach($result as $row){
			$status[$row['name']] = $row['date'];
		}
		
		return $status;
		
	}

	function addStatus(array $data){
		
		$data['object']=$this->id;
		$data['user']=$this->user->id;
		
		if(array_key_exists('date',$data) && is_integer($data['date'])){
			
			if($data['date'] >= 1E12){
				$data['date'] = $data['date']/1000;
			}
			
			$data['date'] = date('Y-m-d H:i:s',$data['date']);
		}
		
		empty($data['date']) && $data['date'] = date('Y-m-d H:i:s');
		
		$this->db->insert('object_status',array_merge(
			self::$fields_status,
			array_intersect_key($data, self::$fields_status)
		));
		
		return $this->db->insert_id();
	}
	
	function updateStatus(array $data, array $args = array()){
		
		$this->db->update('object_status',array_merge(
			array('user'=>$this->user->id),
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
			->join('tag_taxonomy','tag_taxonomy.id = object_tag.tag_taxonomy','inner')
			->join('tag','tag.id = tag_taxonomy.tag','inner')
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
		$data['user']=$this->user->id;
		
		//TODO
		
		return $this->db->insert_id();
	}
	
	function removeTag(array $args = array()){
		
		$args['object']=$this->id;
		
		//TODO
		
		return $this;
	}
	
}
?>

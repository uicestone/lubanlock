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
	
	static $fields_meta=array(
		'object'=>NULL,
		'key'=>'',
		'value'=>NULL,
		'comment'=>NULL,
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
		
		$this->db
			->from('object')
			->where(array(
				'object.id'=>$id,
				'object.company'=>$this->company->id,
			));
		
		if($permission_check){
		
			$permission_condition = 'object.id NOT IN ( SELECT `object` FROM `object_permission` )';

			if(is_array($this->user->group_ids) && !empty($this->user->group_ids)){
				$permission_condition .= ' OR object.id IN ( SELECT `object` FROM `object_permission` WHERE `read` = TRUE AND user IN ( '.implode(', ',$this->user->group_ids).' ) )';
			}

			$this->db->where('( '.$permission_condition.' )');
		}
		
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
	 * 根据部分名称，返回唯一的id
	 * @param type $part_of_name
	 * @return type
	 * @throws Exception 'not_found','duplicated_matches'
	 */
	function check($part_of_name){
		$result=$this->db
			->from('object')
			->where('object.company',$this->company->id)
			->like('name',$part_of_name)
			->get();

		if($result->num_rows()>1){
			throw new Exception(lang('duplicated_matches').' '.$part_of_name, 400);
		}
		elseif($result->num_rows===0){
			throw new Exception($part_of_name.' '.lang('not_found'));
		}
		else{
			return $result->row()->id;
		}
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
			throw new Exception('argument_error', 400);
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
	 * 判断一个对象属性对于一些用户或组来说是否具有某种权限
	 * 权限表中没有此对象属性，默认有权限
	 * @param string $property	meta|relative|status|tag
	 * @param string $permission	read | write | grant
	 * @param type $property_id
	 * @param array|int $users	默认为$this->user->group_ids，即当前用户和递归所属组
	 * @return boolean
	 * @throws Exception	argument_error
	 */
	function allow_property($property, $permission = 'read', $property_id = null, $users = null){
		
		if(!in_array($property, array('meta', 'relative', 'status', 'tag'))){
			throw new Exception('property_name_error', 400);
		}
		
		if(!in_array($permission, array('read', 'write', 'grant'))){
			throw new Exception('argument_error', 400);
		}
		
		if(is_null($property_id)){
			$property_id = $this->$property->id;
		}
		
		if(is_null($users)){
			$users = $this->user->group_ids;
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		$result = $this->db->from("object_{$property}_permission")->where('object_'.$property,$property_id)->where_in('user',$users)->get()->row();
		
		if($result === array() || (bool)$result->$permission === true){
			return true;
		}
		
		return false;
	}
	
	/**
	 * 对某用户或组赋予/取消赋予一个对象某种权限
	 * @param array|string $permission	可选健包括array('read'=>true,'write'=>true,'grant'=>true)，为string时自动转换成array(string=>true)
	 * @param array|int $users	默认为$this->user->id，即当前用户
	 * @param bool $permission_check
	 * @throws Exception
	 */
	function authorize($permission = array('read'=>true), $users = null, $permission_check = true){
		
		if(is_null($users)){
			$users = array($this->user->id);
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		if($permission_check && !$this->allow('grant')){
			throw new Exception('no_permission_to_grant', 403);
		}
		
		if(!is_array($permission)){
			$permission = array($permission => true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true,'write'=>true,'grant'=>true));
		
		foreach($users as $user){
			$this->db->upsert('object_permission', array('user'=>$user, 'object'=>$this->id) + $permission);
		}
		
	}
	
	/**
	 * 对某用户或组赋予/取消赋予一个对象属性某种权限
	 * @param string $property	meta|relative|status|tag
	 * @param array|string $permission	可选健包括array('read'=>true,'write'=>true,'grant'=>true)，为string时自动转换成array(string=>true)
	 * @param type $property_id
	 * @param array|int $users	默认为$this->user->id，即当前用户
	 * @param bool $permission_check
	 * @throws Exception
	 */
	function authorize_property($property, $permission = array('read'=>true), $property_id = null, $users = null, $permission_check = true){
		
		if(!in_array($property, array('meta', 'relative', 'status', 'tag'))){
			throw new Exception('property_name_error', 400);
		}
		
		if(!is_array($permission)){
			$permission = array($permission => true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true,'write'=>true,'grant'=>true));
		
		if(is_null($property_id)){
			$property_id = $this->$property->id;
		}
		
		if(is_null($users)){
			$users = array($this->user->id);
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		if($permission_check && (!$this->allow('grant') || !$this->allow_property($property, 'grant', $property_id))){
			throw new Exception('no_permission_to_grant', 403);
		}
		
		foreach($users as $user){
			$this->db->upsert("object_{$property}_permission", array('user'=>$user, 'object_'.$property=>$property_id) + $permission);
			echo $this->db->last_query()."\n";
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
	 * 生成一个SQL表达式条件，用于确认某种对象属性的权限
	 * 对象属性权限表有权限读，如果无记录，则看对象是否有权限读，如果无记录，则视为有权
	 * @param string $property
	 */
	function _property_permission_conditions($property){
		$permission_condition = "`object_$property`.`id` NOT IN ( SELECT `object_$property` FROM `object_{$property}_permission` ) AND (";
		
		$permission_condition .= "\n`object_$property`.`object` NOT IN ( SELECT `object` FROM `object_permission` )";
		
		if(!empty($this->user->group_ids)){
			$permission_condition .= " OR `object_$property`.`object` IN ( SELECT `object` FROM `object_permission` WHERE `read` = TRUE AND `user` IN ( ".implode(', ', $this->user->group_ids).' ) )';
		}
		
		$permission_condition .= "\n)";
		
		if(!empty($this->user->group_ids)){
			$permission_condition .= "\nOR `object_$property`.`id` IN ( SELECT `object_$property` FROM `object_{$property}_permission` WHERE `read` = TRUE AND `user` IN ( ".implode(', ', $this->user->group_ids).' ) )';
		}
		
		return '( '.$permission_condition.' )';
		
	}
	
	/**
	 * 返回一个对象的资料项列表
	 * @return array
	 */
	function getMeta(array $args = array()){
		
		$this->db->select('object_meta.*')
			->from('object_meta')
			->where("`object_meta`.`object`",$this->id);
		
		$this->db->where($this->_property_permission_conditions('meta'));
		
		if(array_key_exists('id', $args)){
			$this->db->where('`object_meta`.`id`',$args['id']);
			return $this->db->get()->row_array();
		}
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args)){
			return $result;
		}
		
		$meta = array();
		
		foreach($result as $row){
			$meta[$row['key']][] = $row['value'];
		}
		
		return $meta;
		
	}
	
	/**
	 * 给当前对象添加一个资料项
	 * @return Object_model
	 */
	function addMeta(array $data){
		
		$data['object']=$this->id;
		$data['company']=$this->company->id;
		$data['user']=$this->user->id;
		
		$data=array_merge(
			self::$fields_meta,
			array_intersect_key($data, self::$fields_meta)
		);
		
		$this->db->upsert('object_meta',$data);
		
		return $this->db->insert_id();
	}
	
	function addMetas(array $data){
		
		foreach($data as $id => $row){
			if(is_integer($id)){
				$this->addMeta($row);
			}else{
				$this->addMeta(array('key'=>$id, 'value'=>$row));
			}
			
		}
		
		return $this;
	}
	
	/**
	 * 给$key项增加$value
	 * 若$key项不存在则先创建
	 * @param string $key
	 * @param string $value
	 */
	function increaseMeta($key, $value){
		$meta = $this->getMeta();
		if(array_key_exists($key, $meta)){
			$this->db->set("`value` = `value` + ".$this->db->escape($value), null, false)
				->where('`object_meta`.`object`', $this->id)
				->where('object_meta.key', $key)
				->update('object_meta')
				->limit(1);
		}
		else{
			$this->addMeta(compact('key', 'value'));
		}
	}
	
	/**
	 * 更新对象的单条meta，须已知object_meta.id
	 * @param array $data
	 * @return Object_model
	 */
	function updateMeta($data, array $args = array()){
		
		$this->db->update('object_meta',array_merge(
			array('user'=>$this->user->id),
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
	
	function getRelative(array $args = array()){
		
		$this->db
			->from('object_relationship')
			->where('object_relationship.object',$this->id);
		
		$this->db->where($this->_property_permission_conditions('relationship'));
		
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
	
	function addRelatives(array $data){
		
		foreach($data as $index => $row){
			if(is_integer($index)){
				$this->addRelative($row);
			}
			else{
				$this->addRelative(array('relation'=>$index,'relative'=>$row));
			}
		}
		
		return $this;
	}
	
	function updateRelative(array $data, array $args=array()){
		
		$this->db->update('object_relationship',array_merge(
				array('user'=>$this->user->id),
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
		
		$this->db->where($this->_property_permission_conditions('status'));
		
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
	
	function addStatuses(array $data){
		
		foreach($data as $row){
			$this->addStatus($row);
		}
		
		return $this;
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
		
		$this->db->where($this->_property_permission_conditions('tag'));
		
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
	
	function removeTag(array $args = array()){
		
		$args['object']=$this->id;
		
		//TODO
		
		return $this;
	}
	
}
?>

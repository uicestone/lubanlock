<?php
class Object_model extends CI_Model{
	
	var $id;
	
	static $fields=array(
		'name'=>NULL,
		'type'=>'',
		'num'=>'',
		'company'=>NULL,
		'user'=>NULL,
		'time'=>NULL,
		'time_insert'=>NULL
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
		
		foreach(array('meta','relative','status','tag','permission') as $field){
			if(!array_key_exists('with_'.$field,$args) || $args['with_'.$field]){
				$property_args = array_key_exists('with_'.$field,$args) && is_array($args['with_'.$field]) ? $args['with_'.$field] : array();
				$object[$field]=call_user_func(array($this,'get'.$field), $property_args);
			}
		}
		
		return $object;

	}
	
	/**
	 * 
	 * @param array $data
	 *	keys in self::$field
	 *	permission bool|array
	 *		array(user_id=>permission_name, ...)
	 *		array(user_id=>array(permission_name=>false), ...)
	 * @return int insert id
	 */
	function add(array $data){
		
		$data['company']=$this->company->id;
		$data['user']=$this->user->session_id;
		$data['time_insert']=date('Y-m-d H:i:s');
		
		$this->db->insert('object', array_merge(self::$fields, array_intersect_key($data, self::$fields)));
		
		$this->id=$this->db->insert_id();
		
		if(array_key_exists('permission', $data)){
			foreach($data['permission'] as $user => $permissions){
				$this->authorize($permissions, $user);
			}
		}
		
		$this->authorize(array('read'=>true,'write'=>true,'grant'=>true), $this->user->session_id, false);
		
		foreach(array('meta', 'relative', 'status', 'tag') as $property){
			if(array_key_exists($property, $data)){
				call_user_func(array($this, 'add'.$property), $data[$property]);
			}
		}
		
		return $this->id;
	}
	
	function update(array $data){

		$data=array_intersect_key($data, self::$fields);
		
		if(empty($data)){
			return;
		}
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$this->db->set($data)->where('id', $this->id)->update('object');
		
		return $this->db->affected_rows();
	}
	
	function remove(){
		$result = $this->db->where('id', $this->id)->delete('object');
		
		if($this->db->error() && strpos($this->db->error()['message'], 'Cannot delete or update a parent row: a foreign key constraint fails') === 0){
			throw new Exception('存在关联数据，无法删除', 500);
		}
		
		return $result;
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
		
		if(is_null($result) || (bool)$result->$permission === true){
			return true;
		}
		
		return false;
	}
	
	/**
	 * 对某用户或组赋予/取消赋予一个对象某种权限
	 * @param array|string $permission	可选健包括array('read'=>true,'write'=>true,'grant'=>true)，为string时自动转换成array(string=>true)
	 * @param array|int $users	默认为$this->user->session_id，即当前用户
	 * @param boolean $permission_check 授权时是否检查当前用户的grant权限
	 * @throws Exception no_permission_to_grant
	 */
	function authorize($permission = array('read'=>true), $users = null, $permission_check = true){
		
		if(!is_array($permission)){
			$permission = array($permission => true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true,'write'=>true,'grant'=>true));
		
		if(is_null($users)){
			$users = array($this->user->session_id);
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
		
		if(is_null($result) || (bool)$result->$permission === true){
			return true;
		}
		
		return false;
		
	}
	
	/**
	 * 就当前对象的某一元数据，授予某些用户或组某些权限
	 * @param string $key 键名
	 * @param array $permission 权限值 array(read|write|grant => true|false)
	 * @param array|int $users 要授权的用户或组，默认为$this->user->session_id，即当前用户
	 */
	function authorize_meta($key, array $permission = array(), $users = null, $permission_check = true){
		
		if(!is_array($permission)){
			$permission = array($permission => true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true,'write'=>true,'grant'=>true));
		
		if(is_null($users)){
			$users = array($this->user->session_id);
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
		if(!empty($args) && array_reduce(array_keys($args), function($result, $item){
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
				if(is_array($arg_value)){
					foreach($arg_value as $relation => $relative_args){
						$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($relation).' AND ';
						$where[] = "$field IN ( \nSELECT `relative` FROM `object_relationship` WHERE $relation_criteria".$this->_parse_criteria($relative_args, '`object_relationship`.`object`')." \n)";
					}
				}else{
					$where[] = "$field IN ( \nSELECT `relative` FROM `object_relationship` WHERE ".$this->_parse_criteria($arg_value, '`object_relationship`.`object`')." \n)";
				}
			}
			elseif($arg_name === 'has_relative_like'){
				if(is_array($arg_value)){
					foreach($arg_value as $relation => $relative_args){
						$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($relation).' AND ';
						$where[] = "$field IN ( \nSELECT `object` FROM `object_relationship` WHERE $relation_criteria".$this->_parse_criteria($relative_args, '`object_relationship`.`relative`')." \n)";
					}
				}else{
					$where[] = "$field IN ( \nSELECT `object` FROM `object_relationship` WHERE ".$this->_parse_criteria($arg_value, '`object_relationship`.`relative`')." \n)";
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

		$this->db->from('object')->found_rows()->select('object.*');
		
		$this->db->where('object.company', $this->company->id);
		
		$this->db->where($this->_parse_criteria($args), null, false);
		
		$permission_condition = "\n".'`object`.`id` NOT IN ( SELECT `object` FROM `object_permission` )';
		
		if($this->user->roles){
			$permission_condition .= "\nOR `object`.`type` IN ('".implode("', '", $this->user->roles)."')";
		}
		
		if(is_array($this->user->group_ids) && !empty($this->user->group_ids)){
			$permission_condition .= "\n".'OR `object`.`id` IN ( SELECT `object` FROM `object_permission` WHERE `read` = TRUE AND `user` IN ( '.implode(', ',$this->user->group_ids).' ) )';
		}
		
		$this->db->where('( '.$permission_condition.' )', null, false);
		
		if(!array_key_exists('order_by', $args)){
			$args['order_by'] = 'object.time desc';
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
		if(array_key_exists('page', $args)){
			if(!array_key_exists('per_page', $args)){
				$args['per_page'] = $this->company->config('per_page');
				
				if(!$args['per_page']){
					$args['per_page'] = 25;
				}
			}
			//页码-每页数量方式，转换为sql limit
			$args['limit'] = array($args['per_page'],($args['page']-1)*$args['per_page']);
		}
		
		if(!array_key_exists('limit', $args)){
			//默认limit
			$args['limit'] = $this->company->config('per_page');
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
		
		$result = array('data'=>array(), 'info'=>array(
			'total'=>$this->db->query('SELECT FOUND_ROWS() rows')->row()->rows,
			'from'=>is_array($args['limit']) ? $args['limit'][1] + 1 : 1,
			'to'=>is_array($args['limit']) ? $args['limit'][0] + $args['limit'][1] : $args['limit']
		));
		
		if($result['info']['to'] > $result['info']['total']){
			$result['info']['to'] = $result['info']['total'];
		}
		
		//获得四属性的参数，决定是否为对象列表获取属性
		foreach(array('meta','relative','status','tag','permission') as $property){
			if(array_key_exists('with_'.$property, $args) && $args['with_'.$property]){
				array_walk($result_array,function(&$row, $index, $userdata){
					$this->id = $row['id'];
					//参数值可以不是true而是一个数组，那样的话这个数组将被传递给get{property}()方法作为参数
					!is_array($userdata['property_args']) && $userdata['property_args'] = array();
					$row[$userdata['property']] = call_user_func(array($this, 'get'.$userdata['property']), $userdata['property_args']);
				}, array('property'=>$property, 'property_args'=>$args['with_'.$property]));
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
	
	function getPermission(array $args = array()){
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		$result = $this->db->from('object_permission')->where('object', $this->id)->get()->result();
		
		$permission = array('read'=>array(), 'write'=>array(), 'grant'=>array());
		
		foreach($result as $row){
			foreach(array('read', 'write', 'grant') as $type){
				$row->$type && $permission[$type][] = $row->user;
			}
		}
		
		return $permission;
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
			->where("`object_meta`.`object`",$this->id)
			->order_by('`object_meta`.`time`');
		
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
	 * 给当前对象添加一个或多个元数据
	 * @todo 添加多个元数据的功能考虑移除
	 * 即使键已经存在，仍将添加，除非$unique为true，那样的话不执行任何写入
	 * 支持通过单个数组参数的方式一次添加多个元数据
	 * @param string|array $key
	 *	单数组参数一次添加多个元数据的参数格式：
	 *	array(
	 *		key=>value,
	 *		array(
	 *			'key'=>key,
	 *			'value'=>value,
	 *			'unique'=>unique
	 *		)
	 *	)
	 * @param string $value
	 * @param boolean $unique
	 * @return boolean
	 */
	function addMeta($key, $value = null, $unique = false){
		
		if(is_array($key)){
			
			$meta_ids = array();
			
			foreach($key as $k => $v){
				if(is_array($v)){
					if(!array_key_exists('key', $v) || !array_key_exists('value', $v)){
						throw new Exception('argument_error', 400);
					}
					$meta_ids[] = $this->addMeta($v['key'], $v['value'], array_key_exists('unique', $v) ? $v['unique'] : $unique);
				}
				else{
					$meta_ids[] = $this->addMeta($k, $v, $unique);
				}
			}
			
			return $meta_ids;
		}
		
		if(is_null($value)){
			return;
		}
		
		if(!$this->allow('write') || !$this->allow_meta($key, 'write')){
			throw new Exception('no_permission', 403);
		}
		
		$metas = $this->getMeta();
		
		if($unique){
			if(array_key_exists($key, $metas)){
				throw new Exception('duplicated_meta_key', 400);
			}
		}
		
		if(array_key_exists($key, $metas) && in_array($value, $metas[$key])){
			throw new Exception('duplicated_meta_key_value', 400);
		}
		
		$this->db->insert('object_meta', array(
			'object'=>$this->id,
			'key'=>$key,
			'value'=>$value,
			'user'=>$this->user->session_id
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
		
		if(is_array($value)){
			if(array_key_exists('value', $value)){
				$value = $value['value'];
			}
			else{
				throw new Exception('argument_error', 400);
			}
		}
		
		if(!array_key_exists($key, $metas)){
			return $this->addMeta($key, $value);
		}
		
		if(array_key_exists($key, $metas) && in_array($value, $metas[$key])){
			throw new Exception('duplicated_meta_key_value', 400);
		}
		
		$condition = array('object'=>$this->object->id, 'key'=>$key);
		
		if(!is_null($prev_value)){
			$condition += array('value'=>$prev_value);
		}
		
		return $this->db->order_by('time')->limit(1)->update('object_meta', array('value'=>$value), $condition);
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
	
	/**
	 * 
	 * @param array $args
	 *	as_rows
	 *	id_only
	 *	include_disabled
	 *	with_meta default:true
	 * @return array
	 */
	function getRelative(array $args = array()){
		
		$this->db
			->from('object_relationship')
			->where('object_relationship.object',$this->id);
		
		if(array_key_exists('relation', $args)){
			$this->db->where('object_relationship.relation',$args['relation']);
		}
		
		if(!array_key_exists('include_disabled', $args) || !$args['include_disabled']){
			$this->db->where('object_relationship.is_on', true);
		}
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args) && $args['as_rows']){
			return $result;
		}
		
		$relatives = array();
		
		foreach($result as $relationship){
			
			if(array_key_exists('id_only', $args) && $args['id_only']){
				$relatives[$relationship['relation']][] = $relationship['relative'];
			}
			else{
				$relative = $this->fetch($relationship['relative'], array('with_meta'=>false, 'with_relative'=>false, 'with_status'=>false, 'with_tag'=>false, 'set_id'=>false));
				$relative['relationship_id'] = $relationship['id'];
				$relative['relationship_num'] = $relationship['num'];
				$relative['is_on'] = (bool)$relationship['is_on'];
				
				if(!array_key_exists('with_meta', $args) || $args['with_meta']){
					$relative['meta'] = $this->getRelativeMeta($relationship['id']);
				}
				
				$relatives[$relationship['relation']][] = $relative;
			}
		}
		
		return $relatives;
		
	}
	
	/**
	 * 为一个对象添加一个或多个关联对象
	 * @param string $relation 关系
	 * @param string $relative 关联对象id
	 * @param string $num optional, 关系的编号
	 * @param bool $is_on 是否启用关系，若为false，此关系虽然被保存，但默认情况不会被获取
	 * @param array $args
	 *	replace_meta
	 * @return int|array new meta id(s)
	 * @throws Exception
	 */
	function setRelative($relation, $relative, $num = '', array $meta = array(), $is_on = true, array $args = array()){
		
		try{
			$this->fetch($relative, array('set_id'=>false));
		}catch(Exception $e){
			throw new Exception('invalid_relative', 400);
		}
		
		$return = $this->db->upsert('object_relationship', array(
			'object'=>$this->id,
			'relative'=>$relative,
			'relation'=>$relation,
			'num'=>$num,
			'is_on'=>$is_on,
			'user'=>$this->user->session_id
		));
		
		//根据参数，先删除不在此次添加之列的键值对
		if(array_key_exists('replace_meta', $args) && $args['replace_meta']){
			
			$meta_origin = $this->getRelativeMeta($relation, $relative);
			
			foreach($meta_origin as $key => $value){
				if(!array_key_exists($key, $meta)){
					$this->removeRelativeMeta($relation, $relative, $key);
				}
			}
		}
		
		foreach($meta as $key => $value){
			$this->setRelativeMeta($relation, $relative, $key, $value);
		}
		
		return $return;
	}
	
	function removeRelative($relation, $relative){
		return $this->db->delete('object_relationship', array('object'=>$this->id, 'relation'=>$relation, 'relative'=>$relative));
	}
	
	function _getRelationshipID($relation, $relative){
		
		$relationship = $this->db->from('object_relationship')->where(array('object'=>$this->id, 'relative'=>$relative, 'relation'=>$relation, 'is_on'=>true))->get()->row();
		
		if(!$relationship){
			throw new Exception('relationship_not_exist', 500);
		}
		
		return $relationship->id;
	}
	
	function getRelativeMeta($relation, $relative = null){
		$relationship_id = is_null($relative) ? $relation : $this->_getRelationshipID($relation, $relative);
		$result = $this->db->from('object_relationship_meta')->where('relationship', $relationship_id)->get()->result_array();
		return array_column($result, 'value', 'key');
	}
	
	function setRelativeMeta($relation, $relative, $key, $value){
		$relationship_id = is_null($relative) ? $relation : $this->_getRelationshipID($relation, $relative);
		return $this->db->upsert('object_relationship_meta', array('relationship'=>$relationship_id, 'key'=>$key, 'value'=>$value, 'user'=>$this->user->session_id));
	}
	
	function removeRelativeMeta($relation, $relative, $key){
		$relationship_id = is_null($relative) ? $relation : $this->_getRelationshipID($relation, $relative);
		return $this->db->delete('object_relationship_meta', array('relationship'=>$relationship_id, 'key'=>$key));
	}
	
	/**
	 * 获得对象的当前状态或者状态列表
	 * @property array $args
	 *	as_rows bool default: false 对象属性是无序的，需要有序序列时，将本参数设置为true来获得一个数组
	 * $return array|object
	 */
	function getStatus(array $args = array()){
		
		$this->db->select('object_status.*')
			->select('UNIX_TIMESTAMP(`date`) `timestamp`', false)
			->select('date')
			->from('object_status')
			->where('object',$this->id);
		
		array_key_exists('order_by', $args) ? $this->db->order_by($args['order_by']) : $this->db->order_by('date');
		
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
	
	function _parse_date($date){
		
		if(empty($date)){
			$date = date('Y-m-d H:i:s');
		}
		
		elseif(is_integer($date)){
			
			if($date >= 1E12){
				$date = $date/1000;
			}
			
			$date = date('Y-m-d H:i:s', $date);
		}
		
		elseif(strtotime($date)){
			return date('Y-m-d H:i:s', strtotime($date));
		}
		
		else{
			throw new Exception('invalid_date_input', 400);
		}
		
		return $date;
	}

	function addStatus($name, $date = null, $comment = null){
		
		$this->db->insert('object_status',array(
			'object'=>$this->id,
			'name'=>$name,
			'date'=>$this->_parse_date($date),
			'comment'=>$comment,
			'user'=>$this->user->session_id
		));
		
		return $this->db->insert_id();
	}
	
	/**
	 * 更新对象状态
	 * @param string $name 要更新的状态名
	 * @param string|int $date 新的日期
	 * @param string $comment 新的备注
	 * @param string|int $prev_date 为null则更新日期最新一条名称为$name的状态，否则更新名称为$name且日期为$prev_date的状态
	 */
	function updateStatus($name, $date = null, $comment = null, $prev_date = null){
		
		$set = array();
		
		if(!is_null($date)){
			$set['date'] = $this->_parse_date($date);
		}
		
		if(!is_null($comment)){
			$set['comment'] = $comment;
		}
		
		$where = array(
			'object'=>$this->id,
			'name'=>$name
		);
		
		if(!is_null($prev_date)){
			$where['date'] = $this->_parse_date($prev_date);
		}
		else{
			$this->db->order_by('date desc')->limit(1);
		}
		
		$this->db->update('object_status', $set, $where);
	}
	
	function removeStatus($name, $date = null){
		
		$where = array(
			'object'=>$this->id,
			'name'=>$name
		);
		
		if(!is_null($date)){
			$where['date'] = $this->_parse_date($date);
		}
		
		return $this->db->delete('object_status', $where);
	}
	
	/**
	 * 获得一个对象的所有分类标签
	 */
	function getTag(array $args = array()){
		
		$this->db->from('object_tag')
			->join('tag_taxonomy','tag_taxonomy.id = object_tag.tag_taxonomy','inner')
			->join('tag','tag.id = tag_taxonomy.tag','inner')
			->where('object_tag.object', $this->id)
			->select('object_tag.*, tag.id tag, tag.name term, tag_taxonomy.taxonomy, tag_taxonomy.description, tag_taxonomy.parent, tag_taxonomy.count');
		
		if(array_key_exists('taxonomy', $args)){
			$this->db->where('tag_taxonomy.taxonomy', $args['taxonomy']);
		}
		
		$result = $this->db->get()->result();
		
		if(array_key_exists('as_rows', $args)){
			return $result;
		}
		
		$tags = array();
		
		foreach($result as $row){
			$tags[$row->taxonomy][] = $row->term;
		}
		
		return $tags;
	}
	
	/**
	 * 为一个对象设置分类标签
	 * @param array|string $tags 一个或多个分类值(tag.name)或分类值id(tag.id)
	 * @param string $taxonomy 分类
	 * @param bool $append 是否追加，false将用$tags重写此分类的值，否则保留原值
	 */
	function setTag($tags, $taxonomy, $append = false){
		
		if(!$tags){
			$tags = [];
		}
		
		if(!is_array($tags)){
			$tags = array($tags);
		}
		
		//如果并非追加，那么先删除此分类下不在此次添加之列的值
		if(!$append){
			
			$tags_origin = $this->getTag(array('as_rows'=>true, 'taxonomy'=>$taxonomy));
			
			if($tags_origin){
				foreach($tags_origin as $tag_origin){
					if(!in_array($tag_origin->term, $tags)/* && !in_array($tag_origin->tag, $tags)*/){
						$this->db->delete('object_tag', array('object'=>$this->id, 'tag_taxonomy'=>$tag_origin->tag_taxonomy));
					}
				}
			}
		}
		
		foreach($tags as $tag){
			$tag_taxonomy_id = /*is_integer($tag) ? $tag : */$this->tag->get($tag, $taxonomy);
			$query = $this->db->insert_string('object_tag', array('object'=>$this->id, 'tag_taxonomy'=>$tag_taxonomy_id, 'user'=>$this->user->session_id));
			$this->db->query(str_replace('INSERT', 'INSERT IGNORE', $query));
		}
		
	}
	
}
?>

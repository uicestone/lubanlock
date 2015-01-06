<?php
class Object extends CI_Model {
	
	var $id,
		$name, $type, $num, $company, $user, $time, $time_insert,
		$meta, $relative, $parents, $status, $tag, $permission;
	
	static $fields=array(
		'name'=>'',
		'type'=>'',
		'num'=>'',
		'company'=>null,
		'user'=>null,
		'time'=>null,
		'time_insert'=>null
	);
	
	/**
	 * create an Object instance by fetching from database, or create a new object in database
	 * @param $data
	 * @param array $args
	 *	get_data get_data from database when $data is not something for save but an id for retrieving an object, default is true
	 */
	function __construct($data = null, array $args = array()) {
		
		parent::__construct();

		if(!is_null($data)){
			
			if(is_array($data)){
				$data['id'] = $this->add($data);
			}
			else{
				$data = !array_key_exists('get_data', $args) || $args['get_data'] ? $this->get(intval($data), $args) : array('id'=>intval($data));
			}
			
			foreach(array_keys(get_object_vars($this)) as $property){
				$this->$property = array_key_exists($property, $data) ? $data[$property] : null;
			}

		}
	}
	
	/**
	 * 根据id获得单个对象，将属性保存到Object
	 * @param int $id
	 * @param array $args
	 *	with 请求的对象是否包含附加属性，默认包含6种附加属性
	 *	with_user_info
	 * @param bool $permission_check
	 * @return array array version of the object
	 */
	function get($id = null, array $args = array(), $permission_check = true){

		if(!is_null($id)){
			$this->id = intval($id);
		}

		if($permission_check && !$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		$this->db
			->from('object')
			->where(array(
				'object.id'=>$this->id,
				'object.company'=>get_instance()->company->id
			));
		
		$object = $this->db->get()->row_array();
		
		if(!$object){
			throw new Exception(lang('object') . ' ' . $this->id . ' ' . lang('not_found'), 404);
		}
		
		$object['id'] = intval($object['id']);
		$object['type'] = lang($object['type']);
		!is_null($object['user']) && $object['user'] = intval($object['user']);
		
		$properties = array('meta', 'relative', 'parents', 'status', 'tag', 'permission');
		
		foreach($properties as $field){
			
			$property_args = array_key_exists('with_all_prop', $args) ? $args['with_all_prop'] : true;
			
			if(array_key_exists('with_' . $field, $args)){
				$property_args = $args['with_' . $field];
			}
			
			if(array_key_exists('with', $args)){
				
				if(is_array($args['with'])){
					if(in_array($field, $args['with'], true)){// TODO why do we need this "true"???
						$property_args = true;
					}

					if(array_key_exists($field, $args['with'])){
						$property_args = $args['with'][$field];
					}
				}
				else{
					if(strpos($args['with'], ',') !== false){
						$property_args = explode(',', $args['with']);
					}else{
						$property_args = $args['with'];// 可以用 [ 'with' => true | false ] 来全部载入或者全部不载入属性
					}
				}
				
			}
			
			if($property_args){
				$object[$field] = call_user_func(array($this, 'get' . ucfirst($field)), is_array($property_args) ? $property_args : array());
			}
			
		}
		
		if(array_key_exists('with_user_info', $args) && $args['with_user_info']){
			$object['user'] = new User_model($object['user']);
		}
		
		foreach(array_keys(get_object_vars($this)) as $property){
			if(array_key_exists($property, $object)){
				$this->$property = $object[$property];
			}
		}
		
		return $object;

	}
	
	/**
	 * create a new object in database
	 * will also create properties if meta, status... field is defined
	 * @param array $data
	 * @return int insert id
	 */
	function add(array $data){
		
		$data['company'] = get_instance()->company->id;
		
		!array_key_exists('user', $data) && $data['user'] = $this->session->user_id;
		// TODO can add object for other owners now, would that be a problem?
		
		$data['time_insert'] = date('Y-m-d H:i:s');
		
		array_key_exists('type', $data) && $data['type'] = $this->lang->raw($data['type']);
		
		$this->db->insert('object', array_merge(self::$fields, array_intersect_key($data, self::$fields)));
		
		$this->id = $this->db->insert_id();
		
		foreach(array('meta'=>'addMetas', 'relative'=>'setRelatives', 'parents'=>'setParents', 'status'=>'addStatuses', 'tag'=>'addTags', 'permission'=>'authorize') as $property => $function){
			if(array_key_exists($property, $data)){
				call_user_func(array($this, $function), $data[$property], false);
			}
		}
		
		return $this->id;
	}
	
	/**
	 * update an object and save to database
	 * @param array $data
	 * @return int affected rows
	 */
	function update(array $data){

		$data = array_intersect_key($data, self::$fields);
		
		if(empty($data)){
			return;
		}
		
		array_key_exists('type', $data) && $data['type'] = $this->lang->raw($data['type']);
		
		// TODO this means a user can set his own object to someone else
		if(array_key_exists('user', $data) && !$this->allow('grant')){
			unset($data['user']);
		}
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$this->db->set($data)->where('id', $this->id)->update('object');
		
		return $this->db->affected_rows();
	}
	
	function remove(){
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$result = $this->db->where('id', $this->id)->delete('object');
		
		if($this->db->error() && strpos($this->db->error()['message'], 'Cannot delete or update a parent row: a foreign key constraint fails') === 0){
			$parents = $this->getParents(array('id_only'=>true));
			try{
				foreach($parents as $relation => $parent_ids){
					foreach($parent_ids as $parent_id){
						$parent = new Object($parent_id);
						$parent->removeRelative($relation, $this->id);
					}
				}
				$result = $this->db->where('id', $this->id)->delete('object');
			}catch(Exception $e){
				if($e->getMessage() === 'no_permission'){
					throw new Exception('no_permission_on_parent_object', 403);
				}
			}
		}
		
		return $result;
	}
	
	/**
	 * 判断一个对象对于一些用户或组来说是否具有某种权限
	 * 特别的：
	 *	若对象权限表中没有此对象，则所有用户有读权限
	 *	若对象权限表中没有此对象，且用户为对象创建者或用户就是对象本身，那么有所有权限
	 *	若用户roles包含'对象{type}-admin'，则有全部权限
	 *	若用户roles包含'对象{type}-viewer'，则有读取权限
	 *	若用户roles包含'对象{type}-editor'，则有写入权限
	 * @param string $permission	read | write | grant
	 * @param array|int $users	默认为$this->session->group_ids，即当前用户和递归所属组
	 * @return boolean
	 * @throws Exception	argument_error
	 */
	function allow($permission = 'read', $users = null){
		
		if(!in_array($permission, array('read', 'write', 'grant'))){
			throw new Exception('permission_name_error', 400);
		}
		
		$this->get($this->id, array('with'=>null), false);
		
		if(is_null($users)){
			if(is_array($this->session->user_roles) && in_array($this->type . '-admin', $this->session->user_roles)){
				return true;
			}
			if($permission === 'read' && is_array($this->session->user_roles) && in_array($this->type . '-viewer', $this->session->user_roles)){
				return true;
			}
			if(in_array($permission, array('read', 'write')) && is_array($this->session->user_roles) && in_array($this->type . '-editor', $this->session->user_roles)){
				return true;
			}
		}
		
		if(is_null($users)){
			$users = $this->session->group_ids;
		}
		
		if(!is_array($users)){
			$users = array($users);
		}
		
		$result = $this->db->from('object_permission')->where('object', $this->id)->get()->result_array();
		
		if($result){
			foreach($result as $row){
				if(in_array($row['user'], $users) && $row[$permission]){
					return true;
				}
			}
			return false;
		}
		elseif($permission === 'read'){
			return true;
		}
		elseif(in_array($this->user, $users) || (is_null($this->user) && in_array($this->id, $users))){
			return true;
		}
		return false;
	}
	
	/**
	 * 对某用户或组赋予/取消赋予一个对象某种权限
	 * @param array|string $permission
	 *	可选健包括array('read'=>true,'write'=>true,'grant'=>true)
	 *	为string时自动转换成array(string=>true)
	 *	另外有public和private 2个特殊值可选
	 * @param array|int $users	默认为$this->session->user_id，即当前用户
	 * @param boolean $permission_check 授权时是否检查当前用户的grant权限，此参数只允许在后端内部暴露
	 * @throws Exception no_permission_to_grant
	 */
	function authorize($permission = 'read', $users = null, $permission_check = true){
		
		if(is_array($permission) && array_is_numerical_index($permission)){
			
			$permissions = $permission;
			
			foreach($permissions as $permission){
				$permission = array_merge(array(
					'permission' => 'read',
					'users' => null,
				), $permission);

				extract($permission);

				$this->authorize($permission, $users, $permission_check);
			}
			
			return;
		}
		
		if($permission_check && !$this->allow('grant')){
			throw new Exception('no_permission_to_grant', 403);
		}
		
		if($permission === 'public'){
			$this->db->delete('object_permission', array('object'=>$this->id));
			return;
		}
		
		if($permission === 'private'){
			$users = null;
			$permission = array('read'=>true, 'write'=>true, 'grant'=>true);
		}
		
		if(!is_array($permission)){
			$permission = array($permission=>true);
		}
		
		$permission = array_intersect_key($permission, array('read'=>true, 'write'=>true, 'grant'=>true));
		
		if(array_key_exists('grant', $permission) && $permission['grant']){
			$permission['write'] = true;
		}
		
		if(array_key_exists('write', $permission) && $permission['write']){
			$permission['read'] = true;
		}
		
		if(is_null($users)){
			
			if(!$this->session->user_id){
				throw new Exception('user_not_logged_in', 403);
			}
			
			$users = array($this->session->user_id);
		}

		if(!is_array($users)){
			$users = array($users);
		}
		
		foreach($users as $user){
			$this->db->upsert('object_permission', array('user'=>$user, 'object'=>$this->id) + $permission);
		}
		
	}
	
	function _parse_criteria($args, $field='`object`.`id`', $logical_operator = 'AND'){
		
		if(!is_array($args)){
			if($args === 'ME'){
				$args = $this->session->user_id;
			}
			if($args === 'MY_GROUPS'){
				$args = $this->session->group_ids;
			}
			else{
				return $field.' = '.$this->db->escape($args);
			}
		}
		
		//如果参数数组不为空，且全是数字键，且全是值，则作in处理
		
		if(!empty($args)){
			
			$args_as_in = true;
			
			foreach($args as $arg_key => $arg_value){
				if(!is_integer($arg_key) || is_array($arg_value)){
					$args_as_in = false;
					break;
				}
			}
			
			$args_as_in && $args = array('in'=>$args);
		}
		
		$where = array();
		
		foreach($args as $arg_name => $arg_value){
			
			if($arg_name === 'and' || is_integer($arg_name)){
				$where[] = $this->_parse_criteria($arg_value, $field);
			}
			elseif($arg_name === 'or'){
				$where[] = $this->_parse_criteria($arg_value, $field, 'OR');
			}
			elseif($arg_name === 'gt'){
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
			elseif($arg_name === 'like'){
				$where[] = $field.' LIKE \'%'.$this->db->escape_like_str($arg_value) . '%\'';
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
			
			elseif($arg_name === 'is_relative_of' || $arg_name === 'parents'){
				if(is_array($arg_value)){
					foreach($arg_value as $relation => $relative_args){
						$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($this->lang->raw($relation)).' AND ';
						$where[] = "$field IN ( \nSELECT `relative` FROM `object_relationship` WHERE $relation_criteria".$this->_parse_criteria($relative_args, '`object_relationship`.`object`')." \n)";
					}
				}else{
					$where[] = "$field IN ( \nSELECT `relative` FROM `object_relationship` WHERE ".$this->_parse_criteria($arg_value, '`object_relationship`.`object`')." \n)";
				}
			}
			elseif($arg_name === 'has_relative_like' || $arg_name === 'children'){
				if(is_array($arg_value)){
					foreach($arg_value as $relation => $relative_args){
						$relation_criteria = is_integer($relation) ? '' : '`relation` = '.$this->db->escape($this->lang->raw($relation)).' AND ';
						$where[] = "$field IN ( \nSELECT `object` FROM `object_relationship` WHERE $relation_criteria".$this->_parse_criteria($relative_args, '`object_relationship`.`relative`')." \n)";
					}
				}else{
					$where[] = "$field IN ( \nSELECT `object` FROM `object_relationship` WHERE ".$this->_parse_criteria($arg_value, '`object_relationship`.`relative`')." \n)";
				}
			}
			
			elseif(in_array($arg_name,array('id','name','type','num','user','time','time_insert'))){
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
	 *		is_relative_of / parents
	 *			[role => ]recursive args
	 *		has_relative_like / children
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
	 *		found_rows boolean
	 *		human_time_format boolean
	 * @return array
	 */
	function query(array $args=array(), $permission_check = true){

		$this->db->from('object')->select('object.*');
		
		if(!array_key_exists('found_rows', $args) || $args['found_rows']){
			$this->db->found_rows();
		}
		
		$this->db->where('object.company', get_instance()->company->id);
		
		if(array_key_exists('search', $args)){
			
			$keywords = preg_split('/\s/', $args['search']);
			foreach($keywords as $keyword){
				$matches = array();
				preg_match('/(.*?)\:(.*)/', $keyword, $matches);
				
				if($matches){
					if(in_array($matches[1], array('name'))){
						$args['and'][] = array(
							$matches[1]=>array('like'=>$matches[2])
						);
					}
					if(in_array($matches[1], array('num', 'type'))){
						$args['and'][] = array(
							$matches[1]=>$matches[2]
						);
					}
					if(in_array($matches[1], array('meta', 'status', 'tag'))){
						$args['and'][] = array(
							$matches[1]=>array($matches[2])
						);
					}
					if(in_array($matches[1], array('relative', 'parents'))){
						$args['and'][] = array(
							$matches[1]=>array(array('name'=>array('like'=>$matches[2])))
						);
					}
				}
				else{
					$args['and'][] = array(
						'or'=>array(
							'name'=>array('like'=>$keyword),
							'num'=>$keyword,
							'type'=>$keyword,
						)
					);
				}
			}
		}
		
		$this->db->where($this->_parse_criteria($args), null, false);
		
		if($permission_check){
			// 不在object_permission中的对象被视为公共对象，所有访客可读
			$permission_condition = "\n".'`object`.`id` NOT IN ( SELECT `object` FROM `object_permission` )';

			// 若用户或所在组具有对象{type}-admin role，则具有全部权限
			if($this->session->user_roles){
				$permission_condition .= "\nOR `object`.`type` IN ('".implode("', '", array_map(function($role){return preg_replace('/-admin$/', '', $role);}, $this->session->user_roles))."')";
			}

			// 一般读权限检查
			if(is_array($this->session->group_ids) && !empty($this->session->group_ids)){
				$permission_condition .= "\n".'OR `object`.`id` IN ( SELECT `object` FROM `object_permission` WHERE `read` = TRUE AND `user` IN ( '.implode(', ',$this->session->group_ids).' ) )';
			}

			$this->db->where('( '.$permission_condition.' )', null, false);
		}
		
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
				$args['per_page'] = get_instance()->company->config('per_page');
				
				if(!$args['per_page']){
					$args['per_page'] = 25;
				}
			}
			//页码-每页数量方式，转换为sql limit
			$args['limit'] = array($args['per_page'], ($args['page'] - 1) * $args['per_page']);
		}
		
		if(!array_key_exists('limit', $args)){
			//默认limit
			$args['limit'] = get_instance()->company->config('per_page');
			
			if(is_null($args['limit'])){
				$args['limit'] = 25;
			}
		}
		
		if(is_array($args['limit'])){
			//sql limit方式
			call_user_func_array(array($this->db,'limit'), $args['limit']);
		}
		elseif(count(preg_split('/,\s*/',$args['limit'])) === 2){
			$args['limit'] = preg_split('/,\s*/', $args['limit']);
			call_user_func_array(array($this->db,'limit'), $args['limit']);
		}
		else{
			call_user_func(array($this->db, 'limit'), $args['limit']);
		}
		
		$result_array=$this->db->get()->result_array();
		
		if(!array_key_exists('found_rows', $args) || $args['found_rows']){
			$result = array('data'=>array(), 'info'=>array(
				'total'=>$this->db->query('SELECT FOUND_ROWS() rows')->row()->rows,
				'from'=>is_array($args['limit']) ? $args['limit'][1] + 1 : 1,
				'to'=>is_array($args['limit']) ? $args['limit'][0] + $args['limit'][1] : $args['limit']
			));
			
			if($result['info']['total'] == 0){
				$result['info']['from'] = 0;
			}

			if($result['info']['to'] > $result['info']['total']){
				$result['info']['to'] = $result['info']['total'];
			}
		}
		
		//获得四属性的参数，决定是否为对象列表获取属性
		foreach( array('meta', 'relative', 'parents', 'status', 'tag', 'permission') as $field ){
			
			$property_args = false;
			
			if(array_key_exists('with_' . $field, $args)){
				$property_args = $args['with_' . $field];
			}
			
			if(array_key_exists('with', $args)){
				
				if(is_array($args['with'])){
					if(in_array($field, $args['with'], true)){// TODO why do we need this "true"???
						$property_args = true;
					}

					if(array_key_exists($field, $args['with'])){
						$property_args = $args['with'][$field];
					}
				}
				else{
					if(strpos($args['with'], ',') !== false){
						$property_args = explode(',', $args['with']);
					}else{
						$property_args = $args['with'];// 可以用 [ 'with' => true | false ] 来全部载入或者全部不载入属性
					}
				}
				
			}
			
			if($property_args){
				foreach($result_array as &$row){
					$this->id = intval($row['id']);
					//参数值可以不是true而是一个数组，那样的话这个数组将被传递给get{property}()方法作为参数
					!is_array($property_args) && $property_args = array();
					$row[$field] = call_user_func(array($this, 'get'. $field), $property_args);
				}
			}
			
		}
		
		if(array_key_exists('with_user_info', $args) && $args['with_user_info']){
			array_walk($result_array, function(&$row){
				$row['user'] = new User_model($row['user'], array('with'=>false));
			});
		}
		
		array_walk($result_array, function(&$row) use($args){
			$row['id'] = intval($row['id']);
			$row['type'] = lang($row['type']);
			$row['time_human'] = str_replace(' ', '', timespan(strtotime($row['time']), time(), 1) . lang('before'));
			$row['time_insert_human'] = str_replace(' ', '', timespan(strtotime($row['time_insert']), time(), 1) . lang('before'));
		});

		if(array_key_exists('limit', $args) && $args['limit'] === 1){
			return $result_array ? $result_array[0] : null;
		}

		if(!array_key_exists('found_rows', $args) || $args['found_rows']){
			$result['data'] = $result_array;
			return $result;
		}
		
		return $result_array;
		
	}
	
	
	function getArray(array $args=array(),$keyname='name',$keyname_forkey='id'){
		return array_column($this->query($args),$keyname,$keyname_forkey);
	}
	
	function getRow(array $args = array()){
		!array_key_exists('limit', $args) && $args['limit']=1;
		return $this->query($args);
	}
	
	function getTypes()
	{
		$this->db->select('type value, COUNT(*) count')
				->from('object')
				->group_by('type')
				->order_by('COUNT(*) DESC');
		
		$result = $this->db->get()->result();
		
		foreach($result as &$item){
			$item->count = intval($item->count);
		}
		
		return $result;
	}
	
	/**
	 * @param array $args
	 *	with_user_info: whether we return user info, if is false, only user id is returned. default is false
	 */
	function getPermission(array $args = array()){
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		$result = $this->db->from('object_permission')->where('object', $this->id)->get()->result();
		
		$permission = array('read'=>array(), 'write'=>array(), 'grant'=>array());
		
		foreach($result as $row){
			foreach(array('read', 'write', 'grant') as $type){
				$row->$type && $permission[$type][] = empty($args['with_user_info']) ? $row->user : new User_model($row->user, array('with'=>null));
			}
		}
		
		return $permission;
	}
	
	/**
	 * 返回一个对象的资料项列表
	 * $args array
	 *	visibility
	 *	raw_key_name bool
	 * @return array
	 */
	function getMeta(array $args = array()){
		
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		if(!array_key_exists('visibility', $args)){
			$args['visibility'] = 1;
		}
		
		$this->db->select('object_meta.*')
			->from('object_meta')
			->where("`object_meta`.`object`", $this->id)
			->order_by('`object_meta`.`time`');
		
		$this->db->where($this->_parse_criteria($args['visibility'], '`object_meta`.`visibility`'));
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args)){
			return $result;
		}
		
		$this->meta = null;
		
		foreach($result as $row){
			if(!array_key_exists('raw_key_name', $args) || !$args['raw_key_name']){
				$row['key'] = $this->lang->line($row['key']);
			}
			$this->meta[$row['key']][] = $row['value'];
		}
		
		return $this->meta;
		
	}
	
	function getMetaKeys(array $args = array()){
		
		$this->db->from('object_meta')
			->where('`object_meta`.`object` IN ( SELECT id FROM `object` WHERE `company` = ' . $this->session->company_id . ' AND ' . $this->_parse_criteria($args) . ' )', null, false)
			->group_by('object_meta.key')
			->order_by('COUNT(*) DESC')
			->select('key, COUNT(*) count');
			
		$result = $this->db->get()->result();
		
		foreach($result as &$item){
			$item->count = intval($item->count);
		}
		
		return $result;
		
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
	function addMeta($key, $value = null, $unique = false, $visibility = 1, $check_permission = true){
		
		if(is_null($value)){
			return;
		}
		
		if(is_object($value) || is_array($value)){
			$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		}
		
		if($check_permission && !$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$metas = $this->getMeta();
		
		$key = $this->lang->raw($key);
		
		if($unique){
			if(is_array($metas) && array_key_exists($key, $metas)){
				throw new Exception('duplicated_meta_key', 400);
			}
		}
		
		if(is_array($metas) && array_key_exists($key, $metas) && in_array($value, $metas[$key])){
			throw new Exception('duplicated_meta_key_value', 400);
		}
		
		$this->db->insert('object_meta', array(
			'object'=>$this->id,
			'key'=>$key,
			'value'=>$value,
			'visibility'=>$visibility,
			'user'=>$this->session->user_id
		));
		
		$meta_id = $this->db->insert_id();
		
		return $meta_id;
	}
	
	function addMetas(array $data, $check_permission = true){
		
		foreach($data as $sub_key => $sub_func_args){
			try{
				if(is_array($sub_func_args)){

					$sub_func_args = array_merge(array(
						'key' => null,
						'value' => null,
						'unique' => false,
						'visibility' => 1
					), $sub_func_args);

					extract($sub_func_args);

					$this->addMeta($key, $value, $unique, $visibility, $check_permission);
				}
				else{
					$this->addMeta($sub_key, $sub_func_args, false, 1, $check_permission);
				}
			}catch(Exception $e){
				//TODO不中断程序的错误应该也有地方输出错误信息
				continue;
			}
		}
		
	}
	
	/**
	 * 更新对象元数据
	 * 首先检查键名是否存在，如果不存在则执行addMeta()
	 * @param string $key
	 * @param string $value
	 * @param string $prev_value optional 如果不为null，则只更新原来值为$prev_value的记录
	 * @return boolean
	 */
	function updateMeta($key, $value, $prev_value = null, $visibility = 1){
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$metas = $this->getMeta();
		
		if(is_object($prev_value) || is_array($prev_value)){
			$prev_value = json_encode($prev_value, JSON_UNESCAPED_UNICODE);
		}
		
		if(is_object($value) || is_array($value)){
			$value = json_encode($value, JSON_UNESCAPED_UNICODE);
		}
		
		$key = $this->lang->raw($key);
		
		if(!array_key_exists($key, $metas)){
			return $this->addMeta($key, $value);
		}
		
		if(array_key_exists($key, $metas) && in_array($value, $metas[$key])){
			throw new Exception('duplicated_meta_key_value', 400);
		}
		
		$condition = array('object'=>$this->id, 'key'=>$key);
		
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
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$condition = array('key'=>$key);
		
		if(!is_null($value)){
			if(is_object($value) || is_array($value)){
				$value = json_encode($value, JSON_UNESCAPED_UNICODE);
			}
			$condition += array('value'=>$value);
		}
		
		return $this->db->delete('object_meta', $condition);
	}
	
	/**
	 * 
	 * @param array $args
	 *	as_rows retrieve rows in object_relative directly
	 *	id_only retrieve just relative id
	 *	include_disabled include relationships which "is_on" is not true
	 *	with_relationship_meta default:true retrieve meta of relationship (stored in object_relationship_meta)
	 *	order_by available columns are relationship num, time
	 *	limit limit the result rows, default is 25
	 *	get_parents if is true, we are fetching left object in object_relative table, otherwise right one is fetched
	 *	is_user the relative object should exist in user table
	 *	is_group the relative object should exist in user table and "is_group" is not 0
	 *	raw_key_name bool|array retrieve some or all relation name without translating
	 * @return array
	 */
	function getRelative(array $args = array()){
		
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
		if(array_key_exists('get_parents', $args) && $args['get_parents']){
			$get = 'object'; $by = 'relative'; $save_as = &$this->parents;
		}
		else{
			$get = 'relative'; $by = 'object'; $save_as = &$this->relative;
		}
		
		$this->db
			->from('object_relationship')
			->where('object_relationship.' . $by, $this->id);
		
		if(array_key_exists('relation', $args)){
			$this->db->where('object_relationship.relation', $args['relation']);
		}
		
		if(!array_key_exists('include_disabled', $args) || !$args['include_disabled']){
			$this->db->where('object_relationship.is_on', true);
		}
		
		if(array_key_exists('is_user', $args) && $args['is_user']){
			$this->db->join('user', 'object_relationship.' . $get . ' = user.id', 'inner');
		}
		
		if(array_key_exists('is_group', $args) && $args['is_group']){
			$args['is_user'] = true;
			$this->db->join('user', 'object_relationship.' . $get . ' = user.id AND user.is_group > 0', 'inner');
		}
		
		if(array_key_exists('order_by', $args)){
			$this->db->order_by($args['order_by']);
		}
		
		if(!array_key_exists('limit', $args)){
			//默认limit
			$args['limit'] = get_instance()->company->config('relatives_per_page');
			
			if(is_null($args['limit'])){
				$args['limit'] = false;
			}
		}
		
		if(is_array($args['limit'])){
			//sql limit方式
			call_user_func_array(array($this->db,'limit'), $args['limit']);
		}
		elseif(count(preg_split('/,\s*/',$args['limit'])) === 2){
			$args['limit'] = preg_split('/,\s*/', $args['limit']);
			call_user_func_array(array($this->db,'limit'), $args['limit']);
		}
		else{
			call_user_func(array($this->db, 'limit'), $args['limit']);
		}
		
		$result = $this->db->get()->result_array();
		
		if(array_key_exists('as_rows', $args) && $args['as_rows']){
			return $result;
		}
		
		$save_as = null;
		
		!array_key_exists('raw_key_name', $args) && $args['raw_key_name'] = false;
		
		foreach($result as $relationship){
			
			if(
				(is_array($args['raw_key_name']) && !in_array($relationship['relation'], $args['raw_key_name']))
				||
				(!is_array($args['raw_key_name']) && !$args['raw_key_name'])
			){
				$relationship['relation'] = $this->lang->line($relationship['relation']);
			}

			if(array_key_exists('id_only', $args) && $args['id_only']){
				$save_as[$relationship['relation']][] = $relationship[$get];
			}
			else{
				try{
					
					$relative = (array_key_exists('is_user', $args) && $args['is_user']) ? new User_model($relationship[$get], array_merge($args, array('with_all_prop'=>false))) : new Object($relationship[$get], array_merge($args, array('with_all_prop'=>false)));
					$relative->relationship_id = (int) $relationship['id'];
					$relative->relationship_num = $relationship['num'];
					$relative->relationship_is_on = (bool) $relationship['is_on'];
					$relative->relationship_visibility = $relationship['visibility'];
					
					if(!array_key_exists('with_relationship_meta', $args) || $args['with_relationship_meta']){
						$relative->relationship_meta = $relative->getRelativeMeta($relationship['id']);
					}
					$save_as[$relationship['relation']][] = (array) $relative;
					
				}catch(Exception $e){}
			}
			
		}
		
		return $save_as;
		
	}
	
	function getParents(array $args = array()){
		$args['get_parents'] = true;
		return $this->getRelative($args);
	}
	
	/**
	 * 为一个对象添加一个或多个关联对象
	 * @param string $relation 关系，不能为整数，否则将被转换为空字符串
	 * @param string $relative 关联对象id
	 * @param string $num optional, 关系的编号
	 * @param bool $is_on 是否启用关系，若为false，此关系虽然被保存，但默认情况不会被获取
	 * @param array $args
	 *	replace_meta bool 是否删除不在meta参数内的relationship meta
	 *	replace_id int 存储关联对象前要删除的关联对象
	 *	as_parent
	 * @return int|array new meta id(s)
	 * @throws Exception
	 */
	function setRelative($relation, $relative = null, $num = '', array $meta = array(), $is_on = true, array $args = array(), $check_permission = true){
		
		if(is_null($relative)){
			return;
		}
		
		!array_key_exists('as_parent', $args) && $args['as_parent'] = false;
		
		if($check_permission){
			if(!$args['as_parent']){
				if(!$this->allow('write')){
					throw new Exception('no_permission', 403);
				}
			}
			else{
				$parent = new Object($relative, array('with'=>null));
				if(!$parent->allow('write')){
					throw new Exception('no_permission', 403);
				}
			}
		}
		
		if($args['as_parent']){
			$set = 'object'; $by = 'relative';
		}else{
			$set = 'relative'; $by = 'object';
		}
		
		try{
			new Object($relative, array('with'=>null));
		}
		catch(Exception $e){
			throw new Exception(lang('invalid_relative') . ': ' . lang($e->getMessage()), 400);
		}
		
		if(array_key_exists('replace_id', $args)){
			$this->db->delete('object_relationship', array(
				$by=>$this->id,
				$set=>$args['replace_id'],
				'relation'=>$this->lang->raw($relation)
			));
		}
		
		$return = $this->db->upsert('object_relationship', array(
			$by=>$this->id,
			$set=>$relative,
			'relation'=>$this->lang->raw($relation),
			'num'=>$num,
			'is_on'=>$is_on,
			'user'=>$this->session->user_id
		));
		
		// 根据参数，先删除不在此次添加之列的键值对
		if(array_key_exists('replace_meta', $args) && $args['replace_meta']){
			
			$meta_origin = $this->getRelativeMeta($this->lang->raw($relation), $relative);
			
			foreach($meta_origin as $key => $value){
				if(!array_key_exists($key, $meta)){
					$this->removeRelativeMeta($this->lang->raw($relation), $relative, $key);
				}
			}
		}
		
		foreach($meta as $key => $value){
			$this->setRelativeMeta($this->lang->raw($relation), $relative, $key, $value);
		}
		
		return $return;
	}
	
	function setParent($relation, $relative = null, $num = '', array $meta = array(), $is_on = true, array $args = array(), $check_permission = true){
		$args['as_parent'] = true;
		return $this->setRelative($relation, $relative, $num, $meta, $is_on, $args, $check_permission);
	}
	
	function setRelatives(array $data, $as_parent = false, $check_permission = true){
			
		$relationship_ids = array();

		foreach($data as $key => $sub_func_args){
			if(is_array($sub_func_args)){
				if(array_key_exists('relative', $sub_func_args)){
					// $sub_func_args is treated as a relationship data row
					$sub_func_args = array_merge(array(
						'relation' => '',
						'relative' => null,
						'num' => '',
						'meta' => array(),
						'is_on' => true,
						'args' => array(),
					), $sub_func_args);

					extract($sub_func_args);
					
					$as_parent && $args['as_parent'] = true;
					
					$relationship_ids[] = $this->setRelative($relation, $relative, $num, $meta, $is_on, $args, $check_permission);
				}
				else{
					// $sub_func_args is treated as an array of relative IDs
					foreach($sub_func_args as $relative){
						$args = $as_parent ? array('as_parent'=>true) : array();
						$relationship_ids[] = $this->setRelative($key, $relative, '', array(), true, $args, $check_permission);
					}
				}
			}
			else{
				$relation = $key;
				$args = $as_parent ? array('as_parent'=>true) : array();
				$relationship_ids[] = $this->setRelative($relation, $sub_func_args, '', array(), true, $args, $check_permission);
			}
		}

		return $relationship_ids;
			
	}
	
	function setParents(array $data, $check_permission = true){
		return $this->setRelatives($data, true, $check_permission);
	}
	
	function removeRelative($relation, $relative){
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		return $this->db->delete('object_relationship', array('object'=>$this->id, 'relation'=>$this->lang->raw($relation), 'relative'=>$relative));
	}
	
	function _getRelationshipID($relation, $relative){
		
		$relationship = $this->db->from('object_relationship')->where(array('object'=>$this->id, 'relative'=>$relative, 'relation'=>$relation, 'is_on'=>true))->get()->row();
		
		if(!$relationship){
			throw new Exception('relationship_not_exist', 500);
		}
		
		return $relationship->id;
	}
	
	function getRelativeMeta($relation, $relative = null){
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		$relationship_id = is_null($relative) ? $relation : $this->_getRelationshipID($relation, $relative);
		$result = $this->db->from('object_relationship_meta')->where('relationship', $relationship_id)->get()->result_array();
		return $result ? array_column($result, 'value', 'key') : null;
	}
	
	function setRelativeMeta($relation, $relative, $key, $value){
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$relationship_id = is_null($relative) ? $relation : $this->_getRelationshipID($relation, $relative);
		if(is_null($value)){
			return $this->db->delete('object_relationship_meta', array('relationship'=>$relationship_id, 'key'=>$key));
		}
		else{
			return $this->db->upsert('object_relationship_meta', array('relationship'=>$relationship_id, 'key'=>$key, 'value'=>$value, 'user'=>$this->session->user_id));
		}
	}
	
	function removeRelativeMeta($relation, $relative, $key){
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		$relationship_id = is_null($relative) ? $relation : $this->_getRelationshipID($relation, $relative);
		return $this->db->delete('object_relationship_meta', array('relationship'=>$relationship_id, 'key'=>$key));
	}
	
	/**
	 * 获得对象的当前状态或者状态列表
	 * @property array $args
	 *	as_rows bool default: true 对象属性是无序的，需要有序序列时，将本参数设置为true来获得一个数组
	 * $return array|object
	 */
	function getStatus(array $args = array()){
		
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
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
		
		if(!array_key_exists('as_rows', $args) || $args['as_rows']){
			return $result;
		}
		
		$this->status = null;
		
		foreach($result as $row){
			$this->status[$row['name']][] = $row['date'];
		}
		
		return $this->status;
		
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

	function addStatus($name, $date = null, $comment = null, $check_permission = true){
		
		if($check_permission && !$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		$this->db->insert('object_status',array(
			'object'=>$this->id,
			'name'=>$name,
			'date'=>$this->_parse_date($date),
			'comment'=>$comment,
			'user'=>$this->session->user_id
		));
		
		return $this->db->insert_id();
	}
	
	function addStatuses(array $data, $check_permission = true){
			
		foreach($data as $sub_name => $sub_func_args){
			if(is_array($sub_func_args)){

				$sub_func_args = array_merge(array(
					'name' => '',
					'date' => null,
					'comment' => null,
				), $sub_func_args);

				extract($sub_func_args);

				$this->addStatus($name, $date, $comment, $check_permission);
			}
			else{
				$this->addStatus($sub_name, $sub_func_args, null, $check_permission);
			}
		}

	}
	
	/**
	 * 更新对象状态
	 * @param string $name 要更新的状态名
	 * @param string|int $date 新的日期
	 * @param string $comment 新的备注
	 * @param string|int $prev_date 为null则更新日期最新一条名称为$name的状态，否则更新名称为$name且日期为$prev_date的状态
	 */
	function updateStatus($name, $date = null, $comment = null, $prev_date = null){
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
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
		
		if(!$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
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
	 * @return array
	 */
	function getTag(array $args = array()){
		
		if(!$this->allow()){
			throw new Exception('no_permission', 403);
		}
		
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
		
		$this->tag = null;
		
		foreach($result as $row){
			$this->tag[$row->taxonomy][] = $row->term;
		}
		
		return $this->tag;
	}
	
	/**
	 * 为一个对象设置分类标签
	 * @param array|string $tags 一个或多个分类值 (tag.name) 或一组分类和分类值的键值对
	 * @param string $taxonomy 分类
	 * @param bool $append 是否追加，false将用$tags重写此分类的值，否则保留原值
	 */
	function setTag($tags, $taxonomy = null, $append = false, $check_permission = true){
		
		if($check_permission && !$this->allow('write')){
			throw new Exception('no_permission', 403);
		}
		
		if(!$tags){
			$tags = array();
		}
		
		//处理一个标签值的情况
		if(!is_array($tags)){
			$tags = array($tags);
		}
		
		//如果并非追加，那么先删除此分类下不在此次添加之列的值
		if(!$append){
			
			$tags_origin = $this->getTag(array('as_rows'=>true, 'taxonomy'=>$taxonomy));
			
			foreach($tags_origin as $tag_origin){
				if(!in_array($tag_origin->term, $tags)){
					$this->db->delete('object_tag', array('object'=>$this->id, 'tag_taxonomy'=>$tag_origin->tag_taxonomy));
					$this->db->where('id', $tag_origin->tag_taxonomy)->set('count', '`count` - 1', false)->update('tag_taxonomy');
				}
			}
		}
		
		foreach($tags as $index => $tag){
			
			if(!is_integer($index)){
				$taxonomy = $index;
			}
			
			$tag_taxonomy_id = get_instance()->tag->get($tag, $taxonomy);
			$query = $this->db->insert_string('object_tag', array('object'=>$this->id, 'tag_taxonomy'=>$tag_taxonomy_id, 'user'=>$this->session->user_id));
			$this->db->query(str_replace('INSERT', 'INSERT IGNORE', $query));
			if($this->db->affected_rows() === 1){
				$this->db->where('id', $tag_taxonomy_id)->set('count', '`count` + 1', false)->update('tag_taxonomy');
			}
		}
		
	}
	
	function setTags(array $data, $check_permission = true){
		foreach($data as $taxonomy => $tags){
			$this->setTag($tags, $taxonomy, false, $check_permission);
		}
	}
	
	function addTags(array $data, $check_permission = true){
		foreach($data as $taxonomy => $tags){
			$this->setTag($tags, $taxonomy, true, $check_permission);
		}
	}
	
}
?>

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
	
	/**
	 * 
	 * @param array $args
	 *	id_in
	 *	id_less_than
	 *	id_greater_than
	 *	name
	 *	type
	 *	num
	 *	display
	 *	company
	 *	uid
	 * 
	 *	tags array
	 *	without_tags array
	 *	with_tags
	 * 
	 *	meta
	 *		array('电话','来源'=>'网站')
	 *		以上例子将搜素'来源'为'网站'并且有'电话'的对象
	 *	with_meta array
	 * 
	 *	is_relative_of =>user_id object_relationship  根据本对象获得相关对象
	 *		is_relative_of__role
	 *	has_relative_like => user_id object_relationship  根据相关对象获得本对象
	 *		has_relative_like__role
	 *	is_secondary_relative_of 右侧相关对象的右侧相关对象，“下属的下属”
	 *		is_secondary_relative_of__media
	 *	is_both_relative_with 右侧相关对象的左侧相关对象，“具有共同上司的同事”
	 *		is_both_relative_with__media
	 *	has_common_relative_with 左侧相关对象的右侧相关对象，“具有共同下属的上司”
	 *		has_common_relative_with__media
	 *	has_secondary_relative_like 左侧相关对象的左侧相关对象，“上司的上司”
	 *		has_secondary_relative_like__media
	 * 
	 *	status
	 *		array(
	 *			'status_name'=>array('from'=>'from_syntax','to'=>'to_syntax','format'=>'timestamp/date/datetime')/bool
	 *			'首次接洽'=>array('from'=>1300000000,'to'=>1300100000,'format'=>'timestamp'),
	 *			'立案'=>array('from'=>'2013-01-01','to'=>'2013-06-30'),
	 *			'结案'=>true
	 *		)
	 * 
	 *	orderby string or array
	 *	limit string, array
	 * @return array
	 */
	function getList(array $args=array()){

		$this->db->found_rows();
		
		if(!$this->db->ar_select){
			$this->db->select('object.*');
		}
		
		$this->db->from('object');
		
		if($this->table!=='object'){
			$this->db->select("{$this->table}.*")->join($this->table,"object.id = {$this->table}.id",'inner');
		}
		
		//对具体object表的join需要放在其他join前面
		if($this->db->ar_join){
			array_unshift($this->db->ar_join,array_pop($this->db->ar_join));
		}
		
		if(array_key_exists('name',$args)){
			$this->db->like('object.name',$args['name']);
		}
		
		if(array_key_exists('id_in',$args)){
			if(!$args['id_in']){
				$this->db->where('FALSE',NULL,false);
			}else{
				$this->db->where_in('object.id',$args['id_in']);
			}
		}
		
		if(array_key_exists('id_less_than',$args)){
			$this->db->where('object.id <',$args['id_less_than']);
		}
		
		if(array_key_exists('id_greater_than',$args)){
			$this->db->where('object.id >',$args['id_greater_than']);
		}
		
		if(array_key_exists('type',$args) && $args['type']){
			$this->db->where('object.type',$args['type']);
		}
		
		if(array_key_exists('num',$args)){
			$this->db->like('object.num',$args['num']);
		}
		
		if(!array_key_exists('display',$args) || $args['display']===true){
			$this->db->where('object.display',true);
		}

		if(!array_key_exists('company',$args) || $args['company']===true){
			$this->db->where('object.company',$this->company->id);
		}
		
		if(array_key_exists('uid',$args) && $args['uid']){
			$this->db->where('object.uid',$args['uid']);
		}
		
		//使用INNER JOIN的方式来筛选标签，聪明又机灵。//TODO 总觉得哪里不对- -||
		if(array_key_exists('tags',$args) && is_array($args['tags'])){
			foreach($args['tags'] as $id => $tag_name){
				//每次连接object_tag表需要定一个唯一的名字
				$on="object.id = `t_$id`.object AND `t_$id`.tag_name = {$this->db->escape($tag_name)}";
				if(!is_integer($id)){
					$on.=" AND `t_$id`.type = {$this->db->escape($id)}";
				}
				$this->db->join("object_tag `t_$id`",$on,'inner',false);
			}
		}
		
		if(array_key_exists('without_tags',$args)){
			foreach($args['without_tags'] as $id => $tag_name){
				$query_with="SELECT object FROM object_tag WHERE tag_name = {$this->db->escape($tag_name)}";
				if(!is_integer($id)){
					$query_with.=" AND type = {$this->db->escape($id)}";
				}
				$where="object.id NOT IN ($query_with)";
				$this->db->where($where, NULL, false);
			}
		}
		
		if(array_key_exists('mod', $args)){
			$positive=$negative=0;
			foreach($args['mod'] as $mod_name => $status){
					
				if(!array_key_exists($mod_name, $this->mod_list)){
					log_message('error','mod name not found: '.$mod_name);
					continue;
				}

				$mod=$this->mod_list[$mod_name];
				$status?($positive|=$mod):($negative|=$mod);
			}
			
			$this->db
				->join('object_mod',"object_mod.object = object.id AND object_mod.user = {$this->user->id}",'inner')
				->where("object_mod.mod & $positive = $positive AND object_mod.mod & $negative = 0",NULL,false);
		}
		
		if(array_key_exists('meta',$args) && is_array($args['meta'])){
			foreach($args['meta'] as $key => $value){
				$key=$this->db->escape($key);
				$value=$this->db->escape($value);

				if(is_integer($key)){
					$this->db->where("object.id IN (SELECT object FROM object_meta WHERE `key` = $value)");
				}
				else{
					$this->db->where("object.id IN (SELECT object FROM object_meta WHERE `key` = $key AND `value` = $value)");
				}
			}
		}
		
		if(array_key_exists('is_relative_of',$args)){
			
			$on="object.id = object_relationship__is_relative_of.relative AND object_relationship__is_relative_of.object{$this->db->escape_int_array($args['is_relative_of'])}";
			
			if(array_key_exists('is_relative_of__role',$args)){
				$on.=" object_relationship__is_relative_of.role = {$this->db->escape($args['is_relative_of__role'])}";
			}
			
			$this->db->join('object_relationship object_relationship__is_relative_of',$on,'inner',false)
				->select('object_relationship__is_relative_of.id relationship_id, object_relationship__is_relative_of.relation, object_relationship__is_relative_of.time relationship_time');
			
		}

		if(array_key_exists('has_relative_like',$args)){
			
			$on="object.id = object_relationship__has_relative_like.object AND object_relationship__has_relative_like.relative{$this->db->escape_int_array($args['has_relative_like'])}";
			
			if(array_key_exists('has_relative_like__role',$args)){
				$on.=" object_relationship__has_relative_like.role = {$this->db->escape($args['has_relative_like__role'])}";
			}
			
			$this->db->join('object_relationship object_relationship__has_relative_like',$on,'inner',false)
				->select('object_relationship__has_relative_like.id relationship_id, object_relationship__has_relative_like.relation, object_relationship__has_relative_like.time relationship_time');
		}
		
		if(array_key_exists('is_secondary_relative_of',$args)){
			$this->db->where("object.id IN (
				SELECT relative FROM object_relative WHERE object IN (
					SELECT relative FROM object_relative
					".(empty($args['is_secondary_relative_of__media'])?'':" INNER JOIN `{$args['is_secondary_relative_of__media']}` ON `{$args['is_secondary_relative_of__media']}`.id = object_relationship.relative")."
					WHERE object{$this->db->escape_int_array($args['is_secondary_relative_of'])}
				)
			)");
		}

		if(array_key_exists('is_both_relative_with',$args)){
			$this->db->where("object.id IN (
				SELECT relative FROM object_relative WHERE object IN (
					SELECT object FROM object_relative
					".(empty($args['is_both_relative_with__media'])?'':" INNER JOIN `{$args['is_both_relative_with__media']}` ON `{$args['is_both_relative_with__media']}`.id = object_relationship.object")."
					WHERE relative{$this->db->escape_int_array($args['is_both_relative_with'])}
				)
			)");
		}

		if(array_key_exists('has_common_relative_with',$args)){
			$this->db->where("object.id IN (
				SELECT object FROM object_relative WHERE relative IN (
					SELECT relative FROM object_relative
					".(empty($args['has_common_relative_with__media'])?'':" INNER JOIN `{$args['has_common_relative_with__media']}` ON `{$args['has_common_relative_with__media']}`.id = object_relationship.relative")."
					WHERE object{$this->db->escape_int_array($args['has_common_relative_with'])}
				)
			)");
		}

		if(array_key_exists('has_secondary_relative_like',$args)){
			$this->db->where("object.id IN (
				SELECT object FROM object_relative WHERE relative IN (
					SELECT object FROM object_relative
					".(empty($args['has_secondary_relative_like__media'])?'':" INNER JOIN `{$args['has_secondary_relative_like__media']}` ON `{$args['has_secondary_relative_like__media']}`.id = object_relationship.object")."
					WHERE relative{$this->db->escape_int_array($args['has_secondary_relative_like'])}
				)
			)");
		}
		
		$args['status']=array_prefix($args,'status');
		if($args['status']){
			
			$args['status']=array_merge($args['status'], array_prefix($args['status'], '.*?', true));
			
			foreach($args['status'] as $status_name => $status){
				
				if(array_key_exists('from', $status)){
					
					if(strtotime($status['from'])){
						$status['from']=strtotime($status['from']);
					}
					
					$this->db->join("object_status `{$status_name}_from`","`{$status_name}_from`.object = object.id AND `{$status_name}_from`.name = '$status_name' AND UNIX_TIMESTAMP(`{$status_name}_from`.date) >= {$status['from']}",'inner',false);
				}
				
				if(array_key_exists('to', $status)){
					
					if(strtotime($status['to'])){
						$status['to']=strtotime($status['to']);
					}
					
					$this->db->join("object_status `{$status_name}_to`","`{$status_name}_to`.object = object.id AND `{$status_name}_to`.name = '$status_name' AND UNIX_TIMESTAMP(`{$status_name}_to`.date) < {$status['to']}",'inner',false);
				}
			}
		}
		
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

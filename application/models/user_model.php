<?php
class User_model extends Object_model{
	
	var $name = '';
	var $roles = array();
	var $groups = array();
	var $group_ids = array();//当前用户所属组的object id，包含当前用户的user id
	
	static $fields=array(
		'name'=>'',
		'email'=>'',
		'alias'=>NULL,//别名
		'password'=>'',//密码
		'roles'=>''
	);
	
	function __construct(){
		parent::__construct();
	}
	
	function initialize($id=NULL){
		isset($id) && $this->id=$id;
		
		if(is_null($this->id) && $this->session->userdata('user_id')){
			$this->id=intval($this->session->userdata('user_id'));
		}
		
		if(!$this->id){
			return;
		}
		
		$user=$this->fetch($this->id, array(), false);
		$this->name=$user['name'];
		$user['roles'] && $this->roles=explode(',',$user['roles']);
		array_push($this->group_ids, $this->id);
		
		function get_parent_group($children = array(), &$object){
			
			$parents = $object->getList(array(
				'type'=>'group',
				'has_relative_like'=>array($children)
			));
			
			$object->groups = array_merge($object->groups, $parents['data']);
			$parent_group_ids = array_column($parents['data'], 'id');
			$object->group_ids = array_merge($object->group_ids, $parent_group_ids);
			
			if(empty($parent_group_ids)){
				return;
			}
		
			get_parent_group($parent_group_ids, $object);
		}
		
		get_parent_group(array($this->id), $this);
		
		$groups = $this->groups;
		$this->groups = array();
		
		foreach($groups as $value){
			$this->groups[$value['id']] = $value;
		}
		
		$this->config->user=$this->config();
	}
	
	function fetch($id=null, $args = array(), $permission_check = true){
		$object = parent::fetch($id, $args, $permission_check);
		$user = $this->db->from('user')->where('id', $id)->get()->row_array();
		return array_merge($object, $user);
	}
	
	function getList(array $args=array()){
		
		$this->db->join('user','user.id = object.id','inner');
		
		return parent::getList($args);
	}
	
	function add(array $data){
		
		$data['type'] = 'user';
		
		$insert_id=parent::add($data);

		$data=array_merge(self::$fields,array_intersect_key($data,self::$fields));
		
		$data['id']=$insert_id;
		$data['company']=$this->company->id;

		$this->db->insert('user',$data);
		
		return $insert_id;
	}
	
	function verify($username,$password){
		
		$username=$this->db->escape($username);
		
		$this->db
			->from('user')
			->where('company',$this->company->id)
			->where("(name = $username OR alias = $username)",NULL,false)
			->where('password',$password);
				
		$user=$this->db->get()->row_array();
		
		if(!$user){
			throw new Exception('login_info_error', 403);
		}
		
		return $user;
	}
	
	function updateLoginTime(){
		$this->db->update('user',
			array(
				'last_ip'=>$this->session->userdata('ip_address'),
				'last_login'=>date()
			),
			array('id'=>$this->id)
		);
	}
	
	function updatePassword($user_id,$new_password){
		
		return $this->db->update('user',array('password'=>$new_password),array('id'=>$user_id));
		
	}
	
	function updateUsername($user_id,$new_username){
		return $this->db->update('user',array('name'=>$new_username),array('id'=>$user_id));
	}
	
	/**
	 * 根据uid直接为其设置登录状态
	 */
	function sessionLogin($uid){
		$this->db->select('user.*')
			->from('user')
			->where('user.id',$uid);

		$user=$this->db->get()->row_array();
		
		if($user){
			$this->initialize($user['id']);
			$this->session->set_userdata('user_id', $user['id']);
			return true;
		}
		
		return false;
	}

	/**
	 * 登出当前用户
	 */
	function sessionLogout(){
		$this->session->sess_destroy();
	}

	/**
	 * 判断是否以某用户组登录
	 * $check_type要检查的用户组,NULL表示只检查是否登录
	 */
	function isLogged($group=NULL){
		if(is_null($group)){
			if(empty($this->id)){
				return false;
			}
		}elseif(empty($this->roles) || !in_array($group,$this->roles)){
			return false;
		}

		return true;
	}
	
	/**
	 * set or get a user config value
	 * or get all config values of a user
	 * json_decode/encode automatically
	 * @param string $key
	 * @param mixed $value
	 */
	function config($key=NULL,$value=NULL){
		
		if(is_null($key)){
			
			$this->db->from('user_config')->where('user',$this->id);

			$config=array_column($this->db->get()->result_array(),'value','key');

			return array_map(function($value){
				
				$decoded=json_decode($value,true);
				
				if(!is_null($decoded)){
					$value=$decoded;
				}
				
				return $value;
				
			}, $config);
			
		}
		elseif(is_null($value)){
			
			$row=$this->db->select('id,value')
				->from('user_config')
				->where('user',$this->id)
				->where('key',$key)
				->get()->row();

			if($row){
				$json_value=json_decode($row->value);
				
				if(is_null($json_value)){
					return $row->value;
				}
				else{
					return $json_value;
				}
			}
			else{
				return false;
			}
		}
		else{
			
			if(is_array($value)){
				$value=json_encode($value);
			}
			
			return $this->db->upsert('user_config', array('user'=>$this->id,'key'=>$key,'value'=>$value));
		}
	}

}
?>
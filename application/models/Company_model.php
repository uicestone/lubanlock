<?php
class Company_model extends Object_model{
	
	var $name;
	var $type;
	var $syscode;
	var $sysname;
	
	function __construct(){
		parent::__construct();
		$this->table='company';
		$this->recognize($this->input->server('SERVER_NAME'));

		//获取存在数据库中的公司配置项
		$this->db->from('company_config')
			->where('company',$this->id);
		
		$config=array_column($this->db->get()->result_array(),'value','key');
		
		array_walk($config, function(&$value){
			$decoded=json_decode($value,true);
			if(!is_null($decoded)){
				$value=$decoded;
			}
		});
		
		$this->config->company=$config;
		
	}

	function recognize($host_name){
		$this->db->select('id,name,type,syscode,sysname')
			->from('company')
			->or_where(array('host'=>$host_name,'syscode'=>$host_name));

		$row=$this->db->get()->row();
		
		$this->id=intval($row->id);
		$this->name=$row->name;
		$this->type=$row->type;
		$this->syscode=$row->syscode;
		$this->sysname=$row->sysname;
	}
	
	/**
	 * set or get a  company config value
	 * json_decode/encode automatically
	 * @param string $key
	 * @param mixed $value
	 * @return
	 *	get: the config value, false if not found
	 *	set: the insert or update query
	 */
	function config($key,$value=NULL){
		$db = $this->load->database('', true);
		$row=$db->select('id,value')->from('company_config')->where('company',$this->id)->where('key',$key)
			->get()->row();
		
		if(is_null($value)){
			if($row){
				$json_value=json_decode($row->value);
				if(is_null($json_value)){
					return $row->value;
				}else{
					return $json_value;
				}
			}else{
				return false;
			}
		}
		else{
			
			if(is_array($value)){
				$value=json_encode($value);
			}
			
			return $db->upsert('company_config', array('value'=>$value, 'id'=>$row->id));
		}
	}
}
?>
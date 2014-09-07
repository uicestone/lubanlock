<?php
class Company_model extends CI_Model {
	
	var $id, $name, $type, $syscode, $sysname,
		$config;
	
	function __construct(){
		parent::__construct();
		
		// recognize the company by host
		$this->recognize();

		// load company config
		$this->config();
		
	}

	function recognize(){
		
		$host_name = $this->input->server('SERVER_NAME');
		
		$this->db->select('id, name, type, syscode, sysname')
			->from('company')
			->or_where(array('host'=>$host_name, 'syscode'=>$host_name));

		$row = $this->db->get()->row();
		
		if(!$row){
			throw new Exception('"' . $host_name . '" isn\'t leading you to any company in LubanLock. Please check your url.', 404);
		}
		
		$this->id = intval($row->id);
		$this->session->company_id = $this->id;
		$this->name = $row->name;
		$this->type = $row->type;
		$this->syscode = $row->syscode;
		$this->sysname = $row->sysname;
	}
	
	/**
	 * set or get a company config value
	 * or get all config values of a company
	 * json_decode/encode automatically
	 * @param string $key
	 * @param mixed $value
	 */
	function config($key = null, $value = null){
		
		if(is_null($key)){
			
			$this->db->from('company_config')->where('company', $this->session->company_id);

			$config = array_column($this->db->get()->result_array(), 'value', 'key');

			$this->config = array_map(function($value){
				
				$value = json_decode($value,true);
				
				return $value;
				
			}, $config);
			
			return $this->config;
			
		}
		elseif(is_null($value)){
			
			if(array_key_exists($key, $this->config)){
				return $this->config[$key];
			}
			else{
				return;
			}
			
		}
		else{
			$value = json_encode($value);
			return $this->db->upsert('company_config', array('company'=>$this->session->company_id, 'key'=>$key, 'value'=>$value));
		}
	}
}
?>
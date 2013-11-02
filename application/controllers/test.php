<?php
class Test extends SS_Controller{
	function __construct() {
		parent::__construct();
		$this->load->library('unit_test');
	}
	
	function index(){
		
	}
	
	function object(){
		
		$this->output->enable_profiler(TRUE);
		
		$this->db->truncate('object_meta');
		$this->db->truncate('object_mod');
		$this->db->truncate('object_relationship');
		$this->db->truncate('object_status');
		$this->db->truncate('object_tag');
		$this->db->delete('object',array('type'=>'测试'));
		
		$object = new Object_model();
		
		$staff_data=array(
			'name'=>'陆秋石',
			'num'=>'T-01',
			'type'=>'测试',
			'display'=>true,
			'meta'=>array(
				array('name'=>'地址','content'=>'韶山路348弄','comment'=>'家庭地址')
			),
			'mod'=>array(
				array('user'=>$this->user->id,'read'=>true,'write'=>true)
			),
			'relative'=>array(
				array('relative'=>$this->user->id,'relation'=>'主管')
			),
			'status'=>array(
				array('name'=>'添加')
			),
			'tag'=>array('潜在客户','成交客户')
		);
		
		$client_data=array(
			'name'=>'禾子',
			'num'=>'C-01',
			'type'=>'测试',
			'display'=>true
		);
		
		
		$project_data=array(
			'name'=>'禾女士劳动人事纠纷咨询',
			'num'=>'询2013-067',
			'type'=>'测试',
			'display'=>true
		);
		
		$staff=$object->add($staff_data);
		$client=$object->add($client_data);
		$project=$object->add($project_data);
		
		$object->id=$staff;
		$object->update(array('num'=>'T-01'));
		$data = $object->fetch($staff);
		$this->unit->run(
			array_intersect_key(
				array_diff_key($data,array('meta'=>NULL,'mod'=>NULL,'relative'=>NULL,'status'=>NULL,'tag'=>NULL)),
				$staff_data
			),
			array_diff_key($staff_data,array('meta'=>NULL,'mod'=>NULL,'relative'=>NULL,'status'=>NULL,'tag'=>NULL)),
		'对象添加');
		
		$meta_phone=$object->addMeta(array('name'=>'电话', 'content'=>'51096488', 'comment'=>'单位电话'));
		$meta_cell=$object->addMeta(array('name'=>'电话', 'content'=>'13641926334', 'comment'=>'手机'));
		$meta_address=$object->addMeta(array('name'=>'地址', 'content'=>'上海市常德路1211号'));
		$object->updateMetas(array(array('id'=>$meta_phone,'content'=>'51096488-128')));
		$object->updateMeta(array('id'=>$meta_address,'content'=>'上海市常德路1211号1204-1207','comment'=>'单位地址'));
		$object->removeMeta(array('id'=>$meta_cell));
		$this->unit->run(array_column($object->getMeta(),'content','comment'),array('单位电话'=>'51096488-128','单位地址'=>'上海市常德路1211号1204-1207','家庭地址'=>'韶山路348弄'),'资料项增删');
		
		$object->id=$project;
		$object->addTag(array('name'=>'房产','type'=>'领域'));
		$object->addTags(array(array('tag_name'=>'初成案'),array('type'=>'阶段','name'=>'一审')));
		$object->updateTags(array('type'=>'阶段','name'=>'二审'));
		$this->unit->run($object->getTag(),array('领域'=>'房产','初成案','阶段'=>'二审'),'标签增删');

		$relationship_client=$object->addRelative(array('relative'=>$client,'relation'=>'咨询人'));
		$relationship_client_temp=$object->addRelative(array('relative'=>$client,'relation'=>'客户'));
		$relationship_lawyer=$object->addRelative(array('relative'=>$staff,'relation'=>'律师'));
		$object->updateRelative(array('id'=>$relationship_lawyer,'relation'=>'接洽律师'));
		$object->removeRelative(array('id'=>$relationship_client_temp));
		$this->unit->run(array_column($object->getRelative(),'name','relation'),array('咨询人'=>'禾子','接洽律师'=>'陆秋石'),'关系增删');

		$object->relative_mod_list['_self']=array(
			'deleted'=>1,
			'read'=>2,
			'stared'=>4
		);
		$object->addRelativeMod($relationship_client, 'stared');
		$object->updateRelativeMod($relationship_client, array('deleted'=>true,'read'=>true,'stared'=>false));
		$object->removeRelativeMod($relationship_client, 'read');
		$this->unit->run(array_column($object->getRelative(NULL,array('_self'=>array('read'=>false,'deleted'=>true,'stared'=>false))),'name','relation'),array('咨询人'=>'禾子'),'关系开关量增删');
		
		$object->addStatus(array('name'=>'立案','date'=>'2013-7-27'));
		$object->addStatus(array('name'=>'电话咨询','timestamp'=>strtotime('2013-07-22')));
		$status_unknown=$object->addStatus(array('name'=>'未知状态','timestamp'=>strtotime('-5 days')));
		$object->removeStatus(array('id'=>$status_unknown));
		$this->unit->run(array_column($object->getStatus(),'date','name'),array('立案'=>'2013-07-27','电话咨询'=>'2013-07-22'),'状态增删');
		
		$this->unit->run(array_column($object->match('陆'),'name','num'),array('T-01'=>'陆秋石'),'匹配');
//		
//		$object->id=$object2;
//		$object->addTag('房产','领域');
//		$this->unit->run(array_column($object->getList(array('type'=>'test','tags'=>array('领域'=>'房产'))),'name','num'),array('001'=>'测试对象1','002'=>'测试对象2'),'对象列表-标签','包含类别的搜索匹配');
//		$this->unit->run($object->getList(array('type'=>'test','tags'=>array('类别'=>'房产'))),array(),'对象列表-标签','包含类别的搜索不匹配');
//		$this->unit->run(array_column($object->getList(array('type'=>'test','without_tags'=>array('类别'=>'房产'))),'num'),array('001','002','003'),'对象列表-标签','包含类别的否定搜索匹配');
//		$this->unit->run(array_column($object->getList(array('type'=>'test','without_tags'=>array('房产'))),'num'),array('003'),'对象列表-标签','包含类别的否定搜索不匹配');
//		
//		$object->addMeta(array('name'=>'电话', 'content'=>'13641926334', 'comment'=>'手机'));
//		$object->addMeta(array('name'=>'电话', 'content'=>'56756616', '家庭'));
//		$object->addMeta(array('name'=>'地址', 'content'=>'韶山路348弄28号'));
//		$this->unit->run(array_column($object->getList(array('type'=>'test','has_meta'=>array('电话'))),'num'),array('001','002'),'对象列表-资料项');
//		
		$this->output->set_output($this->unit->report());
		
	}
	
	function session(){
		print_r($this->session->all_userdata());
	}
}
?>

<?php
class Test extends LB_Controller{
	function __construct() {
		parent::__construct();
		$this->load->library('unit_test');
		$this->output->enable_profiler();
	}
	
	function index(){
		
	}
	
	function object(){
		
		$this->db->delete('object', array('num'=>'_test'));
		$this->tag->calibrateCount();
		$this->user->sessionLogout();
		
		// insert an object
		$object_1 = new Object_model(array(
			'name'=>'大灰',
			'type'=>'user',
			'num'=>'_test',
			'meta'=>array(
				array('key'=>'身份证号','value'=>'123456789012345678','unique'=>true),
				array('key'=>'手机','value'=>'12345678901'),
				array('key'=>'手机','value'=>'12345678902')
			),
			'status'=>array(
				array('name'=>'注册','date'=>'2014-05-12 00:00:00'),
				array('name'=>'登录','date'=>'2014-05-12 00:00:03')
			),
			'tag'=>array(
				'性别'=>'男'
			),
		));
		$this->unit->run($object_1->name, '大灰', 'insert an object');
		
		// insert a user based on an object
		$user_1_id = $this->user->add(array(
			'name'=>'大灰',
			'password'=>'myPassword',
			'email'=>'dusty@test.com',
			'roles'=>'admin,test',
		), array('object'=>$object_1->id));
		$user_1 = new User_model($user_1_id);
		$this->unit->run($user_1->name === '大灰' && $user_1_id === $object_1->id, true, 'insert a user based on an object');

		// insert a group, add user to the group
		$this->user->add(array('name'=>'男人帮', 'type'=>'group', 'num'=>'_test', 'roles'=>'man', 'relative'=>array($object_1->id)));
		$this->user->initialize($object_1->id);
		$this->unit->run(in_array('man', $this->user->roles), true, 'insert a group, add user to the group', 'Group role should exists in user role.');
		
		// insert a user and the base object at once
		$user_2 = new User_model(array(
			'name'=>'大鱼',
			'type'=>'user',
			'num'=>'_test',
			'password'=>'myPassword',
			'email'=>'fish@test.com',
			'roles'=>'admin,test',
			'meta'=>array(
				array('key'=>'身份证号','value'=>'123456789012345678','unique'=>true),
				array('key'=>'手机','value'=>'12345678901'),
				array('key'=>'手机','value'=>'12345678902')
			),
			'status'=>array(
				array('name'=>'注册','date'=>'2014-05-12 00:00:01'),
				array('name'=>'登录','date'=>'2014-05-12 00:00:02')
			),
			'tag'=>array(
				'性别'=>'女'
			)
		));
		$this->unit->run($user_2->name === '大鱼', true, 'insert a user and the base object at once');
		
		$this->user->add(array('name'=>'女儿国', 'type'=>'group', 'num'=>'_test', 'roles'=>'girl', 'relative'=>array($user_2->id)));
		
		// insert meta
		$object_1->addMeta('爱好', '桌球');
		$object_1->addMeta('爱好', '编程');
		$object_1->getMeta();
		$this->unit->run($object_1->meta['爱好'] === array('桌球','编程'), true, 'insert meta');
		
		$object_1->addMeta('年龄', 21, true);
		try{$object_1->addMeta('年龄', 80, true);}catch(Exception $e){$error = $e->getCode();}
		$object_1->getMeta();
		$this->unit->run($object_1->meta['年龄'] === array('21') && $error === 400, true, 'insert unique meta');unset($error);
		
		$object_1->addMeta(array(
			array('key'=>'工作单位', 'value'=>'Allstar', 'unique'=>true),
			array('key'=>'项目', 'value'=>'89jian'),
			'项目'=>'zhouyi'
		));
		$object_1->getMeta();
		$this->unit->run(
			$object_1->meta['工作单位'] === array('Allstar')
			&& $object_1->meta['项目'] === array('89jian', 'zhouyi'), 
			true, 'batch insert meta'
		);
		
		// update meta
		$object_1->updateMeta('工作单位', 'Career');
		$object_1->getMeta();
		$this->unit->run($object_1->meta['工作单位'], array('Career'), 'update meta');
		
		$object_1->updateMeta('项目', 'Circle');
		$object_1->getMeta();
		$this->unit->run($object_1->meta['项目'], array('Circle', 'zhouyi'), 'update meta (one of multiple)');
		
		$object_1->updateMeta('项目', 'CMCC', 'zhouyi');
		$object_1->getMeta();
		$this->unit->run($object_1->meta['项目'], array('Circle', 'CMCC'), 'update meta (specific previous meta value)');
		
		// insert tag
		$object_1->addTag('外省', '户籍');
		$object_1->getTag();
		$this->unit->run($object_1->tag['户籍'] , array('外省'), 'insert tag');
		
		// insert tag (appending)
		$object_1->addTag(array('本市', '外省'), '户籍', true);
		$object_1->getTag();
		$this->unit->run($object_1->tag['户籍'] , array('外省', '本市'), 'insert tag (appending)');
		
		// insert tag (replacing)
		$object_1->addTag('本市', '户籍');
		$object_1->getTag();
		$this->unit->run($object_1->tag['户籍'] , array('本市'), 'insert tag (replacing)');

		// batch insert tag
		$object_1->addTag(array(
			'劳动关系'=>'派遣',
			'居住地'=>'宝山',
		));
		$object_1->getTag();
		$this->unit->run($object_1->tag['劳动关系'] === array('派遣') && $object_1->tag['居住地'] === array('宝山'), true, 'batch insert tag');
		
		// remove tag
		$object_1->setTag(null, '户籍');
		$object_1->getTag();
		$this->unit->run(array_key_exists('户籍', $object_1->tag), false, 'remove tag');
		
		// insert status
		$object_1->addStatus('登录');
		$object_1->getStatus();
		$this->unit->run(strtotime($object_1->status['登录'][count($object_1->status['登录']) - 1]), time(), 'insert status');
		
		// update status
		$object_1->updateStatus('登录', time() + 1000, 'A Comment Message.');
		$object_1->getStatus();
		$this->unit->run($object_1->status['登录'][count($object_1->status['登录']) - 1], date('Y-m-d H:i:s', time() + 1000), 'update status');
		
		// update status
		$object_1->updateStatus('登录', time(), null, '2014-05-12 00:00:03');
		$object_1->getStatus();
		$this->unit->run($object_1->status['登录'][0], date('Y-m-d H:i:s', time()), 'update status (specified previous date)');
		
		// remove status
		$object_1->removeStatus('登录', time() + 1000);
		$object_1->getStatus();
		$this->unit->run($object_1->status['登录'][0], date('Y-m-d H:i:s', time()), 'remove status (specified previous date)');
		
		$object_1->removeStatus('登录');
		$object_1->getStatus();
		$this->unit->run(array_key_exists('登录', $object_1->status), false, 'remove status');
		
		// insert relative
		$object_1->addRelative('同学', $user_2->id, 1, array('自从'=>'初中', '直到'=>'现在'));
		$object_1->getRelative();
		$this->unit->run($object_1->relative['同学'][0]['name'], '大鱼', 'insert relative');
		
		$object_1->setRelativeMeta('同学', $user_2->id, '直到', '永远');
		$object_1->getRelative();
		$this->unit->run($object_1->relative['同学'][0]['meta']['直到'], '永远', 'set relative meta');
		
		$object_1->setRelativeMeta('同学', $user_2->id, '自从', null);
		$object_1->getRelative();
		$this->unit->run(array_key_exists('自从', $object_1->relative['同学'][0]['meta']), false, 'remove relative meta');
		
		// read public object as user not logged in
		$this->user->initialize();
		$object_1->fetch();
		$this->unit->run($object_1->name, '大灰', 'reading public object without logged in');
		
		// reading private object without logged in
		$this->user->initialize($user_1->id);
		$object_1->authorize('private');
		$this->user->initialize();
		try{$object_1->fetch();}catch(Exception $e){$error = $e->getCode();}
		$this->unit->run($error, 403, 'reading private object without logged in');
		
		$this->output->set_output($this->unit->report());
	}
	
	function session(){
		print_r($this->session->all_userdata());
		print_r($this->user);
	}
}
?>

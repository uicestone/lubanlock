<!DOCTYPE html>
<html ng-app="lubanlockApp">
	<head ng-controller="headCtrl">
		<meta charset="utf-8" />
		<meta name="renderer" content="webkit" />
		<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
		
		<title ng-bind="(title() ? title() + ' - ' : '') + '<?=$this->session->user_name?> - <?=$this->company->sysname?>'"><?=$this->session->user_name?> - <?=$this->company->sysname?></title>

		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		
		<?=$this->stylesheet('css/font-awesome.min')?>
		<?=$this->stylesheet('css/bootstrap.min')?>
		<?=$this->stylesheet('css/ace')?>
		
		<!--[if lt IE 9]>
		<?=$this->stylesheet('css/ace-ie')?>
		<![endif]-->

		<!--[if lt IE 9]>
		<?=$this->javascript('js/es5-shim.min')?>
		<![endif]-->
		
		<!--[if !IE]> -->
		<?=$this->javascript('js/jquery/jquery.min')?>
		<!-- <![endif]-->
		<!--[if IE]>
		<?=$this->javascript('js/jquery/jquery-1.x.min')?>
		<![endif]-->
		
		<?=$this->javascript('js/angular/angular.min')?>
		<?=$this->javascript('js/angular/angular-locale_zh-cn')?>
		<?=$this->javascript('js/angular/angular-route.min')?>
		<?=$this->javascript('js/angular/angular-resource.min')?>
		<?=$this->javascript('js/angular/ui-bootstrap.min')?>
		<?=$this->javascript('js/angular/angular-file-upload.min')?>
		
		<?=$this->javascript('app')?>
		<?=$this->javascript('controllers/component')?>
		<?=$this->javascript('controllers/dialog')?>
		<?=$this->javascript('controllers/object')?>
		<?=$this->javascript('controllers/user')?>
		<?=$this->javascript('directives')?>
		<?=$this->javascript('filters')?>
		<?=$this->javascript('services')?>

		<!--[if lt IE 9]>
		<?=$this->javascript('js/html5shiv.min')?>
		<?=$this->javascript('js/respond.min')?>
		<![endif]-->
		
		<?php if($this->company->config('modules')): foreach($this->company->config('modules') as $module): ?>
		<?=$this->javascript('modules/' . $module . '/index')?>
		<?php endforeach; endif; ?>

		<script type="text/javascript">
			var company = <?=json_encode($this->company)?>;
			var user = <?=json_encode($this->session->user)?>;
			var groups = <?=json_encode($this->session->groups)?>;
		</script>
		
	</head>
	
	<body class="navbar-fixed">
		
		<div class="navbar navbar-default navbar-fixed-top" id="navbar" ng-controller="TopBarCtrl">

			<div class="navbar-container" id="navbar-container">
				<div class="navbar-header pull-left">
					<a href="" class="navbar-brand">
						<small>
							<i class="icon-book"></i>
							<?=$this->company->sysname?>
						</small>
					</a>
				</div>

				<div class="navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">
						<li class="blue">
							<a href="#/dialog">
								<i class="icon-envelope" ng-class="{'icon-animated-vertical':unread_messages>0}"></i>
								<span class="badge badge-danger" ng-show="unread_messages>0">{{unread_messages}}</span>
							</a>
						</li>
						<li class="light-blue" dropdown>
							<a href="" dropdown-toggle>
								<span class="user-info">
									<small>你好,</small>
									<?=$this->session->user_name?>
								</span>

								<i class="icon-caret-down"></i>
							</a>

							<ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="#/user/<?=$this->user->id?>">
										<i class="icon-cog"></i>
										设置
									</a>
								</li>

								<li>
									<a href="#/detail/<?=$this->user->id?>">
										<i class="icon-user"></i>
										个人信息
									</a>
								</li>

								<li class="divider"></li>

								<li>
									<a href="<?=site_url()?>logout">
										<i class="icon-off"></i>
										退出
									</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="main-container" id="main-container">
			<div class="main-container-inner">
		

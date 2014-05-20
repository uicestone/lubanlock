<!DOCTYPE html>
<html ng-app="lubanlockApp">
	<head>
		<meta charset="utf-8" />
		<title><?=$this->company->sysname?></title>

		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		
		<!--[if !IE]> -->
		<script type="text/javascript" src="js/jquery/jquery-2.1.0.js"></script>
		<!-- <![endif]-->
		<!--[if IE]>
		<script type="text/javascript" src="js/jquery/jquery-1.11.0.js"></script>
		<![endif]-->
					
		<script src="js/dropzone.js"></script>

		<script type="text/javascript" src="js/angular/angular.js"></script>
		<script type="text/javascript" src="js/angular/angular-route.js"></script>
		<script type="text/javascript" src="js/angular/angular-resource.js"></script>
		<script type="text/javascript" src="js/ui-bootstrap-tpls-0.10.0.js"></script>
		
		<script type="text/javascript" src="app.js"></script>
		<script type="text/javascript" src="controllers.js"></script>
		<script type="text/javascript" src="directives.js"></script>
		<script type="text/javascript" src="filters.js"></script>
		<script type="text/javascript" src="services.js"></script>

		<link rel="stylesheet" href="css/font-awesome.css" />
		
		<link rel="stylesheet" href="css/bootstrap.css" />
		<link rel="stylesheet" href="css/dropzone.css" />

		<link rel="stylesheet" href="css/ace-fonts.css" />
		<link rel="stylesheet" href="css/ace.css" />
		
		<!--[if lte IE 8]>
		<link rel="stylesheet" href="css/ace-ie.css" />
		<![endif]-->

		<link rel="stylesheet" href="css/style.css" />
		
		<!--[if lt IE 9]>
		<script src="js/html5shiv.js"></script>
		<script src="js/respond.min.js"></script>
		<![endif]-->

		<script type="text/javascript">
			var company = <?=json_encode($this->company)?>;
			var user = <?=json_encode($this->user)?>;
		</script>
		
	</head>
	
	<body class="navbar-fixed">
		
		<div class="navbar navbar-default navbar-fixed-top" id="navbar">

			<div class="navbar-container" id="navbar-container">
				<div class="navbar-header pull-left">
					<a href="" class="navbar-brand">
						<small>
							<i class="icon-book"></i>
							<?=$this->company->sysname?>
						</small>
					</a><!-- /.brand -->
				</div><!-- /.navbar-header -->

				<div class="navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">

						<li class="light-blue">
							<a href="" dropdown-toggle>
								<span class="user-info">
									<small>你好,</small>
									<?=$this->user->name?>
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
									<a href="/logout">
										<i class="icon-off"></i>
										退出
									</a>
								</li>
							</ul>
						</li>
					</ul><!-- /.ace-nav -->
				</div><!-- /.navbar-header -->
			</div><!-- /.container -->
		</div>

		<div class="main-container" id="main-container">

			<div class="main-container-inner">
				<a class="menu-toggler" id="menu-toggler" href="">
					<span class="menu-text"></span>
				</a>

				<div class="sidebar sidebar-fixed" id="sidebar" ng-controller="NavCtrl" ng-class="{'menu-min':config.nav_minimized}">

					<ul class="nav nav-list">
						
						<li ng-repeat="item in items">
							<a ng-click="navigateTo(item)">
								<i class="icon-trash" ng-if="removable[item.name]" ng-mouseleave="makeRemovable(item.name, false)" ng-click="remove(item, $event)"></i>
								<i class="icon-{{item.icon}}" ng-hide="removable[item.name]" ng-mouseenter="makeRemovable(item.name)"></i>
								<span class="menu-text"> {{item.name}} </span>
							</a>
						</li>
						
						<li>
							<a href="#/list">
								<i class="icon-cloud"></i>
								<span class="menu-text"> 所有数据 </span>
							</a>
						</li>
						
<?php if($this->user->isLogged('user_admin')){ ?>
						<li>
							<a href="#/user">
								<i class="icon-user"></i>
								<span class="menu-text"> 用户管理 </span>
							</a>
						</li>
<?php } ?>
						
					</ul><!-- /.nav-list -->

					<div class="sidebar-collapse" id="sidebar-collapse">
						<i data-icon1="icon-double-angle-left" data-icon2="icon-double-angle-right" ng-class="{'icon-double-angle-left': !config.nav_minimized, 'icon-double-angle-right': config.nav_minimized}" ng-click="toggleMinimize()"></i>
					</div>

				</div>

				<div class="main-content">
					
					<div class="page-content" ng-view>
					</div><!-- /.page-content -->
				</div><!-- /.main-content -->

			</div><!-- /.main-container-inner -->

		</div><!-- /.main-container -->
		
		<div class="alert-container">
			<div class="alert alert-warning" ng-controller="AlertCtrl" ng-show="alerts.length > 0">{{alert.message}}</div>
		</div>
		
	</body>

</html>

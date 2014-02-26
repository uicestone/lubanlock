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
					
		<script src="js/bootstrap.min.js"></script>
		<script src="js/dropzone.js"></script>

		<script type="text/javascript" src="js/angular/angular.js"></script>
		<script type="text/javascript" src="js/angular/angular-route.js"></script>
		<script type="text/javascript" src="js/angular/angular-resource.js"></script>
		
		<script type="text/javascript" src="app.js"></script>
		<script type="text/javascript" src="controllers.js"></script>
		<script type="text/javascript" src="directives.js"></script>
		<script type="text/javascript" src="filters.js"></script>
		<script type="text/javascript" src="services.js"></script>

		<script src="js/ace.min.js"></script>
		<script src="js/ace-elements.min.js"></script>
		<script src="js/ace-extra.min.js"></script>

		<!--[if lt IE 9]>
		<script src="js/html5shiv.js"></script>
		<script src="js/respond.min.js"></script>
		<![endif]-->

		<link rel="stylesheet" href="css/font-awesome.min.css" />
		
		<!--[if IE 7]>
		<link rel="stylesheet" href="css/font-awesome-ie7.min.css" />
		<![endif]-->
		
		<link rel="stylesheet" href="css/bootstrap.min.css" />
		<link rel="stylesheet" href="css/dropzone.css" />

		<link rel="stylesheet" href="css/ace-fonts.css" />
		<link rel="stylesheet" href="css/ace.min.css" />
		
		<!--[if lte IE 8]>
		<link rel="stylesheet" href="css/ace-ie.min.css" />
		<![endif]-->

		<link rel="stylesheet" href="css/style.css" />
		
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
							<a data-toggle="dropdown" href="" class="dropdown-toggle">
								<span class="user-info">
									<small>你好,</small>
									<?=$this->user->name?>
								</span>

								<i class="icon-caret-down"></i>
							</a>

							<ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="">
										<i class="icon-cog"></i>
										设置
									</a>
								</li>

								<li>
									<a href="">
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

				<div class="sidebar sidebar-fixed" id="sidebar">

					<ul class="nav nav-list">
						<li>
							<a class="dropdown-toggle">
								<i class="icon-list"></i>
								<span class="menu-text"> 职位信息 </span>

								<b class="arrow icon-angle-down"></b>
							</a>

							<ul class="submenu">
								<li>
									<a href="#/jobs">
										<i class="icon-double-angle-right"></i>
										所有职位
									</a>
								</li>
								<li>
									<a href="#/jobs/favorite">
										<i class="icon-double-angle-right"></i>
										已投递的职位
									</a>
								</li>
							</ul>
						</li>

						<li>
							<a href="#/my-resume">
								<i class="icon-edit"></i>
								<span class="menu-text"> 我的简历 </span>
							</a>
						</li>
						
						<li>
							<a href="#/user">
								<i class="icon-user"></i>
								<span class="menu-text"> 用户管理 </span>
							</a>
						</li>
						
						<li>
							<a href="#/list">
								<i class="icon-cloud"></i>
								<span class="menu-text"> 所有数据 </span>
							</a>
						</li>
						
					</ul><!-- /.nav-list -->

					<div class="sidebar-collapse" id="sidebar-collapse">
						<i class="icon-double-angle-left" data-icon1="icon-double-angle-left" data-icon2="icon-double-angle-right"></i>
					</div>

				</div>

				<div class="main-content">
					
					<div class="page-content" ng-view>
					</div><!-- /.page-content -->
				</div><!-- /.main-content -->

			</div><!-- /.main-container-inner -->

			<a href="" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="icon-double-angle-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->
		
	</body>

</html>

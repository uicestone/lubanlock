<!DOCTYPE html>
<html ng-app="lubanlockApp">
	<head>
		<meta charset="utf-8" />
		<title><?=$this->company->sysname?></title>

		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		
		<!--[if !IE]> -->
		<script type="text/javascript" src="js/jquery/jquery-2.0.3.min.js"></script>
		<!-- <![endif]-->

		<!--[if IE]>
		  <script type="text/javascript" src="js/jquery/jquery-1.10.2.min.js"></script>
		<![endif]-->
					
		<script type="text/javascript" src="js/angular/angular.js"></script>
		<script type="text/javascript" src="js/angular/angular-route.js"></script>
		<script type="text/javascript" src="js/angular/angular-resource.js"></script>
		
		<script type="text/javascript" src="app.js"></script>
		<script type="text/javascript" src="controllers.js"></script>
		<script type="text/javascript" src="directives.js"></script>
		<script type="text/javascript" src="filters.js"></script>
		<script type="text/javascript" src="services.js"></script>

		<!-- basic styles -->

		<link rel="stylesheet" href="css/bootstrap.min.css" />
		<link rel="stylesheet" href="css/font-awesome.min.css" />
		<link rel="stylesheet" href="css/dropzone.css" />

		<!--[if IE 7]>
		  <link rel="stylesheet" href="css/font-awesome-ie7.min.css" />
		<![endif]-->

		<!-- page specific plugin styles -->

		<!-- fonts -->

		<link rel="stylesheet" href="css/ace-fonts.css" />

		<!-- ace styles -->

		<link rel="stylesheet" href="css/ace.min.css" />
		<link rel="stylesheet" href="css/ace-rtl.min.css" />
		<link rel="stylesheet" href="css/ace-skins.min.css" />

		<!--[if lte IE 8]>
		  <link rel="stylesheet" href="css/ace-ie.min.css" />
		<![endif]-->

		<!-- inline styles related to this page -->

		<!-- ace settings handler -->

		<script src="js/ace-extra.min.js"></script>

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->

		<!--[if lt IE 9]>
		<script src="js/html5shiv.js"></script>
		<script src="js/respond.min.js"></script>
		<![endif]-->

	</head>
	
	<body>
		
		<div class="navbar navbar-default navbar-fixed-top" id="navbar">

			<div class="navbar-container" id="navbar-container">
				<div class="navbar-header pull-left">
					<a href="#" class="navbar-brand">
						<small>
							<i class="icon-book"></i>
							<?=$this->company->sysname?>
						</small>
					</a><!-- /.brand -->
				</div><!-- /.navbar-header -->

				<div class="navbar-header pull-right" role="navigation">
					<ul class="nav ace-nav">

<!--						<li>
							<a data-toggle="dropdown" class="dropdown-toggle" href="#">
								<i class="icon-envelope icon-animated-vertical"></i>
								<span class="badge badge-success">5</span>
							</a>

							<ul class="pull-right dropdown-navbar dropdown-menu dropdown-caret dropdown-close">
								<li class="dropdown-header">
									<i class="icon-envelope-alt"></i>
									5 Messages
								</li>

								<li>
									<a href="#">
										<img src="" class="msg-photo" alt="Alex's Avatar" />
										<span class="msg-body">
											<span class="msg-title">
												<span class="blue">Alex:</span>
												Ciao sociis natoque penatibus et auctor ...
											</span>

											<span class="msg-time">
												<i class="icon-time"></i>
												<span>a moment ago</span>
											</span>
										</span>
									</a>
								</li>

								<li>
									<a href="#">
										<img src="" class="msg-photo" alt="Susan's Avatar" />
										<span class="msg-body">
											<span class="msg-title">
												<span class="blue">Susan:</span>
												Vestibulum id ligula porta felis euismod ...
											</span>

											<span class="msg-time">
												<i class="icon-time"></i>
												<span>20 minutes ago</span>
											</span>
										</span>
									</a>
								</li>

								<li>
									<a href="#">
										<img src="" class="msg-photo" alt="Bob's Avatar" />
										<span class="msg-body">
											<span class="msg-title">
												<span class="blue">Bob:</span>
												Nullam quis risus eget urna mollis ornare ...
											</span>

											<span class="msg-time">
												<i class="icon-time"></i>
												<span>3:15 pm</span>
											</span>
										</span>
									</a>
								</li>

								<li>
									<a href="inbox.html">
										See all messages
										<i class="icon-arrow-right"></i>
									</a>
								</li>
							</ul>
						</li>
-->
						<li class="light-blue">
							<a data-toggle="dropdown" href="#" class="dropdown-toggle">
								<span class="user-info">
									<small>你好,</small>
									<?=$this->user->name?>
								</span>

								<i class="icon-caret-down"></i>
							</a>

							<ul class="user-menu pull-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
								<li>
									<a href="#">
										<i class="icon-cog"></i>
										设置
									</a>
								</li>

								<li>
									<a href="#">
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
				<a class="menu-toggler" id="menu-toggler" href="#">
					<span class="menu-text"></span>
				</a>

				<div class="sidebar sidebar-fixed" id="sidebar">

					<ul class="nav nav-list">
						<li>
							<a href="#/jobs" class="dropdown-toggle">
								<i class="icon-list"></i>
								<span class="menu-text"> 职位信息 </span>

								<b class="arrow icon-angle-down"></b>
							</a>

							<ul class="submenu">
								<li>
									<a href="#/jobs">
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

			<a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
				<i class="icon-double-angle-up icon-only bigger-110"></i>
			</a>
		</div><!-- /.main-container -->
		
		<!-- basic scripts -->

		<script type="text/javascript">
			if("ontouchend" in document) document.write("<script src='js/jquery/jquery.mobile.custom.min.js'>"+"<"+"/script>");
		</script>
		<script src="js/bootstrap.min.js"></script>
		<!--<script src="js/typeahead-bs2.min.js"></script>-->

		<!-- page specific plugin scripts -->
		<script src="js/dropzone.js"></script>

		<!-- ace scripts -->

		<script src="js/ace-elements.min.js"></script>
		<script src="js/ace.min.js"></script>

		<!-- inline scripts related to this page -->
	</body>

</html>

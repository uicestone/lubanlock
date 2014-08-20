<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.2.4 or newer
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the Academic Free License version 3.0
 *
 * This source file is subject to the Academic Free License (AFL 3.0) that is
 * bundled with this package in the files license_afl.txt / license_afl.rst.
 * It is also available through the world wide web at this URL:
 * http://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world wide web, please send an email to
 * licensing@ellislab.com so we can send you a copy immediately.
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @license		http://opensource.org/licenses/AFL-3.0 Academic Free License (AFL 3.0)
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<title>错误</title>

		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		
		<!--[if !IE]> -->
		<script type="text/javascript" src="js/jquery/jquery.min.js?v=2.1.1"></script>
		<!-- <![endif]-->
		<!--[if IE]>
		<script type="text/javascript" src="js/jquery/jquery-1.x.min.js?v=1.11.1"></script>
		<![endif]-->
					
		<link rel="stylesheet" href="css/font-awesome.min.css?v=3.2.1" />
		<link rel="stylesheet" href="css/bootstrap.min.css?v=3.2.0" />
		<link rel="stylesheet" href="css/ace.css" />
		
		<!--[if lt IE 9]>
		<link rel="stylesheet" href="css/ace-ie.css" />
		<![endif]-->

		<link rel="stylesheet" href="css/style.css?v=2014-08-18-1" />
		
		<!--[if lt IE 9]>
		<script src="js/html5shiv.js"></script>
		<script src="js/respond.min.js"></script>
		<![endif]-->

	</head>

	<body>
		<div class="main-container">
			<div class="page-content">
				<div class="row">
					<div class="col-xs-12">

						<div class="error-container">
							<div class="well">
								<h1 class="grey lighter smaller">
									<span class="blue bigger-125">
										<i class="icon-sitemap"></i>
										<?=$status_code?>
									</span>
									<?=$heading?>
								</h1>
								
								<hr />
								
								<h3 class="lighter smaller"><?=$message?></h3>
								
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>

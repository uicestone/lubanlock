			</div><!-- /.main-container-inner -->

		</div><!-- /.main-container -->
		
		<div class="alert-container">
			<div class="alert alert-warning" ng-controller="AlertCtrl" ng-show="alerts.length > 0">
				<?php if($this->agent->is_browser('Internet Explorer') && $this->agent->version() < 8){ ?>
				你应该使用Chrome，FireFox, Safari 或Internet Explorer 9.0以上内核的浏览器访问本站。如果您使用的是360、搜狗、遨游等浏览器，
				<?php }else{ ?>
				{{alert.message}}
				<?php } ?>
			</div>
		</div>
		
	</body>

</html>

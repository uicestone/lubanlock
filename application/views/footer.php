			</div><!-- /.main-container-inner -->

		</div><!-- /.main-container -->
		
		<div class="alert-container" ng-controller="AlertCtrl">
			<alert ng-repeat="alert in alerts" type="{{alert.type}}" ng-mouseenter="toggleCloseButton($index)" ng-mouseleave="toggleCloseButton($index)">
				<button ng-show="alert.closeable" type="button" class="close" ng-click="close(alert.id)">
					<span aria-hidden="true">×</span>
					<span class="sr-only">Close</span>
				</button>
				{{alert.msg}}
			</alert>
			<!--[if lt IE 9]>
			<div class="alert alert-warning">
				您正使用不受支持的旧版本IE浏览器。建议使用Chrome，Firefox，Safari或是360，搜狗，傲游等浏览器的“极速模式”（在地址栏右侧关闭兼容模式）
			</div>
			<![endif]-->
		</div>
		
	</body>

</html>

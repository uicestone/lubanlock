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
		</div>
		
	</body>

</html>

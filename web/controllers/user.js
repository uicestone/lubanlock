(function(){

'use strict';

lubanlockControllers.controller('UsersCtrl', ['$scope', '$location', '$modal', 'users',
	function($scope, $location, $modal, users) {
		
		//列表分页
		$scope.currentPage = $location.search().page || 1;
		
		$scope.users = users;
		// get pagination argument from statusText
		var statusText = $scope.users.$response.statusText;
		$scope.total = Number(statusText.match(/(\d+) Users in Total/)[1]);
		$scope.listStart = Number(statusText.match(/(\d+)\-/)[1]);
		$scope.listEnd = Number(statusText.match(/\-(\d+)/)[1]);

		$scope.nextPage = function(){
			$location.search('page', ++$scope.currentPage);
		}
		
		$scope.previousPage = function(){
			$location.search('page', --$scope.currentPage);
		}
		
		$scope.showDetail = function(id){
			//因为使用了表格，无法使用a，因此绑定ng-click
			$location.url('user/' + id);
		}
		
		//保存为菜单，TODO，需要抽象以便快速应用于其他列表页
		$scope.showNavSaveForm = function(){

			var modalInstance = $modal.open({
				templateUrl: 'partials/new_nav_modal.html',
				controller: NewNavItemCtrl,
				resolve: {
					items: function() {
						return $scope.items;
					}
				}
			});

			modalInstance.result.then(function(selectedItem) {
				$scope.selected = selectedItem;
			});

		}
		
		var NewNavItemCtrl = ['$scope', '$modalInstance', 'Nav', 'User', function($scope, $modalInstance, Nav, User) {
			
			$scope.newNavItem = {};
			
			$scope.addNavItem = function() {
				Nav.save({name: $scope.newNavItem.name, template: $scope.newNavItem.template, icon: $scope.newNavItem.icon, user: $scope.newNavItem.user, params: $location.search()}, function() {
					$modalInstance.close();
				});
			}
			
			$scope.cancel = function() {
				$modalInstance.dismiss();
			};
			
			$scope.searchUser = function(name) {
				// a promise can be parsed by typeahead, no then() wrapping required
				return User.query({name: {like: name}}).$promise;
			};

		}];
		
		
		$scope.searchKeyword = $location.search().search;
		
		$scope.search = function(){
			$location.search('search', $scope.searchKeyword);
		}
		
		$scope.cancelSearch = function(){
			$scope.searchKeyword = null;
			$location.search('search', null);
		}
		
	}
]);

lubanlockControllers.controller('UserDetailCtrl', ['$scope', '$location', 'Alert', 'User', 'user',
	function($scope, $location, Alert, User, user) {
		
		$scope.user = user || new User();
		
		$scope.save = function(){
			
			if($scope.newPassword !== $scope.newPasswordConfirm){
				Alert.add('两次密码输入不一致');
				return;
			}else{
				$scope.user.password = $scope.newPassword;
			}
			
			if($scope.isGroup){
				$scope.user.type = 'group';
			}
			
			$scope.user.$save(function(user){
				$location.replace().path('user/' + user.id);
			});
		}
		
		$scope.updateConfig = function(){
			if($scope.newPassword){
				if($scope.newPassword !== $scope.newPasswordConfirm){
					Alert.add('两次密码输入不一致');
					return;
				}else{
					$scope.user.password = $scope.newPassword;
				}
			}
			$scope.user.$update();
			Alert.add('用户信息已更新', 'success');
		}
	}
]);

})();

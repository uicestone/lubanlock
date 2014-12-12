(function(){

'use strict';

lubanlockControllers.controller('AlertCtrl', ['$scope', 'Alert',
	function($scope, Alert){
		$scope.alerts = Alert.get();
		$scope.close = Alert.close;
		$scope.previous = function(){};
		$scope.next = function(){};
		
		$scope.toggleCloseButton = function(index){
			$scope.alerts[index].closeable = !$scope.alerts[index].closeable;
		};
	}
]);

lubanlockControllers.controller('TopBarCtrl', ['$scope', '$interval', 'Object',
	function($scope, $interval, Object){
		
		$scope.unread_messages = 0;
		$scope.dialog_unread = Object.query({type: '对话', user: 'ME', meta: {unread_messages: {gt: 0}}, with_meta: true});
		
		$interval(function(){
			Object.query({type: '对话', user: 'ME', meta: {unread_messages: {gt: 0}}, with_meta: true}, function(response){
				$scope.dialog_unread = response;
			});
		}, 10000);
		
		$scope.$watch('dialog_unread', function(){
			$scope.unread_messages = 0;
			angular.forEach($scope.dialog_unread, function(dialog){
				$scope.unread_messages += Number(dialog.meta.unread_messages[0]);
			});
		}, true)
	}
]);

lubanlockControllers.controller('NavCtrl', ['$scope', '$location', '$rootScope', 'Nav', 'User',
	function($scope, $location, $rootScope, Nav, User){
		
		$scope.items = Nav.query();
		
		$scope.config = User.getConfig();
		
		$scope.toggleMinimize = function(){
			$scope.config.nav_minimized = !$scope.config.nav_minimized;
			$scope.config.$saveConfig();
		}
		
		$scope.navigateTo = function(item){
			$location.path(item.meta && item.meta.template ? item.meta.template[0] : 'list').search(item.meta && item.meta.params ? angular.fromJson(item.meta.params[0]) : {});
		}
		
		$scope.removable = {};
		
		$scope.makeRemovable = function(item, value){
			value = value === undefined ? true : value;
			$scope.removable[item.id] = value;
		}
		
		$scope.remove = function(item, event){
			event.stopPropagation();
			Nav.remove(item);
		}
		
		$rootScope.$on('$routeChangeSuccess', function(event, data){
			$scope.currentUrl = $location.url();
			$scope.items.map(function(item){
				var url = '/'  + (item.meta && item.meta.template ? item.meta.template[0] : 'list') + (item.meta && item.meta.params && item.meta.params[0] !== '{}' ? '?' + jQuery.param(angular.fromJson(item.meta.params[0])) : '')
				if(url === $scope.currentUrl){
					item.isActive = true;
				}else{
					item.isActive = false;
				}
				return item;
			});
		});
	}
]);

lubanlockControllers.controller('headCtrl', ['$scope', 'Head',
	function($scope, Head){
		$scope.title = Head.title;
	}
]);

lubanlockControllers.controller('DashboardCtrl', [
	function(){
		
	}
]);

})();

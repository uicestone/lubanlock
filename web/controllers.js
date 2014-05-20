'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('AlertCtrl', ['$scope', 'Alert',
	function($scope, Alert){
		
		$scope.alerts = Alert.getAlerts();
		
		$scope.$watch('alerts.length', function(length){
			
			if(length === 0){
				return;
			}
			
			$scope.alert = $scope.alerts[$scope.alerts.length - 1];
		});
		
	}
]);

lubanlockControllers.controller('NavCtrl', ['$scope', 'Nav', 'UserConfig', '$location',
	function($scope, Nav, UserConfig, $location){
		
		$scope.items = Nav.query();
		
		//TODO 不是很舒服， 考虑改成全局config，但需要解决promise的问题
		$scope.config = UserConfig.get({item: 'nav_minimized'});
		
		$scope.toggleMinimize = function(){
			$scope.config.nav_minimized = !$scope.config.nav_minimized;
			$scope.config.$save();
		}
		
		$scope.navigateTo = function(item){
			$location.path(item.template || 'list').search(item.params);
		}
		
		$scope.removable = {};
		
		$scope.makeRemovable = function(item, value){
			value = value === undefined ? true : value;
			$scope.removable[item] = value;
		}
		
		$scope.remove = function(item, event){
			event.stopPropagation();
			Nav.remove(item);
		}
	}
]);

lubanlockControllers.controller('ListCtrl', ['$scope', '$location', 'Nav', 'objectsResponse',
	function($scope, $location, Nav, objectsResponse) {
		//列表分页
		$scope.currentPage = $location.search().page || 1;
		
		$scope.headers = objectsResponse.headers();
		$scope.objects = objectsResponse.data;
		
		//从responseHeaders中获得status-text，用正则匹配出分页参数
		var statusText = eval($scope.headers['status-text']);
		$scope.totalObjects = Number(statusText.match(/(\d+) Objects in Total/)[1]);
		$scope.objectListStart = Number(statusText.match(/(\d+) \-/)[1]);
		$scope.objectListEnd = Number(statusText.match(/\- (\d+)/)[1]);

		$scope.nextPage = function(){
			$location.search('page', ++$scope.currentPage);
		}
		
		$scope.previousPage = function(){
			$location.search('page', --$scope.currentPage);
		}
		
		//详情页，TODO，待完善，对于符合一定条件的对象，使用特定模板载入
		$scope.showDetail = function(id, type){
			
			if(type === 'file'){
				window.open('/file/download/' + id);
				return;
			}
			
			$location.url('detail/' + id);
		}
		
		//保存为菜单，TODO，需要抽象以便快速应用于其他列表页
		$scope.showNavSaveForm = false;
		
		$scope.toggleNavSaveForm = function(){
			$scope.showNavSaveForm = !$scope.showNavSaveForm;
		}
		
		$scope.addNavItem = function(){
			Nav.save({name: $scope.newNavItemName, template: $scope.newNavItemTemplate, params: $location.search()}, function(){
				$scope.showNavSaveForm = false;
				$scope.newNavItemName = $scope.newNavItemTemplate = null;
			});
		}
		
	}
]);

lubanlockControllers.controller('DetailCtrl', ['$scope', 'objectResponse', 'ObjectMeta', 'ObjectRelative', 'ObjectStatus', 'ObjectTag', '$location',
	function($scope, objectResponse, ObjectMeta, ObjectRelative, ObjectStatus, ObjectTag, $location) {
		
		if(objectResponse){
			$scope.object = objectResponse.resource;
			$scope.headers = objectResponse.headers();
		}
		
		$scope.adding = {
			meta: false,
			relative: false,
			status: false,
			tag: false
		}
		
		$scope['new'] = {};
		
		$scope.openPropAddForm = function(prop){
			$scope.adding[prop] = true;
		}
		
		$scope.closePropAddForm = function(prop){
			$scope['new'][prop] = {};
			$scope.adding[prop] = false;
		}
		
		$scope.addMeta = function($event){
			
			ObjectMeta.save({object: $scope.object.id, key: $scope['new'].meta.key}, $scope['new'].meta.value, function(){
				
				if($scope.object.meta[$scope['new'].meta.key] === undefined){
					$scope.object.meta[$scope['new'].meta.key] = [];
				}

				$scope.object.meta[$scope['new'].meta.key].push($scope['new'].meta.value);
			
				$scope['new'].meta.value = undefined;
				angular.element($event.target).find(':input:first').trigger('select');
			});
			
		}
		
		$scope.removeMeta = function(key, value){
			ObjectMeta.remove({object: $scope.object.id, key: key, value: value}, function(meta){
				$scope.object.meta = meta;
			});
		}
		
		$scope.addStatus = function(){
			
			ObjectStatus.save({object: $scope.object.id, name: $scope['new'].status.name, as_rows: true, order_by: 'date desc'}, $scope['new'].status.date, function(status){
				$scope.object.status = status;
				$scope['new'].status = {};
			});
			
		}
		
		$scope.removeStatus = function(name, date){
			ObjectStatus.remove({object: $scope.object.id, name: name, date: date, as_rows: true, order_by: 'date desc'}, function(status){
				$scope.object.status = status;
			});
		}
		
		$scope.addTag = function(){
			ObjectTag.save({object: $scope.object.id, taxonomy: $scope['new'].tag.taxonomy}, $scope['new'].tag.term, function(tag){
				$scope.object.tag = tag;
			});
		}
		
		$scope.showDetail = function(id, type){
			
			if(type === 'file'){
				window.open('/file/download/' + id);
				return;
			}
			
			$location.url('detail/' + id);
		}
		
		$scope.remove = function(){
			$scope.object.$remove({}, function(){
				history.back();
			});
		}
		
	}
]);

lubanlockControllers.controller('UsersCtrl', ['$scope', '$routeParams', 'User', '$location',
	function($scope, $routeParams, User, $location) {
		
		$scope.users = User.query($routeParams);
		$scope.currentPage = $location.search().page || 1;
		
		$scope.showDetail = function(id){
			//因为使用了表格，无法使用a，因此绑定ng-click
			$location.url('detail/' + id);
		}
		
		$scope.nextPage = function(){
			$location.search('page', ++$scope.currentPage);
		}
		
		$scope.previousPage = function(){
			$location.search('page', --$scope.currentPage);
		}
	}
]);

lubanlockControllers.controller('UserDetailCtrl', ['$scope', '$routeParams', 'User', 'UserConfig',
	function($scope, $routeParams, User, UserConfig) {
		
		$scope.user = User.get({id: $routeParams.id});
		
		$scope.config = UserConfig.get();
		
		$scope.updateConfig = function(){
			if($scope.newPassword && $scope.newPassword === $scope.newPasswordConfirm){
				$scope.user.password = $scope.newPassword;
			}
			$scope.user.$update();
		}
	}
]);

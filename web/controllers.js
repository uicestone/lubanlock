'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

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

lubanlockControllers.controller('NavCtrl', ['$scope', '$location', 'Nav', 'User',
	function($scope, $location, Nav, User){
		
		$scope.items = Nav.query();
		
		$scope.config = User.getConfig();
		
		$scope.toggleMinimize = function(){
			$scope.config.nav_minimized = !$scope.config.nav_minimized;
			$scope.config.$saveConfig();
		}
		
		$scope.navigateTo = function(item){
			$location.path(item.meta.template ? item.meta.template[0] : 'list').search(angular.fromJson(item.meta.params[0]));
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
	}
]);

lubanlockControllers.controller('ListCtrl', ['$scope', '$location', 'Nav', 'objects',
	function($scope, $location, Nav, objects) {
		//列表分页
		$scope.currentPage = $location.search().page || 1;
		
		$scope.objects = objects;
		// get pagination argument from statusText
		var statusText = $scope.objects.$response.statusText;
		$scope.totalObjects = Number(statusText.match(/(\d+) Objects in Total/)[1]);
		$scope.objectListStart = Number(statusText.match(/(\d+)\-/)[1]);
		$scope.objectListEnd = Number(statusText.match(/\-(\d+)/)[1]);

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

/*
 * This is a common controller for detail page of any object.
 * We can view and modify Tags, Metas, Relatives and Statuses if we are permitted
 */
lubanlockControllers.controller('DetailCtrl', ['$scope', '$location', 'Object', 'User', 'Alert', 'object',
	function($scope, $location, Object, User, Alert, object) {
		
		$scope.angular = angular; // we need call angular.equal() in template
		
		// objectResponse are resolved before route in routeProvider. So page is redirected after data ready.
		$scope.object = object;
		
		// flags for property adding form toggling
		$scope.adding = {meta: false, relative: false, status: false, tag: false, permission: false}
		
		// collection of new property models. 'new' are wrapped in '[]' here because it's a reserved word in ECMA Script 3.
		$scope['new'] = {meta: {}, relative: {}, status: {}, tag: {}, permission: {}};
		
		$scope.openPropAddForm = function(prop, $event){
			$scope.adding[prop] = true;
			// auto-select the first input field after expanding property adding
			// since the form won't expand after above change were applied, we trigger 'select' after a while
			setTimeout(function(){
				angular.element($event.target).siblings('form').find(':input:first').trigger('select');
			});
			if(prop === 'permission'){
				$scope['new'].permission.permission = 'read';
			}
		}
		
		$scope.closePropAddForm = function(prop){
			$scope['new'][prop] = {};
			$scope.adding[prop] = false;
		}
		
		$scope.addMeta = function($event){
			Object.saveMeta({object: $scope.object.id, key: $scope['new'].meta.key}, $scope['new'].meta.value, function(meta){
				$scope.object.meta = meta;
				$scope['new'].meta.value = undefined;
				// we keep the key and select it, for faster continuously input
				angular.element($event.target).find(':input:first').trigger('select');
			});
		}
		
		$scope.removeMeta = function(key, value){
			Object.removeMeta({object: $scope.object.id, key: key, value: value}, function(meta){
				$scope.object.meta = meta;
			});
		}
		
		$scope.addStatus = function($event){
			Object.saveStatus({object: $scope.object.id, name: $scope['new'].status.name, as_rows: true, order_by: 'date desc'}, $scope['new'].status.date, function(status){
				$scope.object.status = status;
				$scope['new'].status = undefined;
				angular.element($event.target).find(':input:first').trigger('select');
			});
		}
		
		$scope.removeStatus = function(name, date){
			Object.removeStatus({object: $scope.object.id, name: name, date: date, as_rows: true, order_by: 'date desc'}, function(status){
				$scope.object.status = status;
			});
		}
		
		$scope.toggleStatusDatepicker = function($event){
			// a 'keng' of angular-ui-bootstrap: button default behavior needs to be prevent to trigger datepick popup
			$event.preventDefault();
			$event.stopPropagation();
			$scope['new'].status.isDatepickerOpen = !$scope['new'].status.isDatepickerOpen;
		}
		
		$scope.addRelative = function($event){
			
			if($scope['new'].relative === undefined || $scope['new'].relative.id === undefined){
				Alert.addAlert('请选择关联对象');
				return;
			}
			
			Object.saveRelative({object: $scope.object.id, relation: $scope['new'].relative.relation}, $scope['new'].relative.id, function(relative){
				$scope.object.relative = relative;
				$scope['new'].relative.id = $scope['new'].relative.name = undefined;
				angular.element($event.target).find(':input:first').trigger('select');
			});
		}
		
		$scope.removeRelative = function(relation, relative){
			Object.removeRelative({object: $scope.object.id, relation: relation, relative: relative}, function(relative){
				$scope.object.relative = relative;
			});
		}
		
		// used in typeahead for relative name auto complete
		$scope.search = function(name){
			// a promise can be parsed by typeahead, no then() wrapping required
			return Object.query({name: {like: name}}).$promise;
		};
		
		$scope.searchUser = function(name){
			// a promise can be parsed by typeahead, no then() wrapping required
			return User.query({name: {like: name}}).$promise;
		};
		
		$scope.onRelativeSelect = function($item){
			$scope['new'].relative.id = $item.id;
		}
		
		$scope.addTag = function($event){
			Object.saveTag({object: $scope.object.id, taxonomy: $scope['new'].tag.taxonomy}, $scope['new'].tag.term, function(tag){
				$scope.object.tag = tag;
				$scope['new'].tag = {};
				angular.element($event.target).find(':input:first').trigger('select');
			});
		}
		
		$scope.showDetail = function(id, type){
			
			if(type === 'file'){
				window.open('/file/download/' + id);
				return;
			}
			
			$location.url('detail/' + id);
		}
		
		$scope.authorize = function(permission, users){
			if(permission === undefined){
				permission = $scope['new'].permission.permission;
			}
			if(users === undefined){
				users = $scope['new'].permission.users;
			}
			Object.authorize({object: $scope.object.id, permission: permission, with_user_info: true}, users, function(permission){
				$scope.object.permission = permission;
			});
		}
		
		$scope.prohibit = function(permission, userId){
			Object.prohibit({object: $scope.object.id, permission: permission, with_user_info: true}, userId, function(permission){
				$scope.object.permission = permission;
			});
		}
		
		$scope.onAuthorizedUserSelected = function($item){
			$scope['new'].permission.users = $item.id;
		}
		
		$scope.remove = function(){
			$scope.object.$remove({}, function(){
				history.back();
			});
		}
		
		/**
		 * check if the current object is allowed for current user on certain action
		 * @param {type} permission
		 * @returns {Boolean}
		 */
		$scope.allow = function(action){
			
			if(!$scope.object.permission){
				return false;
			}
			
			var users = $scope.object.permission[action];
			
			if(users.length === 0){
				if($scope.object.permission.read.length === 0 && 
					$scope.object.permission.write.length === 0 && 
					$scope.object.permission.grant.length === 0 && 
					(
						user.id === $scope.object.user || 
						($scope.object.user === null && $scope.object.id === user.id)
					)
				){
					return true;
				}
				return false;
			}
			for(var i = 0; i < users.length; i++){
				if(users[i].id === user.id){
					return true
				}
			}
			return false;
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

lubanlockControllers.controller('UserDetailCtrl', ['$scope', '$routeParams', 'User',
	function($scope, $routeParams, User) {
		
		$scope.user = User.get({id: $routeParams.id});
		
		$scope.config = User.getConfig();
		
		$scope.updateConfig = function(){
			if($scope.newPassword && $scope.newPassword === $scope.newPasswordConfirm){
				$scope.user.password = $scope.newPassword;
			}
			$scope.user.$update();
		}
	}
]);

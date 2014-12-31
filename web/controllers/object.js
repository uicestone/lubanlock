(function(){

'use strict';

lubanlockControllers.controller('ListCtrl', ['$scope', '$location', '$route', '$modal', 'Head', 'objects',
	function($scope, $location, $route, $modal, Head, objects) {
		
		Head.title('列表');
		
		//列表分页
		$scope.currentPage = $location.search().page || 1;
		
		$scope.objects = objects;
		// get pagination argument from statusText
		var statusText = $scope.objects.$response.statusText;
		$scope.total = Number(statusText.match(/(\d+) Objects in Total/)[1]);
		$scope.listStart = Number(statusText.match(/(\d+)\-/)[1]);
		$scope.listEnd = Number(statusText.match(/\-(\d+)/)[1]);
		
		if($scope.listStart > $scope.listEnd){
			$location.search(angular.extend($location.search(), {page: 1}));
		} 

		$scope.nextPage = function(){
			$location.search('page', ++$scope.currentPage);
		}
		
		$scope.previousPage = function(){
			$scope.currentPage--;
			$location.search('page', $scope.currentPage === 1 ? null : $scope.currentPage);
		}
		
		$scope.reload = function(){
			$route.reload();
		}
		
		//详情页，TODO，待完善，对于符合一定条件的对象，使用特定模板载入
		$scope.showDetail = function(id, type){
			$location.url('detail/' + id);
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

/*
 * This is a common controller for detail page of any object.
 * We can view and modify Tags, Metas, Relatives and Statuses if we are permitted
 */
lubanlockControllers.controller('DetailCtrl', ['$scope', 'Object', 'User', 'Meta', 'Alert', 'Head', 'object',
	function($scope, Object, User, Meta, Alert, Head, object) {
		
		object && Head.title(object.name);
		
		// objectResponse are resolved before route in routeProvider. So page is redirected after data ready.
		$scope.object = object;
		
		// flags for property adding form toggling
		$scope.adding = {meta: false, relative: false, status: false, tag: false, permission: false}
		
		// collection of new property models. 'new' are wrapped in '[]' here because it's a reserved word in ECMA Script 3.
		$scope['new'] = {meta: {}, relative: {}, status: {}, tag: {}, permission: {}};
		
		$scope.permissionTypes = [{name:'read',label:'查看'},{name:'write',label:'修改'},{name:'grant',label:'授权'}];
		
		$scope.metaKeys = Meta.getKeys({type: $scope.object.type});
		
		$scope.openPropAddForm = function(prop, $event){
			$scope.adding[prop] = true;
			// auto-select the first input field after expanding property adding
			// since the form won't expand after above change were applied, we trigger 'select' after a while
			setTimeout(function(){
				angular.element($event.target).siblings('form').find(':input:first').trigger('focus');
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
			
			if(!$scope['new'].meta.key){
				return;
			}
			
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
			
			if(!$scope['new'].status.name){
				return;
			}
			
			Object.saveStatus({object: $scope.object.id, name: $scope['new'].status.name, order_by: 'date desc'}, {date: $scope['new'].status.date, comment:$scope['new'].status.comment}, function(status){
				$scope.object.status = status;
				$scope['new'].status = undefined;
				angular.element($event.target).find(':input:first').trigger('select');
			});
		}
		
		$scope.removeStatus = function(name, date){
			Object.removeStatus({object: $scope.object.id, name: name, date: date, order_by: 'date desc'}, function(status){
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
			
			if(!$scope['new'].relative.relation){
				return;
			}
			
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
		$scope.search = function(keyword){
			// a promise can be parsed by typeahead, no then() wrapping required
			return Object.query({search: keyword}).$promise;
		};
		
		$scope.searchUser = function(name){
			// a promise can be parsed by typeahead, no then() wrapping required
			return User.query({name: {like: name}}).$promise;
		};
		
		$scope.onRelativeSelect = function($item){
			$scope['new'].relative.id = $item.id;
		}
		
		$scope.addTag = function($event){
			
			if(!$scope['new'].tag.taxonomy){
				return;
			}
			
			Object.saveTag({object: $scope.object.id, taxonomy: $scope['new'].tag.taxonomy}, $scope['new'].tag.term, function(tag){
				$scope.object.tag = tag;
				$scope['new'].tag = {};
				angular.element($event.target).find(':input:first').trigger('select');
			});
		}
		
		$scope.removeTag = function(taxonomy){
			Object.saveTag({object: $scope.object.id, taxonomy: taxonomy}, null, function(tag){
				$scope.object.tag = tag;
			});
		}
		
		$scope.urlTo = function(object){
			return '#detail/' + object.id;
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
			
			if(!$scope.object || !$scope.object.permission){
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

})();

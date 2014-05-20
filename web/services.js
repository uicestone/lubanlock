'use strict';

/* Services */

var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

lubanlockServices.factory('Object', ['$resource',
	function($resource){
		return $resource('object/:id', {id: '@id'}, {
			//通过改变Resource请求成功时的返回值来达到获得header的目的 TODO 这样做改变了Resource的行为，可能会有未知问题
			get: {method: 'GET', interceptor: {response: function(response){
				return response;
			}}},
			query: {method: 'GET', isArray: true, interceptor: {response: function(response){
				return response;
			}}},
			update: {method: 'PUT'},
		});
	}
]);

lubanlockServices.factory('ObjectMeta', ['$resource',
	function($resource){
		return $resource('object/:object/meta/:key', {object: '@object', key: '@key'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('ObjectRelative', ['$resource',
	function($resource){
		return $resource('object/:object/relative/:relation', {object: '@object'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('ObjectStatus', ['$resource',
	function($resource){
		return $resource('object/:object/status/:name', {object: '@object'}, {
			save: {method: 'POST', isArray: true},
			update: {method: 'PUT', isArray: true},
			remove: {method: 'DELETE', isArray: true}
		});
	}
]);

lubanlockServices.factory('ObjectTag', ['$resource',
	function($resource){
		return $resource('object/:object/tag/:taxonomy', {object: '@object'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('User', ['$resource',
	function($resource){
		return $resource('user/:id', {id: '@id'}, {
			update: {method: 'PUT'},
		});
	}
]);

lubanlockServices.factory('UserConfig', ['$resource',
	function($resource){
		return $resource('user/config/:item', {item: '@item'});
	}
]);

lubanlockServices.factory('Company', ['$resource',
	function($resource){
		return $resource('company/:id', {id: '@id'});
	}
]);

lubanlockServices.factory('CompanyConfig', ['$resource',
	function($resource){
		return $resource('company/:company/config/:item', {user: 'company', item: '@item'});
	}
]);

// register the interceptor as a service
lubanlockServices.factory('HttpInterceptor', ['$q', '$window', 'Alert', function($q, $window, Alert) {
	return {
		// optional method
		'request': function(config) {
			// do something on success
			return config || $q.when(config);
		},
		// optional method
		'requestError': function(rejection) {
			// do something on error
//			if (canRecover(rejection)) {
//				return responseOrNewPromise
//			}
			return $q.reject(rejection);
		},
		// optional method
		'response': function(response) {
			// do something on success
			return response || $q.when(response);
		},
		// optional method
		'responseError': function(rejection) {
//			if (canRecover(rejection)) {
//				return responseOrNewPromise
//			}
			Alert.addAlert(eval(rejection.headers()['status-text']));
			
			return $q.reject(rejection);
		}
	};
}]);

lubanlockServices.factory('Alert', ['$rootScope', '$timeout', function($rootScope, $timeout){
	
	var alerts = [];
	
	//监控路由导航状态并给予提示信息 TODO 应该分离到消息服务之外
	
	var fastRouteChangeTimeout;
	var slowRouteChangeTimeout;
	
	$rootScope.$on('$routeChangeStart', function(){
		
		fastRouteChangeTimeout = $timeout(function(){
			alerts.push({message: '正在加载...'});
		}, 200);
		
		slowRouteChangeTimeout = $timeout(function(){
			alerts.push({message: '仍在继续...'});
		}, 5000);
	});
	
	$rootScope.$on('$routeChangeSuccess', function(){
		//TODO 消息应该可以根据ID删除，这也就意味着消息插入的时候要有ID
		$timeout.cancel(fastRouteChangeTimeout);
		$timeout.cancel(slowRouteChangeTimeout);
		alerts.pop();
	});
	
	return {
		getAlerts: function(){
			return alerts;
		},
		addAlert: function (message) {
            alerts.push({ message: message });
        },
        closeAlert: function (index) {
            alerts.splice(index, 1);
        }
	};
}]);

lubanlockServices.service('Nav', ['$resource',
	function($resource){
		
		var Resource = $resource('nav/:name', {name:'@name'});
		var items = Resource.query();
		
		return {
			query: function(){
				return items;
			},
			save: function(data, success){
				var item = new Resource(data);
				items.push(item);
				item.$save({}, function(){
					success();
				});
			},
			remove: function(item, success){
				var index;
				for(index in items){
					if(items[index].name === item.name){
						items.splice(index, 1);
					}
				}
				
				item.$remove({}, function(){
					angular.isFunction(success) && success();
				});
			}
		};
		
	}
]);

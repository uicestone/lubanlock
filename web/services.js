'use strict';

/* Services */

var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

lubanlockServices.service('Object', ['$resource',
	function($resource){
		
		var responseInterceptor = function(response){
			if(response.data === 'null'){
				return null;
			}
			response.resource.$response = response;
			return response.resource;
		}
		
		return $resource('object/:id', {id: '@id'}, {
			get: {method: 'GET', interceptor: {response: responseInterceptor}},
			query: {method: 'GET', isArray: true, interceptor: {response: responseInterceptor}},
			update: {method: 'PUT', interceptor: {response: responseInterceptor}},
			getMeta: {method: 'GET', url: 'object/:object/meta/:key', interceptor: {response: responseInterceptor}},
			saveMeta: {method: 'POST', url: 'object/:object/meta/:key', interceptor: {response: responseInterceptor}},
			updateMeta: {method: 'PUT', url: 'object/:object/meta/:key', interceptor: {response: responseInterceptor}},
			removeMeta: {method: 'DELETE', url: 'object/:object/meta/:key', interceptor: {response: responseInterceptor}},
			getRelative: {method: 'GET', url: 'object/:object/relative/:relation'},
			saveRelative: {method: 'POST', url: 'object/:object/relative/:relation', interceptor: {response: responseInterceptor}},
			removeRelative: {method: 'DELETE', url: 'object/:object/relative/:relation', interceptor: {response: responseInterceptor}},
			getStatus: {method: 'GET', url: 'object/:object/status/:name', isArray: true},
			saveStatus: {method: 'POST', url: 'object/:object/status/:name', isArray: true},
			updateStatus: {method: 'PUT', url: 'object/:object/status/:name', isArray: true},
			removeStatus: {method: 'DELETE', url: 'object/:object/status/:name', isArray: true},
			getTag: {method: 'GET', url: 'object/:object/tag/:taxonomy'},
			saveTag: {method: 'POST', url: 'object/:object/tag/:taxonomy', interceptor: {response: responseInterceptor}},
			removeTag: {method: 'DELETE', url: 'object/:object/tag/:taxonomy', interceptor: {response: responseInterceptor}}
		});
	}
]);

lubanlockServices.service('User', ['$resource',
	function($resource){
		return $resource('user/:id', {id: '@id'}, {
			update: {method: 'PUT'},
			getConfig: {method: 'GET', url:'user/config/:item'},
			saveConfig: {method: 'POST', url:'user/config/:item'}
		});
	}
]);

lubanlockServices.service('Company', ['$resource',
	function($resource){
		return $resource('company/:id', {id: '@id'}, {
			update: {method: 'PUT'},
			getConfig: {method: 'GET', url:'company/config/:item'},
			saveConfig: {method: 'POST', url:'company/config/:item'}
		});
	}
]);

// register the interceptor as a service
lubanlockServices.service('HttpInterceptor', ['$q', '$window', 'Alert', function($q, $window, Alert) {
	
	return {
		request: function(config) {
			return config || $q.when(config);
		},
		requestError: function(rejection) {
			return $q.reject(rejection);
		},
		response: function(response) {
			response.statusText = angular.fromJson('"' + response.statusText + '"');
			return response || $q.when(response);
		},
		responseError: function(rejection) {
			rejection.statusText = angular.fromJson('"' + rejection.statusText + '"');
			Alert.addAlert(rejection.statusText);
			return $q.reject(rejection);
		}
	};
}]);

lubanlockServices.service('Alert', ['$rootScope', '$timeout', function($rootScope, $timeout){
	
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

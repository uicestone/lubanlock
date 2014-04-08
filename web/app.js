'use strict';

/* App Module */

var lubanlockApp = angular.module('lubanlockApp', [
	'ngRoute',
	'lubanlockControllers',
	'lubanlockDirectives',
	'lubanlockFilters',
	'lubanlockServices',
	'ui.bootstrap'
]);

lubanlockApp.config(['$routeProvider', '$httpProvider',
	function($routeProvider, $httpProvider) {
		$routeProvider
			.when('/user', {
				templateUrl: 'partials/list_user.html',
				controller: 'UsersCtrl'
			})
			.when('/user/:id', {
				templateUrl: 'partials/detail_user.html',
				controller: 'UserDetailCtrl'
			})
			.when('/list', {
				templateUrl: 'partials/list.html',
				controller: 'ListCtrl',
				resolve: {
					objectsResponse: ['Object', '$route', function(Object, $route){
						return Object.query(angular.extend({with_status: {as_rows: true}, with_tag: true}, $route.current.params)).$promise;
					}]
				}
			})
			.when('/detail/:id?', {
				templateUrl: 'partials/detail.html',
				controller: 'DetailCtrl',
				//TODO 模版也需要在路由执行前预加载
				resolve: {
					objectResponse: ['$route', 'Object', function($route, Object){
						if($route.current.params.id){
							return Object.get({id: $route.current.params.id, with_status: {as_rows: true, order_by:'date desc'}}).$promise;
						}
					}]
				}
			})
			.otherwise({
				redirectTo: '/list'
			});
			
		$httpProvider.interceptors.push('HttpInterceptor');

	}
	
]);

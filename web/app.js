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

lubanlockApp.config(['$routeProvider', '$httpProvider', '$parseProvider',
	function($routeProvider, $httpProvider, $parseProvider) {
		$routeProvider
			.when('/dashboard', {
				templateUrl: 'partials/dashboard.html',
				controller: 'DashboardCtrl'
			})
			.when('/user', {
				templateUrl: 'partials/list_user.html',
				controller: 'UsersCtrl',
				resolve: {
					users: ['User', '$route', function(User, $route){
						return User.query($route.current.params).$promise;
					}]
				}
			})
			.when('/user/:id', {
				templateUrl: 'partials/detail_user.html',
				controller: 'UserDetailCtrl',
				resolve: {
					user: ['$route', 'User', function($route, User){
						if($route.current.params.id && $route.current.params.id !== 'add'){
							return User.get({id: $route.current.params.id}).$promise;
						}
					}]
//					config: ['$route', 'User', function($route, User){
//						if($route.current.params.id){
//							return User.getConfig({id: $route.current.params.id}).$promise;
//						}
//					}]
				}
			})
			.when('/list', {
				templateUrl: 'partials/list.html',
				controller: 'ListCtrl',
				resolve: {
					objects: ['Object', '$route', function(Object, $route){
						return Object.query(angular.extend({with_status: true, with_tag: true}, $route.current.params)).$promise;
					}]
				}
			})
			.when('/detail/:id?', {
				templateUrl: 'partials/detail.html',
				controller: 'DetailCtrl',
				resolve: {
					object: ['$route', 'Object', function($route, Object){
						if($route.current.params.id){
							return Object.get({id: $route.current.params.id, with_status: {order_by:'date desc'}, with_permission: {with_user_info: true}}).$promise;
						}
					}],
					templateEditable: ['$http', '$templateCache', function($http, $templateCache){
						$http.get('partials/editable.html', {cache: $templateCache});
					}]
				}
			})
			.otherwise({
				redirectTo: '/dashboard'
			});
			
		$httpProvider.interceptors.push('HttpInterceptor');
		
		$parseProvider.unwrapPromises(true);

	}
	
]);

var lubanlockApp = angular.module('lubanlockApp', [
	'ngRoute',
	'lubanlockControllers',
	'lubanlockDirectives',
	'lubanlockFilters',
	'lubanlockServices',
	'ui.bootstrap',
	'angularFileUpload'
]);

var lubanlockControllers = angular.module('lubanlockControllers', []);
var lubanlockDirectives = angular.module('lubanlockDirectives', []);
var lubanlockFilters = angular.module('lubanlockFilters', []);
var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

(function(){

'use strict';

/* App Module */

lubanlockApp.config(['$routeProvider', '$httpProvider', '$parseProvider',
	function($routeProvider, $httpProvider, $parseProvider) {
		$routeProvider
			.when('/dashboard', {
				templateUrl: 'partials/dashboard.html',
				controller: 'DashboardCtrl'
			})
			.when('/user', {
				templateUrl: 'partials/user/list.html',
				controller: 'UsersCtrl',
				resolve: {
					users: ['User', '$route', function(User, $route){
						return User.query($route.current.params).$promise;
					}]
				}
			})
			.when('/user/:id', {
				templateUrl: 'partials/user/detail.html',
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
						return Object.query(angular.extend({with_status: true, with_tag: true, with_meta: true}, $route.current.params)).$promise;
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
			.when('/dialog', {
				templateUrl: 'partials/dialog/list.html',
				controller: 'DialogListCtrl',
				resolve: {
					dialogs: ['Object', '$route', function(Object, $route){
						if(!$route.current.params.id){
							return Object.query({type: '对话', user: 'ME', with: {meta: true, relative: {relation: 'message', order_by: 'time desc', limit: 1, raw_key_name: true}}}).$promise;
						}
					}]
				}
			})
			.when('/dialog/new', {
				templateUrl: 'partials/dialog/new_message.html',
				controller: 'DialogNewCtrl',
				resolve: {
				}
			})
			.when('/dialog/:id', {
				templateUrl: 'partials/dialog/messages.html',
				controller: 'DialogMessageCtrl',
				resolve: {
					dialog: ['Object', '$route', function(Object, $route){
						if($route.current.params.id){
							return Object.get({id: $route.current.params.id, with: {meta: true}}).$promise;
						}
					}],
					messages: ['Object', '$route', function(Object, $route){
						if($route.current.params.id){
							return Object.query({type: '消息', is_relative_of: {'message': $route.current.params.id}, with: {meta: true, relative: {with: {meta: true}, raw_key_name: true}}, with_user_info: true}).$promise;
						}
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

lubanlockApp.run(['Head', '$rootScope',
	function(Head, $rootScope){
		$rootScope.$on('$routeChangeSuccess', function (event, current, previous){
			Head.title('');
		});
	}
]);

})();

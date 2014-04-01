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
				controller: 'ListCtrl'
			})
			.when('/detail/:id?', {
				templateUrl: 'partials/detail.html',
				controller: 'DetailCtrl'
			})
			.otherwise({
				redirectTo: '/list'
			});
			
		$httpProvider.interceptors.push('HttpInterceptor');

	}
	
]);

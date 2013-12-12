'use strict';

/* App Module */

var lubanlockApp = angular.module('lubanlockApp', [
	'ngRoute',
	'lubanlockControllers',
	'lubanlockFilters',
	'lubanlockServices'
]);

lubanlockApp.config(['$routeProvider',
	function($routeProvider) {
		$routeProvider
			.when('/list', {
				templateUrl: 'partials/list.html',
				controller: 'ListCtrl'
			})
			.when('/detail/:id', {
				templateUrl: 'partials/detail.html',
				controller: 'DetailCtrl'
			})
			.otherwise({
				redirectTo: '/list'
			});
	}
]);

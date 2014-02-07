'use strict';

/* App Module */

var lubanlockApp = angular.module('lubanlockApp', [
	'ngRoute',
	'lubanlockControllers',
	'lubanlockDirectives',
	'lubanlockFilters',
	'lubanlockServices'
]);

lubanlockApp.config(['$routeProvider',
	function($routeProvider) {
		$routeProvider
			.when('/jobs', {
				templateUrl: 'partials/list_job.html',
				controller: 'JobsCtrl'
			})
			.when('/job-detail/:id', {
				templateUrl: 'partials/detail_job.html',
				controller: 'JobDetailCtrl'
			})
			.when('/my-resume', {
				templateUrl: 'partials/my_resume.html',
				controller: 'MyResumeCtrl'
			})
			.when('/list', {
				templateUrl: 'partials/list.html',
				controller: 'ListCtrl'
			})
			.when('/detail/:id', {
				templateUrl: 'partials/detail.html',
				controller: 'DetailCtrl'
			})
			.otherwise({
				redirectTo: '/jobs'
			});
	}
]);

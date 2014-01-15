'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('ListCtrl', ['$scope', '$location', '$routeParams', 'Object',
	function($scope, $location, $routeParams, Object) {
		$scope.objects = Object.query($routeParams);
		$scope.showDetail = function(objectId){
			$location.path('/detail/' + objectId);
		};
	}
]);

lubanlockControllers.controller('DetailCtrl', ['$scope', '$routeParams', 'Object',
	function($scope, $routeParams, Object) {
		$scope.object = Object.get({id: $routeParams.id});
	}
]);

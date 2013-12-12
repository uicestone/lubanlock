'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('ListCtrl', ['$scope', '$location', 'Object',
	function($scope, $location, Object) {
		$scope.objects = Object.query({get_status:true, get_tag:true});
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

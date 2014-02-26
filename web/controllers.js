'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('ListCtrl', ['$scope', '$routeParams', 'Object',
	function($scope, $routeParams, Object) {
		$scope.objects = Object.query($routeParams);
	}
]);

lubanlockControllers.controller('DetailCtrl', ['$scope', '$routeParams', 'Object',
	function($scope, $routeParams, Object) {
		$scope.object = Object.get({id: $routeParams.id});
	}
]);

lubanlockControllers.controller('JobsCtrl', ['$scope', 'Object', '$routeParams', 
	function($scope, Object, $routeParams){
		var query = {
			type: 'job',
			with_meta: true
		};
		
		if($routeParams.favorite === 'favorite'){
			query.is_relative_of = user.id
		}
		
		$scope.jobs = Object.query(query);
		
		$scope.showJobDetail = function(id){
			window.location.hash = '/job/' + id;
		}
	}
]);

lubanlockControllers.controller('JobDetailCtrl', ['$scope', 'Object', '$routeParams',
	function($scope, Object, $routeParams){
		$scope.object = Object.get({id: $routeParams.id});
	}
]);

lubanlockControllers.controller('MyResumeCtrl', ['$scope', 'Object',
	function($scope, Object){
		$scope.my = Object.get({id: user.id});
	}
]);

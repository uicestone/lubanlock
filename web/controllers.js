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

lubanlockControllers.controller('JobsCtrl', ['$scope', 'Object', '$routeParams', '$location',
	function($scope, Object, $routeParams, $location){
		var query = {
			type: 'job',
			with_meta: true
		};
		
		if($routeParams.favorite === 'favorite'){
			query.is_relative_of = user.id
		}
		
		$scope.jobs = Object.query(query);
		
		$scope.showJobDetail = function(id){
			$location.path('job/' + id);
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
		$scope.value = '女';
		$scope.my = Object.get({id: user.id});
		$scope.genders = ["男","女"];
		$scope.grades = ["2010级","2011级","2012级"];
		$scope.resumes = Object.query({type: 'file', user: user.id, with_meta: true}, function(){
			console.log('resource resolved');
		});
	}
]);

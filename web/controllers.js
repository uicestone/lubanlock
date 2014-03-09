'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('ListCtrl', ['$scope', '$routeParams', 'Object', '$location',
	function($scope, $routeParams, Object, $location) {
		
		$scope.objects = Object.query($routeParams);
		
		$scope.showDetail = function(id){
			//因为使用了表格，无法使用a，因此绑定ng-click
			$location.path('/detail/' + id);
		}
	}
]);

lubanlockControllers.controller('DetailCtrl', ['$scope', '$routeParams', 'Object', 'ObjectMeta',
	function($scope, $routeParams, Object, ObjectMeta) {
		
		if($routeParams.id !== undefined){
			$scope.object = Object.get({id: $routeParams.id});
		}
		
		$scope.addingMeta = false;
		
		$scope.addMeta = function(){
			$scope.addingMeta = true;
		}
		
		$scope.submitMeta = function(){
			
			if($scope.object.meta[$scope.newMetaKey] === undefined){
				$scope.object.meta[$scope.newMetaKey] = [];
			}
			
			$scope.object.meta[$scope.newMetaKey].push($scope.newMetaValue);
			
			//$scope.object.$saveMeta();
		}
		
		$scope.removeMeta = function(key, value){
			$scope.object.$removeMeta({key: key, value: value}, {}, function(){
				$scope.object
				//$scope.object.meta = meta === [] ? {} : meta;
			});
		}
	}
]);

lubanlockControllers.controller('UsersCtrl', ['$scope', 'Object',
	function($scope, Object){
		$scope.users = Object.query({type: 'user'});
	}
]);

lubanlockControllers.controller('UserDetailCtrl', ['$scope', '$routeParams', 'Object',
	function($scope, $routeParams, Object) {
		$scope.user = Object.get({id: $routeParams.id});
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

lubanlockControllers.controller('JobDetailCtrl', ['$scope', 'Object', '$routeParams', '$modal', 'ObjectMeta',
	function($scope, Object, $routeParams, $modal, ObjectMeta) {
		
		$scope.job = Object.get({id: $routeParams.id});

		$scope.myResumes = Object.query({type: 'file', user: user.id});
		
		$scope.showApplyForm = function() {

			var modalInstance = $modal.open({
				templateUrl: 'partials/job_application_form.html',
				scope: $scope,
				controller: function($scope, $modalInstance) {
					$scope.applicationForm = {};//使用单一变量并提前初始化，绕过ui.bootstrap的scope issue
			
					$scope.ok = function() {

						Object.save({
							type: '职位申请',
							name: user.name + '申请' + $scope.job.name,
							meta: {'求职信': $scope.applicationForm.coverLetter},
							relative: {
								'申请人': user.id,
								'职位': $scope.job.id,
								'简历': $scope.applicationForm.resume.id
							}
						});
						
						$modalInstance.close($scope.applicationForm);
						
					};

					$scope.cancel = function() {
						$modalInstance.dismiss('cancel');
					};
				}
				
			});

			modalInstance.result.then(function(applicationForm) {
				$scope.applicationForm = applicationForm;
			});
		};

	}
]);

lubanlockControllers.controller('MyResumeCtrl', ['$scope', 'Object',
	function($scope, Object){
		$scope.my = Object.get({id: user.id});
		$scope.genders = ["男","女"];
		$scope.grades = ["2010级","2011级","2012级"];
		$scope.resumes = Object.query({type: 'file', user: user.id, with_meta: true});
	}
]);

'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('ListCtrl', ['$scope', '$routeParams', 'Object', '$location',
	function($scope, $routeParams, Object, $location) {
		
		$scope.objects = Object.query(angular.extend({with_status: {as_rows: true}, with_tag: true}, $routeParams));
		$scope.currentPage = $location.search().page || 1;
		
		$scope.showDetail = function(id, type){
			
			if(type === 'file'){
				window.open('/file/download/' + id);
				return;
			}
			
			$location.url('detail/' + id);
		}
		
		$scope.nextPage = function(){
			$location.search('page', ++$scope.currentPage);
		}
		
		$scope.previousPage = function(){
			$location.search('page', --$scope.currentPage);
		}
	}
]);

lubanlockControllers.controller('DetailCtrl', ['$scope', '$routeParams', 'Object', 'ObjectMeta', '$location',
	function($scope, $routeParams, Object, ObjectMeta, $location) {
		
		if($routeParams.id !== undefined){
			$scope.object = Object.get({id: $routeParams.id, with_status: {as_rows: true, order_by:'date desc'}});
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
			
			ObjectMeta.save({object: $scope.object.id, key: $scope.newMetaKey}, $scope.newMetaValue);
			
		}
		
		$scope.removeMeta = function(key, value){
			ObjectMeta.remove({object: $scope.object.id, key: key, value: value}, function(meta){
				$scope.object.meta = meta;
			});
		}
		
		$scope.showDetail = function(id, type){
			
			if(type === 'file'){
				window.open('/file/download/' + id);
				return;
			}
			
			$location.url('detail/' + id);
		}
		
	}
]);

lubanlockControllers.controller('UsersCtrl', ['$scope', '$routeParams', 'User', '$location',
	function($scope, $routeParams, User, $location) {
		
		$scope.users = User.query($routeParams);
		$scope.currentPage = $location.search().page || 1;
		
		$scope.showDetail = function(id){
			//因为使用了表格，无法使用a，因此绑定ng-click
			$location.url('detail/' + id);
		}
		
		$scope.nextPage = function(){
			$location.search('page', ++$scope.currentPage);
		}
		
		$scope.previousPage = function(){
			$location.search('page', --$scope.currentPage);
		}
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
			$location.url('job/' + id);
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

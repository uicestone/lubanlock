'use strict';

/* Controllers */

var lubanlockControllers = angular.module('lubanlockControllers', []);

lubanlockControllers.controller('AlertCtrl', ['$scope', 'Alert',
	function($scope, Alert){
		
		$scope.alerts = Alert.getAlerts();
		
		$scope.$watch('alerts.length', function(length){
			
			if(length === 0){
				return;
			}
			
			$scope.alert = $scope.alerts[$scope.alerts.length - 1];
		});
		
	}
]);

lubanlockControllers.controller('ListCtrl', ['$scope', '$routeParams', 'Object', '$location',
	function($scope, $routeParams, Object, $location) {
		
		$scope.currentPage = $location.search().page || 1;
		
		$scope.objects = Object.query(angular.extend({with_status: {as_rows: true}, with_tag: true}, $routeParams), function(value, responseHeaders){
			var statusText = eval(responseHeaders()['status-text']);
			$scope.totalObjects = Number(statusText.match(/(\d+) Objects in Total/)[1]);
			$scope.objectListStart = Number(statusText.match(/(\d+) \-/)[1]);
			$scope.objectListEnd = Number(statusText.match(/\- (\d+)/)[1]);
		});
		
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

lubanlockControllers.controller('DetailCtrl', ['$scope', '$routeParams', 'Object', 'ObjectMeta', 'ObjectRelative', 'ObjectStatus', 'ObjectTag', '$location',
	function($scope, $routeParams, Object, ObjectMeta, ObjectRelative, ObjectStatus, ObjectTag, $location) {
		
		if($routeParams.id !== undefined){
			$scope.object = Object.get({id: $routeParams.id, with_status: {as_rows: true, order_by:'date desc'}});
		}
		
		$scope.adding = {
			meta: false,
			relative: false,
			status: false,
			tag: false
		}
		
		$scope.new = {};
		
		$scope.openPropAddForm = function(prop){
			$scope.adding[prop] = true;
		}
		
		$scope.closePropAddForm = function(prop){
			$scope.new[prop] = {};
			$scope.adding[prop] = false;
		}
		
		$scope.addMeta = function($event){
			
			ObjectMeta.save({object: $scope.object.id, key: $scope.new.meta.key}, $scope.new.meta.value, function(){
				
				if($scope.object.meta[$scope.new.meta.key] === undefined){
					$scope.object.meta[$scope.new.meta.key] = [];
				}

				$scope.object.meta[$scope.new.meta.key].push($scope.new.meta.value);
			
				$scope.new.meta.value = undefined;
				angular.element($event.target).children(':input:first').trigger('select');
			});
			
		}
		
		$scope.removeMeta = function(key, value){
			ObjectMeta.remove({object: $scope.object.id, key: key, value: value}, function(meta){
				$scope.object.meta = meta;
			});
		}
		
		$scope.addStatus = function(){
			
			ObjectStatus.save({object: $scope.object.id, name: $scope.new.status.name, as_rows: true, order_by: 'date desc'}, $scope.new.status.date, function(status){
				$scope.object.status = status;
				$scope.new.status = {};
			});
			
		}
		
		$scope.removeStatus = function(name, date){
			ObjectStatus.remove({object: $scope.object.id, name: name, date: date, as_rows: true, order_by: 'date desc'}, function(status){
				$scope.object.status = status;
			});
		}
		
		$scope.addTag = function(){
			ObjectTag.save({object: $scope.object.id, taxonomy: $scope.new.tag.taxonomy}, $scope.new.tag.term, function(tag){
				$scope.object.tag = tag;
			});
		}
		
		$scope.showDetail = function(id, type){
			
			if(type === 'file'){
				window.open('/file/download/' + id);
				return;
			}
			
			$location.url('detail/' + id);
		}
		
		$scope.remove = function(){
			$scope.object.$remove({}, function(){
				history.back();
			});
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

lubanlockControllers.controller('UserDetailCtrl', ['$scope', '$routeParams', 'User', 'UserConfig',
	function($scope, $routeParams, User, UserConfig) {
		$scope.user = User.get({id: $routeParams.id});
		$scope.updateConfig = function(){
			if($scope.newPassword && $scope.newPasswordConfirm){
				$scope.user.password = $scope.newPassword;
				$scope.user.update();
			}
		}
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

(function(){

'use strict';

lubanlockControllers.controller('DialogMessageCtrl', ['$scope', '$interval', '$upload', '$filter', 'Object', 'dialog', 'messages',
	function($scope, $interval, $upload, $filter, Object, dialog, messages){
		
		$scope.dialog = dialog;
		$scope.messages = messages;
		$scope.attachments = [];
		
		$scope.relevant_dialogs = Object.query({type: '对话', num: $scope.dialog.num, limit: false, with_user_info: true});
		
		var messagePolling = $interval(function(){
			Object.query({
				type: '消息',
				is_relative_of: {'message': dialog.id},
				'with': {meta: true, relative: {'with': {meta: true}}},
				with_user_info: true
			}, function(messages){
				if($scope.messages.$response.statusText !== messages.$response.statusText){
					$scope.messages = messages;
				}
			});
		}, 3000);
		
		$scope.$on('$destroy', function(){
			$interval.cancel(messagePolling);
		});
		
		$scope.addAttachment = function ($files) {
			for (var i = 0; i < $files.length; i++) {
				var file = $files[i];
				$scope.upload = $upload.upload({
					url: 'file/upload',
					data: {},
					file: file
				}).progress(function (evt) {
					console.log('percent: ' + parseInt(100.0 * evt.loaded / evt.total));
				}).success(function (data) {
					$scope.attachments.push(data);
				});
			}
		};
		
		$scope.removeAttachment = function(attachment){
			$scope.attachments = $scope.attachments.filter(function(item){
				return item.id !== attachment.id;
			});
		}
		
		$scope.send = function(){
			
			var message = new Object();
			
			message.name = $scope.newMessageContent.substr(0, 255);
			message.type = '消息';
			message.meta = {content: $scope.newMessageContent};
			message.parents = {message: $scope.relevant_dialogs.map(function(element){
				return element.id;
			})};
			
			message.relative = {attachment: $scope.attachments.map(function(attachment){
				return attachment.id;
			})};

			message.$save({with_user_info: true}, function(data){
				$scope.messages.unshift(data);
				$scope.attachments = [];
				$scope.newMessageContent = undefined;
			});
		}
		
		$scope.remove = function(message){
			Object.removeRelative({object: $scope.dialog.id, relation: 'message', relative: message.id, raw_key_name: true, id_only: true}, function(){
				$scope.messages = $filter('filter')($scope.messages, {id: '!' + message.id});
			});
		}
	}
]);

lubanlockControllers.controller('DialogNewCtrl', ['$scope', '$upload', '$http', 'User', 'Object',
	function($scope, $upload, $http, User, Object){
		
		$scope.groups = User.query({is_group: 1, limit: false, parents: {member: {num: 'root'}}});
		
		$scope.receivers = [];
		
		$scope.attachments = [];
		
		$scope.expandMembers = function(group){
			
			if((group.relative && group.relative.member) || !group.is_group){
				$scope.addReceiver(group);
				return;
			}
			
			group.relative = Object.getRelative({object: group.id, relation: 'member', is_user: true});
		}
		
		$scope.foldMembers = function(group, $event){
			$event.stopPropagation();
			group.relative.member = null;
		}
		
		$scope.searchReceiver = function(keyword){
			return User.query({search: keyword}).$promise;
		}
		
		$scope.addReceiver = function(user){
			if(user.$inReceiveList){
				return;
			}
			user.$inReceiveList = true;
			$scope.receivers.push(user);
		}
		
		$scope.removeReceiver = function(user){
			user.$inReceiveList = false;
			$scope.receivers = $scope.receivers.filter(function(item){
				return item.id !== user.id;
			});
		}
		
		$scope.addAttachment = function ($files) {
			for (var i = 0; i < $files.length; i++) {
				var file = $files[i];
				$scope.upload = $upload.upload({
					url: 'file/upload',
					data: {},
					file: file
				}).progress(function (evt) {
					console.log('percent: ' + parseInt(100.0 * evt.loaded / evt.total));
				}).success(function (data) {
					$scope.attachments.push(data);
				});
			}
		};
		
		$scope.removeAttachment = function(attachment){
			$scope.attachments = $scope.attachments.filter(function(item){
				return item.id !== attachment.id;
			});
		}
		
		$scope.send = function(){
			$http.post('message', {
				title: $scope.messageTitle,
				content: $scope.messageContent,
				receivers: $scope.receivers.map(function(item){return item.id}),
				attachments: $scope.attachments.map(function(item){return item.id}),
			}).success(function(){
				history.back();
			});
		}
		
	}
]);

lubanlockControllers.controller('DialogListCtrl', ['$scope', 'dialogs',
	function($scope, dialogs){
		$scope.dialogs = dialogs;
	}
]);

})();

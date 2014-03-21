'use strict';

/* Services */

var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

lubanlockServices.factory('Object', ['$resource',
	function($resource){
		return $resource('object/:id', {id: '@id'}, {
			update: {method: 'PUT'},
		});
	}
]);

lubanlockServices.factory('ObjectMeta', ['$resource',
	function($resource){
		return $resource('object/:object/meta/:key?', {object: '@object', key: '@key'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('ObjectRelative', ['$resource',
	function($resource){
		return $resource('object/:object/relative/:relation', {object: '@object'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('ObjectStatus', ['$resource',
	function($resource){
		return $resource('object/:object/status/:name', {object: '@object'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('ObjectTag', ['$resource',
	function($resource){
		return $resource('object/:object/tag', {object: '@object'}, {
			update: {method: 'PUT'}
		});
	}
]);

lubanlockServices.factory('User', ['$resource',
	function($resource){
		return $resource('user/:id', {id: '@id'}, {
			updatePassword: {method: 'PUT'},
		});
	}
]);

lubanlockServices.factory('UserConfig', ['$resource',
	function($resource){
		return $resource('user/:user/config/:item', {user: '@user', item: '@item'});
	}
]);

lubanlockServices.factory('Company', ['$resource',
	function($resource){
		return $resource('company/:id', {id: '@id'});
	}
]);

lubanlockServices.factory('CompanyConfig', ['$resource',
	function($resource){
		return $resource('company/:company/config/:item', {user: 'company', item: '@item'});
	}
]);

// register the interceptor as a service
lubanlockServices.factory('HttpInterceptor', ['$q', '$window', 'Alert', function($q, $window, Alert) {
	return {
		// optional method
		'request': function(config) {
			// do something on success
			return config || $q.when(config);
		},
		// optional method
		'requestError': function(rejection) {
			// do something on error
//			if (canRecover(rejection)) {
//				return responseOrNewPromise
//			}
			return $q.reject(rejection);
		},
		// optional method
		'response': function(response) {
			// do something on success
			return response || $q.when(response);
		},
		// optional method
		'responseError': function(rejection) {
//			if (canRecover(rejection)) {
//				return responseOrNewPromise
//			}
			
			Alert.addAlert(rejection.status);
			
			return $q.reject(rejection);
		}
	};
}]);

lubanlockServices.factory('Alert', [function(){
	var alerts = [];
	
	return {
		getAlerts: function(){
			return alerts;
		},
		addAlert: function (message) {
            alerts.push({ message: message });
        },
        closeAlert: function (index) {
            alerts.splice(index, 1);
        }
	};
}]);

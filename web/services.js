'use strict';

/* Services */

var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

lubanlockServices.factory('Object', ['$resource','$http',
	function($resource,$http) {
		$http({method:'GET', url: 'object'});
		//return $resource('object/:id');
	}
]);
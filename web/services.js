'use strict';

/* Services */

var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

lubanlockServices.factory('Object', ['$resource',
	function($resource) {
		return $resource('object/:id');
	}
]);
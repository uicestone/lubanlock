'use strict';

/* Services */

var lubanlockServices = angular.module('lubanlockServices', ['ngResource']);

lubanlockServices.factory('Object', ['$resource',
	function($resource){
		return $resource('object/:id', {id: '@id'}, {
			update: {method: 'PUT'},
			getMeta: {method: 'GET', url: 'object/:id/meta'},
			saveMeta: {method: 'POST', url: 'object/:id/meta'},
			updateMeta: {method: 'PUT', url: 'object/:id/meta'},
			removeMeta: {method: 'DELETE', url: 'object/:id/meta'},
		});
	}
]);

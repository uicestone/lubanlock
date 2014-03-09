'use strict';

/* Filters */

var lubanlockFilters = angular.module('lubanlockFilters', []);

lubanlockFilters.filter('plain', function(){
	return function(input){
		if(angular.isArray(input)){
			return input.join(', ');
		}
		
		return input;
	}
});
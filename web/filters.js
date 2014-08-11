'use strict';

/* Filters */

var lubanlockFilters = angular.module('lubanlockFilters', []);

/**
 * convert an array or an object to a human friendly string
 */
lubanlockFilters.filter('plain', function(){
	return function(input){
		
		if(angular.isObject(input)){
			input = angular.element.map(input, function(value){
				return value;
			});
		}
		
		if(angular.isArray(input)){
			input = input.join(', ');
		}
		
		return input;
	}
});

/**
 * select a key of elements from an array to a new plain array
 */
lubanlockFilters.filter('select', function(){
	return function(input, select){
		
		if(!angular.isArray(input)){
			return input;
		}
		
		input = angular.element.map(input, function(value){
			return value[select];
		});
		
		return input;
	}
});

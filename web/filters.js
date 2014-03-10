'use strict';

/* Filters */

var lubanlockFilters = angular.module('lubanlockFilters', []);

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
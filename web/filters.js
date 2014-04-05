'use strict';

/* Filters */

var lubanlockFilters = angular.module('lubanlockFilters', []);

/**
 * 将对象或数组转化为逗号分隔字符串显示
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
 * 一个数组的对象中取一个属性成为一个一维数组
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

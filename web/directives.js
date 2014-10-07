'use strict';

/* Directives */
var lubanlockDirectives = angular.module('lubanlockDirectives', []);

lubanlockDirectives.directive('lubanEditable', ['$location', 'Object', function($location, Object){
	return {
		restrict: 'A',				//此指令通过HTML属性方式调用
		templateUrl: 'partials/editable.html',
		transclude: true,			//支持将HTML标签内部的内容追加到可编辑内容之后
		scope:{
			object: '=',				//正在编辑的对象
			value: '=lubanEditable',	//可编辑字段的值
			name: '@lubanEditable',	//字段的值表达式，用以正则匹配获取属性类型或属性键名
			model:'@',				//当luban-editable为引用值时，此值为字段表达式
			type: '@',				//input的类型，可选text, radio, select
			options: '=',				//input:radio和select的可用选项
			placeholder: '@',		//input:text的placeholder
			key:'='					//在模版中手动指定的meta key TODO
		},
		link: function(scope, element){
			
			scope.name = scope.model || scope.name;
			
			//从值表达式中获得属性类型，为.之后[之前的字符串
			scope.prop = scope.name.match(/\.([^.^\[]*)/)[1];
			
			//如果整个object都是undefined，说明没get过，则需要在首次更改时创建对象
			if(scope.object === undefined){
				scope.inAddMode = true;
				scope.object = {};
			}
			
			scope.isEditing = scope.inAddMode;
			
			//监控对象资源的请求状态
			scope.$watch('object.$resolved', function($resolved){
				//资源请求完成，需求属性仍然没有，则需要编辑来创建此属性
				if($resolved === true && scope.value === undefined){
					scope.isEditing = true;
				}
			});
			
			scope.edit = function(){
				scope.isEditing = true;
				scope.oldValue = scope.value;
				setTimeout(function(){//解决click事件触发之后不能自动focus
					element.find('input').trigger('focus');
				});
			}
			
			scope.editCompleted = function(){
				
				//失焦时值仍然为undefined，说明未曾编辑，则不保存
				//name和type由于不推荐为空值，因此为空也视为未曾编辑
				if(scope.value === undefined || ((scope.prop === 'name' || scope.prop === 'type') && scope.value === '')){
					return;
				}
				
				scope.isEditing = false;
				
				//首次添加时，失焦为首次保存的时间点
				if(scope.inAddMode && scope.object.name && scope.object.type){
					
					Object.save(scope.object, function(value){
						//保存后跳转到对象编辑页
						$location.replace().url('detail/' + value.id);
					});
				}
				
			}
			
			scope.editCanceled = function(){
				scope.isEditing = false;
				// scope.value = scope.oldValue;
				// scope.save();
			}
			
			scope.save = function(){
				
				//首次添加对象时，不在每次变化时保存，而是在首次失焦时保存
				if(scope.inAddMode && (!scope.object.name || !scope.object.type)){
					return;
				}
				
				switch(scope.prop){
					
					case 'meta':
						Object.updateMeta({object: scope.object.id, key: scope.key}, scope.value);
						break;

					case 'status':
						Object.updateStatus({object: scope.object.id, name: scope.key}, scope.value);
						break;

					case 'relative':
						Object.saveRelative({object: scope.object.id, relation: scope.key}, scope.value);
						break;

					case 'tag':
						Object.saveTag({object: scope.object.id, taxonomy: scope.key}, scope.value);
						break;

					default:
						scope.object.$update({with_status: {as_rows: true, order_by: 'date desc'}, with_permission: {with_user_info: true}});
				}
			}
			
		}
	}
}]);

lubanlockDirectives.directive('lubanEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.lubanEnter);
                });

                event.preventDefault();
            }
        });
    };
});

lubanlockDirectives.directive('lubanEsc', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 27) {
                scope.$apply(function (){
                    scope.$eval(attrs.lubanEsc);
                });

                event.preventDefault();
            }
        });
    };
});

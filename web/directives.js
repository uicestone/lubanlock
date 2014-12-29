(function(){

'use strict';

/* Directives */

lubanlockDirectives.directive('lubanEditable', ['$location', 'Object', function($location, Object){
	return {
		restrict: 'A',				//此指令通过HTML属性方式调用
		templateUrl: 'partials/editable.html',
		transclude: true,			//支持将HTML标签内部的内容追加到可编辑内容之后
		scope:{
			object: '=',				//正在编辑的对象
			value: '=lubanEditable',	//可编辑字段的值
			name: '@lubanEditable',	//字段的值表达式，用以正则匹配获取属性类型或属性键名
			type: '@',				//input的类型，可选text, radio, select
			options: '=',				//input:radio和select的可用选项
			placeholder: '@',		//input:text的placeholder
			prop:'@',				//meta, relative, parents, status or tag
			key:'=',					//在模版中手动指定的meta.key, relative.relation, tag.taxonomy或status.name
			field:'@',				//field in property: status.comment
			label:'=',				//display label for relative field
			range:'='					//search object argument for relative field
		},
		link: function(scope, element){
			
			//从值表达式中获得属性类型，为.之后[之前的字符串
			if(!scope.prop && scope.name.match(/\.([^.^\[]*)/)){
				scope.prop = scope.name.match(/\.([^.^\[]*)/)[1];
			}
			
			//如果整个object都是undefined，说明没get过，则需要在首次更改时创建对象
			if(scope.object === undefined){
				scope.inAddMode = true;
				scope.object = new Object();
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
				if(!scope.allow('write')){
					return;
				}
				scope.isEditing = true;
				scope.oldValue = scope.value;
				if(scope.prop === 'relative'){
					scope.value = scope.label;
				}
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
					
					scope.object.$save(function(value){
						//保存后跳转到对象编辑页
						$location.replace().url('detail/' + value.id);
					});
				}
				
			}
			
			scope.editCanceled = function(){
				scope.value = scope.oldValue;
				scope.isEditing = false;
			}
			
			scope.save = function(){
				
				//首次添加对象时，不在每次变化时保存，而是在首次失焦时保存
				if(scope.inAddMode && (!scope.object.name || !scope.object.type)){
					return;
				}
				
				if(scope.oldValue === scope.value){
					return;
				}
				
				switch(scope.prop){
					
					case 'meta':
						Object.updateMeta({object: scope.object.id, key: scope.key, prev_value: scope.oldValue}, scope.value, function(metas){
							scope.object.meta = metas;
						});
						break;

					case 'status':
						
						var data = {};
						
						if(!scope.field){
							scope.field = 'date';
						}
						
						data[scope.field] = scope.value;
						
						Object.updateStatus({object: scope.object.id, name: scope.key, order_by: 'date desc'}, data, function(statuses){
							scope.object.status = statuses;
						});
						
						break;

					case 'relative':
						Object.saveRelative({object: scope.object.id, relation: scope.key, replace_id: scope.oldValue}, scope.value, function(relatives){
							scope.object.relative = relatives;
						});
						break;

					case 'parent':
						Object.saveParent({object: scope.object.id, relation: scope.key}, scope.value.id, function(parents){
							scope.object.parents = parents;
						});
						break;

					case 'tag':
						Object.saveTag({object: scope.object.id, taxonomy: scope.key}, scope.value, function(tags){
							scope.object.tag = tags;
						});
						break;

					default:
						var args = {with_status: {as_rows: true, order_by: 'date desc'}, with_permission: {with_user_info: true}};
						if(!scope.inAddMode){
							scope.object.$update(args);
						}
						
				}
			}
			
			/**
			 * check if the current object is allowed for current user on certain action
			 * @param {type} permission
			 * @returns {Boolean}
			 */
			scope.allow = function(action){

				if(!scope.object.permission){
					return false;
				}

				var users = scope.object.permission[action];

				if(users.length === 0){
					if(scope.object.permission.read.length === 0 && 
						scope.object.permission.write.length === 0 && 
						scope.object.permission.grant.length === 0 && 
						(
							user.id === scope.object.user || 
							(scope.object.user === null && scope.object.id === user.id)
						)
					){
						return true;
					}
					return false;
				}
				for(var i = 0; i < users.length; i++){
					if(users[i].id === user.id){
						return true
					}
					for(var j = 0; j < groups.length; j++){
						if(users[i].id === groups[j].id){
							return true;
						}
					}
				}
				return false;
			}
			
			scope.search = function(keyword){
				return Object.query(angular.extend({search: keyword}, scope.range)).$promise;
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

})();

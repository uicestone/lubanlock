'use strict';

/* Directives */
var lubanlockDirectives = angular.module('lubanlockDirectives', []);

lubanlockDirectives.directive('lubanDropzone', ['Object', function(Object){
	return {
		scope: {
			files: '='
		},
		link: function(scope, element){
			
			scope.$watch('files.$resolved', function($resolved){
				
				$resolved && element.dropzone({
					paramName: "file", // The name that will be used to transfer the file
					maxFilesize: 10, // MB
					url: 'file/upload',
					addRemoveLinks: true,
					dictDefaultMessage:
					'<span class="bigger-150 bolder"><i class="icon-caret-right red"></i> 上传你的简历</span>  拖放到这里\
					<span class="smaller-80 grey">（或点击选择文件）</span> <br /> \
					<i class="upload-icon icon-cloud-upload blue icon-3x"></i>',
					dictResponseError: '上传文件错误',
					dictRemoveFile: '删除',
					init: function(){
						var _this = this;
						
						angular.forEach(scope.files, function(file){
							_this.emit('addedfile', {id: file.id, name: file.name, size: file.meta['file_size'][0] * 1024});
//							this.options.thumbnail.call(this, mockFile, "http://someserver.com/myimage.jpg");
						});
					},
					removedfile: function(file){
						Object.remove({id: file.id}, function(){
							angular.element(file.previewElement).remove();
						});
						
					},
					success: function(file, serverFile){
						file.id = serverFile.id;
						return file.previewElement.classList.add("dz-success");
					}
				});
					
			});
			
			
		}
	}
}]);

lubanlockDirectives.directive('lubanEditable', ['Object', 'ObjectMeta', '$location', function(Object, ObjectMeta, $location){
	return {
		restrict: 'A',
		templateUrl: 'partials/editable.html',
		transclude: true,
		scope:{
			object: '=',
			value: '=lubanEditable',
			options: '=',
			placeholder: '@',
			key:'=',//在模版中手动指定的meta key
			type: '@',
			name: '@lubanEditable'
		},
		link: function(scope, element, attr){
			
			//从值表达式中获得属性名，为.之后[之前的字符串
			scope.prop = attr.lubanEditable.match(/\.([^.^\[]*)/)[1];
			
			//如果整个object都是undefined，说明没get过，则需要在首次更改时创建对象
			scope.inAddMode = scope.object === undefined;
			scope.isEditing = scope.inAddMode;
			
			//监控对象资源的请求状态
			scope.$watch('object.$resolved', function($resolved){
				//资源
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
				scope.isEditing = false;
			}
			
			scope.editCanceled = function(){
				scope.isEditing = false;
				scope.value = scope.oldValue;
				scope.save();
			}
			
			scope.save = function(){
				
				var data = {};
				
				switch(scope.prop){
					case 'meta':
						var key = scope.key === undefined ? attr.lubanEditable.match(/\['(.*?)'\]/)[1] : scope.key;
						ObjectMeta.update({object: scope.object.id, key: key}, scope.value);
						break;

					case 'status':
						//TODO
						break;

					case 'relative':
						//TODO
						break;

					case 'tag':
						//TODO
						break;

					default:
						data[scope.prop] = scope.value;
						
						if(scope.object === undefined){//TODO 新建对象时，首次保存信息即跳转到编辑，不完美
							Object.save(data, function(value){
								$location.url('detail/' + value.id);
							});
						}
						else{
							scope.object[scope.prop] = scope.value;
							scope.object.$update();
						}
						
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

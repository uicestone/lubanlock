'use strict';

/* Directives */
var lubanlockDirectives = angular.module('lubanlockDirectives', []);

lubanlockDirectives.directive('luDropzone', function(){
	return {
		compile: function(tElement, tAttrs, transclude){
			tElement.dropzone({
				paramName: "file", // The name that will be used to transfer the file
				maxFilesize: 10, // MB
				url: 'file/upload',
				addRemoveLinks : true,
				dictDefaultMessage :
				'<span class="bigger-150 bolder"><i class="icon-caret-right red"></i> 上传你的简历</span>  拖放到这里\
				<span class="smaller-80 grey">（或点击选择文件）</span> <br /> \
				<i class="upload-icon icon-cloud-upload blue icon-3x"></i>',
				dictResponseError: 'Error while uploading file!',

				//change the previewTemplate to use Bootstrap progress bars
				previewTemplate: "<div class=\"dz-preview dz-file-preview\">\n  <div class=\"dz-details\">\n    <div class=\"dz-filename\"><span data-dz-name></span></div>\n    <div class=\"dz-size\" data-dz-size></div>\n    <img data-dz-thumbnail />\n  </div>\n  <div class=\"progress progress-small progress-striped active\"><div class=\"progress-bar progress-bar-success\" data-dz-uploadprogress></div></div>\n  <div class=\"dz-success-mark\"><span></span></div>\n  <div class=\"dz-error-mark\"><span></span></div>\n  <div class=\"dz-error-message\"><span data-dz-errormessage></span></div>\n</div>"
			});
		}
	}
});

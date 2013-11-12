var app = angular.module('sys', ['ngResource','ui.bootstrap.modal','ui.router','ui.select2']);

app.config(['$stateProvider', function ($stateProvider) {
    $stateProvider.state("home",{
      url: '',
      templateUrl:"listView.html",
      controller:"List"
    }).state("list",{
      templateUrl:"listView.html",
      controller:"List",
      url:"/list?search&name&type&tag&related&status&info&limit"
    }).state("detail",{
      templateUrl:"detailView.html",
      controller:"Detail",
      url:"/detail/:tpl/:id"
    }).state("add",{
      templateUrl:"detailView.html",
      controller:"Detail",
      url:"/add-detail"
    });
}]).run(['$state', function ($state) {
}]);


/**
 * sys-grid
 * 元数据表格
 * 支持增删改
 */
app.directive('sysGrid',function($timeout,$resource){

  var Widgets = {
    "text":angular.noop,
    "select2":function(scope){
      // 这里的scope其实是整个表格的scope，不是某个单元格的scope
      // 所以供选数据并没有区分，目前这是ok的
      // 如果需要做区分，就需要在select2.html，对数据进行关联，再在相应的controller中予以实现
      scope.selections = [];
      var Names = $resource("/meta/name");

      Names.query(function(resources){
        scope.selections = resources.map(function(res){return res[0]});
      });
    }
  }

  return {
    templateUrl: 'template/sysGrid/table.html',
    restrict: 'A',
    scope:{
      onFinishEdit:"&"
    },
    replace:false,
    link: function(scope, elem, attrs){

      var parent_data = scope.$parent.data;
      var mode = scope.$parent.mode;
      var attr_name = attrs.gridAttr;
      var data = parent_data[attr_name];

      var render = scope.render;

      var Info;

      /* 从attr设置初始值（将来考虑在单个属性上写json） */
      scope.title = attrs.gridTitle;
      scope.fields = attrs.gridFields.split(",");
      scope.titles = attrs.gridTitles.split(",");
      scope.widgets = [];
      var widgetsAttrList = (attrs.gridWidget||"").split(",");
      
      /* 用于在模板中将某一列设置成对应的widget */
      for(var i=0; i < scope.fields.length; i++ ){
        scope.widgets[i] = widgetsAttrList[i] || "text";
        /* 执行相应的初始化方法 */
        Widgets[scope.widgets[i]](scope);
      }



      /* 更新scope值 */
      var updateScopeValues = function(){
        scope.data = scope.$parent.data[attr_name];
        attr_name = attrs.gridAttr;
        Info = $resource("/object/" + scope.$parent.data.id + "/" + attr_name);
      }

      updateScopeValues();

      /* 数据真正进入后端之前，前端模板需要有空行 */
      var makeEmptyRow = function(){
        var obj = {};
        scope.fields.forEach(function(key){
          obj[key] = "";
        });
        return obj;
      };

      /* 表格可设为不能编辑（考虑某列不允许编辑的情况） */
      if(attrs.editable !== undefined){
        scope.editcell = function(e){
          var elem = angular.element(e.target);
          var elem_scope = elem.scope();
          elem_scope.editing = true;
          $timeout(function(){
            var input = elem.parent().find('input')[0];
            input && input.focus();
          },100);
        }
      }else{
        scope.editcell = function(){}
      }

      /* 单元格完成编辑 */
      scope.exitEdit = function(e,row,index){
        updateScopeValues();
        var elem = angular.element(e.target);
        var elem_scope = elem.scope();
        var params = {};

        elem_scope.editing = false;
        if(scope.$parent.mode == "edit"){
          if(scope.data[index].id){
            params={id:scope.data[index].id};
          }
          scope.data[index] = Info.save(params,row,function(resource){
            console.log(scope.data[index],resource);
          });
        }
        scope.onFinishEdit({name:attr_name,value:row,grid:scope.data});
      }

      /* 删除行 */
      scope.removerow = function(row){
        updateScopeValues();
        if(scope.$parent.mode == "edit"){
          Info.remove({id:row.id},function(){
            scope.data.splice(scope.data.indexOf(row),1);
          });
        }else{
          scope.data.splice(scope.data.indexOf(row),1);
        }
      }

      /* 新增行 */
      scope.addrow = function(){
        updateScopeValues();
        var newrow;
        scope.data.push(makeEmptyRow());
      }
    },
    controller: function($scope, $element, $attrs, $transclude, Obj) {
      // $scope.data = Obj.get
    },
  }
});


app.directive('sysEditable',function($timeout,Obj){
  return {
    templateUrl: 'template/sysEditable/field.html',
    restrict: 'A',
    replace:false,
    scope:{
      fieldName:"@",
      onFinishEdit:"&"
    },
    link: function(scope, elem, attrs){
      var input = elem.parent().find("input");
      var viewRootScope = scope.$parent.$parent;
      scope.fieldValue = viewRootScope.data[scope.fieldName];
      scope.editing = !scope.fieldValue;
      scope.edit = function(){
        scope.editing = true;
        $timeout(function(){
          input[0].focus();
        },0);
      };

      scope.exitEdit = function(){
        var data = {};
        if(scope.fieldValue){
          scope.editing = false;
          if(scope.$parent.mode == "edit"){
            data[scope.fieldName] = scope.fieldValue;
            Obj.save({
              id:viewRootScope.data.id
            },data);
          }
          scope.onFinishEdit({name:scope.fieldName,value:scope.fieldValue});
        }
      };
    }
  }
});

/**
 * 支持键盘触发
 * @return {[type]} [description]
 */
app.directive('ngEnter', function() {
  return function( scope, elem, attrs ) {
    elem.bind('keyup', function(e) {
        if(e.keyCode==13){
            scope.$apply(attrs.ngEnter);
        }
    });
  };
});

/**
 * via https://gist.github.com/eliotsykes/5394631
 * @return {[type]} [description]
 */
app.directive('ngBlur', ['$parse', function($parse) {
  return function( scope, elem, attrs ) {
    var fn = $parse(attrs.ngBlur);
    if(elem.is("input,text")){
      elem.bind('blur', function(event) {
        scope.$apply(function() {
          fn(scope, {$event:event});
        });
      }); 
    }else{
      // let the div has the ability to blur
      var focused = false;
      elem.on("click",function(e){
        e.stopPropagation();
        focused = true;
      });
      $("body").click(function(e) {
        if(!$.contains(elem,e.target) && focused){
          focused = false;
          scope.$apply(function() {
            fn(scope, {$event:event});
          });
        }
      });
    }
  };
}]);

/**
 * 对象资源
 * @param  {[type]} $resource [description]
 * @return {[type]}           [description]
 */
app.factory('Obj', ['$resource', function($resource) {

  return $resource('/object/:id');

}]);

/**
 * 侧边栏数据，支持localStorage及后端同步
 * @param  {[type]} $resource [description]
 * @return {[type]}           [description]
 */
app.factory('NavData',['$resource',function($resource){
  var key = "sys_nav_list";
  var nav_list = localStorage.getItem(key);
  var resource = $resource('/nav');

  try{
    nav_list = JSON.parse(nav_list);
    if(!nav_list){nav_list=[];}
  }catch(e){
    nav_list = [];
  }

  return {
    add: function(new_obj){
      if(!nav_list.filter(function(item){
        return item.info.pageName == new_obj.info.pageName
      }).length){
        nav_list.push(new_obj);
      }
      localStorage.setItem(key,JSON.stringify(nav_list));
    },
    get: function(){
      return nav_list;
    }
  };
}]);


/**
 * 搜索服务，提供搜索方法以调用
 * @param  {[type]} Obj        [description]
 * @param  {[type]} $rootScope [description]
 * @return {[type]}            [description]
 */
app.factory("SearchService",["Obj","$rootScope","$state","$location","$stateParams","$timeout",function(Obj,$rootScope,$state,$location,$stateParams,$timeout){
  return {
    searching : false,
    filtered : false,
    from_side_bar : false,
    currentTemplate:"default",
    items: [],
    total: 0,
    search:function(arg,options){
      var self = this;
      var useful_params = {};

      options = options || {};

      this.searching = true;
      

      for(var key in $stateParams){
        if($stateParams[key]){
          useful_params[key] = $stateParams[key];
        }
      }

      Obj.get(useful_params,function(resource){
        // 判断若为过滤结果，则显示保存按钮
        for(var key in self.search_param){
          if($stateParams[key] !== "" && key!=="limit"){
            self.filtered = true;
            break;
          }
        }

        self.searching = false;
        self.items = resource.data;
        self.total = resource.total;
      });
    }
  };
}]);

app.controller('RootCtrl',function($scope,SearchService){
  $scope.resetTemplate = function(){
    SearchService.currentTemplate = "default";
  }
});

/**
 * 侧边栏
 * @param  {[type]} $scope        [description]
 * @param  {[type]} $state        [description]
 * @param  {[type]} NavData       [description]
 * @param  {[type]} SearchService [description]
 * @return {[type]}               [description]
 */
app.controller('Sidebar',function($scope,$state,NavData,SearchService){
  $scope.items = NavData.get();
  $scope.show = false;

  $scope.search = function(params,info){
    SearchService.from_side_bar = true;
    SearchService.currentTemplate = info.pageTemplate;
    $state.go("list",params);
  };
});

app.controller('Search',function($scope,$state,SearchService){
  $scope.searchService = SearchService;
  $scope.advanced_search_open = false;
  $scope.key = "";

  $scope.toggleAdvancedSearch = function(){
    $scope.advanced_search_open = !$scope.advanced_search_open;
    // $scope.$apply();
  };


  $scope.simpleSearch = function(){
    SearchService.currentTemplate = "default";
    var args = {};
    Object.keys($state.params).forEach(function(name){args[name] = ""});
    args.search = $scope.key;
    $state.go("list",args);
  }

  $scope.advancedSearch = function(){
    SearchService.from_side_bar = false;
    $scope.advanced_search_open = false;
    $state.go("list",SearchService.search_param);
  };

});


/**
 * 数据列表
 * @param  {[type]} $scope        [description]
 * @param  {[type]} $modal        [description]
 * @param  {[type]} $log          [description]
 * @param  {[type]} $location     [description]
 * @param  {[type]} NavData       [description]
 * @param  {[type]} SearchService [description]
 * @return {[type]}               [description]
 */
app.controller('List', function($scope,$state,$modal,$log,$location,$stateParams,NavData,SearchService){
  $scope.show = true;

  $scope.searching = false;

  $scope.start = 1;
  $scope.per = 25;
  $scope.searchService = SearchService
  $scope.page = 0;

  $scope.pageTemplates = ["a","b","c"];

  $scope.nextPage = function(){
    if($scope.page == parseInt($scope.searchService.total / $scope.per,10)){return;}
    $scope.page += 1;
    $state.go("list",{
      "limit":[$scope.per,$scope.page*$scope.per]
    });
  };

  $scope.prevPage = function(){
    if($scope.page===0){return;}
    $scope.page -= 1;
    $state.go("list",{
      "limit":[$scope.per,$scope.page*$scope.per]
    });
  };

  $scope.addNew = function(){
    $state.go("add");
  };

  $scope.saveSearchResults = function(){
    var params = {};
    angular.extend(params,$scope.searchService.search_param);
    delete params.limit;

    $scope.saveTemplate(params);
  };

  $scope.showDetail = function(item){
    var template = SearchService.currentTemplate;
    $state.go("detail",{tpl:template,id:item.id});
  }

  $scope.saveTemplate = function(params){
    var modalInstance = $modal.open({
      templateUrl: 'template-save.html',
      controller: saveTemplateInstanceController,
      resolve: {
        pageTemplates: function () {
          return $scope.pageTemplates;
        }
      }
    });
    modalInstance.result.then(function (info) {
      var pageName = info.pageName;
      var pageTemplate = info.pageTemplate;

      NavData.add({
        info:info,
        params:params
      });

      // $scope.selected = selectedItem;
    }, function () {
      $log.info('Modal dismissed at: ' + new Date());
    });
  };

  var saveTemplateInstanceController = function ($scope, $modalInstance, pageTemplates) {
    $scope.pageTemplates = pageTemplates;
    $scope.pageTemplate = pageTemplates[0];

    $scope.ok = function (pageTemplate,pageName) {
      $modalInstance.close({
        pageTemplate:pageTemplate,
        pageName:pageName
      });
    };

    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
  };

  $state.go("list");
  SearchService.search($stateParams);
});

app.controller('Detail',function($scope,$state,$stateParams,Obj){

  var current_state_name = $state.current.name;
  $scope.mode = current_state_name = current_state_name == "add" ? "add" : "edit";


  if(current_state_name == "add"){
    $scope.data = {
      name:"",
      type:"",
      meta:[],
      status:[],
      relative:[]
    }
  }else{
    $scope.data = Obj.get({
      id:$stateParams.id
    });
  }

  $scope.editDone = function(name,value,grid){
    var data = $scope.data;

    if($scope.mode === "edit"){
      return;
    }

    // $scope.mode == "add"
    if(typeof value == "string"){
      data[name] = value;
    }else{
      data[name] = grid;
    }

    if($scope.fetching){return;}
    $scope.fetching = true;
    $scope.data = Obj.save(data,function(){
      $scope.fetching = false;
      $scope.mode = "edit";
    });
  }

  $scope.template = ["template/detailView",$stateParams.tpl||"default","html"].join(".");
});


app.controller('StatusCtrl',function($scope){
  $scope.render = {
    color:function(val){
      return '<span style="color:'+val+'">'+val+'</span>'
    }
  }
});
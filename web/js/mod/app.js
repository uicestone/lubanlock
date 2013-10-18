var app = angular.module('sys', ['ngResource','ui.bootstrap.modal','ui.router']);

app.config(['$stateProvider', function ($stateProvider) {
    $stateProvider.state("home",{
      url: '',
      templateUrl:"listView.html",
      controller:"List"
    }).state("list",{
      templateUrl:"listView.html",
      controller:"List",
      url:"/list?name&type&tag&related&status&info&limit"
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

  return {
    templateUrl: 'template/sysGrid/table.html',
    restrict: 'A',
    scope:true,
    replace:false,
    link: function($scope, $elem, $attrs){
      var parent_data = $scope.$parent.data;
      var mode = $scope.$parent.mode;
      var data = parent_data[$attrs.gridAttr];
      var render = $scope.render;


      $scope.title = $attrs.gridTitle;
      $scope.fields = $attrs.gridFields.split(",");
      $scope.tdwidth = (100/($scope.fields.length-1)) + "%";

      var Info = $resource("/object/" + parent_data.id + "/" + $attrs.gridAttr);

      var makeEmptyRow = function(){
        var obj = {};
        $scope.fields.forEach(function(key){
          obj[key] = "";
        });
        return obj;
      };

      if($attrs.editable !== undefined){
        $scope.editcell = function(e){
          var elem = angular.element(e.srcElement);
          var scope = elem.scope();
          scope.editing = true;
          $timeout(function(){
            var input = elem.parent().find('input')[0];
            input && input.focus();
          },100);
        }
      }else{
        $scope.editcell = function(){}
      }

      $scope.exitedit = function(e,row){
        var elem = angular.element(e.srcElement);
        var scope = elem.scope();
        scope.editing = false;
        if(mode == "edit"){
          Info.save({id:row.id},row);
        }
      }

      $scope.removerow = function(row){
        if(mode == "edit"){
          Info.remove({id:row.id},function(){
            data.splice(data.indexOf(row),1);
          });
        }else{
          data.splice(data.indexOf(row),1);
        }
      }

      $scope.addrow = function(){
        var newrow;
        if(mode == "edit"){
          newrow = Info.save(function(){
            data.push(newrow);
          });
        }else{
          data.push(makeEmptyRow());
        }
      }
      $scope.data = data;
    },
    controller: function($scope, $element, $attrs, $transclude, Obj) {
      // $scope.data = Obj.get
    },
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
    elem.bind('blur', function(event) {
      scope.$apply(function() {
        fn(scope, {$event:event});
      });
    });
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
  $scope.advantage_search_open = false;
  $scope.key = "";

  $scope.toggleAdvantageSearch = function(){
    $scope.advantage_search_open = !$scope.advantage_search_open;
    // $scope.$apply();
  };


  $scope.simpleSearch = function(){
    SearchService.currentTemplate = "default";
    var args = {};
    Object.keys($state.params).forEach(function(name){args[name] = ""});
    args.name = $scope.key;
    $state.go("list",args);
  }

  $scope.advantageSearch = function(){
    SearchService.from_side_bar = false;
    $scope.advantage_search_open = false;
    $state.go("list");
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
  // $scope.data = Obj.get({
  //   id:$stateParams.id
  // });

  // ["meta","tag","relative"].forEach(function(key){
  //   if(!$scope.data[key]){
  //     $scope.data[key] = [];
  //   }
  // });
  var mode = $state.current.name;
  $scope.mode = mode == "add" ? "add" : "edit";


  if(mode == "add"){
    $scope.data = {
      name:"",
      type:"",
      meta:[],
      status:[],
      relative:[]
    }
  }else{
    $scope.data = {
      name:"沈苹",
      type:"客户",
      id:$stateParams.id,
      meta:[{
        id:1,
        name:"a",
        content:"b"
      },{
        id:2,
        name:"b",
        content:"c"
      }],

      status:[{
        "id":0,
        "name":"czxc",
        "type":"we",//标签类型，分类的分类，如“阶段”，“领域”,
        "color":"#390"
      },{
        "id":1,
        "name":"qwe",
        "type":"qwe",//标签类型，分类的分类，如“阶段”，“领域”,
        "color":"#990"
      }],

      relative:[{
        "id":0,//关系id
        "name":"学号",//关连对象显示名称
        "num":"123",//关系编号，比如一个学生在一个班级中的学号
        "relation":"",
        "till":"1970-01-01",//关系结束时间
        "relative":0,//关联对象id
        "type":""//关连对象类型
      }]
    }
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
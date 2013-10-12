var app = angular.module('sys', ['ngResource','ui.bootstrap.modal','ui.router']);

app.config(['$stateProvider', function ($stateProvider) {
    $stateProvider.state("home",{
      url: '',
      templateUrl:"listView.html",
      controller:"List"
    }).state("list",{
      templateUrl:"listView.html",
      controller:"List",
      url:"/list"
    }).state("detail",{
      templateUrl:"detailView.html",
      controller:"Detail",
      url:"/detail/:tpl/:id"
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
      var data = parent_data[$attrs.gridAttr];
      var render = $scope.render;
      $scope.title = $attrs.gridTitle;
      $scope.fields = $attrs.gridFields.split(",");
      $scope.tdwidth = (100/(Object.keys(data[0]).length-1)) + "%";

      var Info = $resource("/object/" + parent_data.id + "/" + $attrs.gridAttr);

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
        Info.save({id:row.id},row);
        $scope.$broadcast("wuha",{data:row});
      }

      $scope.removerow = function(row){
        Info.remove({id:row.id},function(){
          data.splice(data.indexOf(row),1);
        });
      }

      $scope.addrow = function(){
        var newrow = Info.save(function(){
          data.push(newrow);
          console.log(newrow);
        });
      }

      // console.log($attrs.editable);
      // data.forEach(function(row){
      //   for(var key in row){
      //     if(render && render[key]){
      //       console.log(key,render[key](row[key]));
      //       row[key] = ;
      //     }
      //   }
      // });
      $scope.data = data;
    },
    controller: function($scope, $element, $attrs, $transclude, Obj) {
      // $scope.data = Obj.get
    },
  }
})


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
app.factory("SearchService",["Obj","$rootScope","$state","$location",function(Obj,$rootScope,$state,$location){
  return {
    searching : false,
    filtered : false,
    from_side_bar : false,
    search_param: {
      type:"",
      name:"",
      tag:"",
      related:"",
      status:"",
      info:""
    },
    currentTemplate:"default",
    items: [],
    total: 0,
    clear:function(key){
      if(key=="all"){
        for(key in this.search_param){
          this.clear(key);
        }
      }else{
        this.search_param[key] = "";
      }
    },
    search:function(arg){
      var self = this;
      var useful_params = {};
      this.searching = true;

      angular.extend(this.search_param,$location.search());
      angular.extend(this.search_param,arg);
        
      for(var key in this.search_param){
        if(this.search_param[key]){
          useful_params[key] = this.search_param[key];
        }
      }

      Obj.get(useful_params,function(resource){
        // 判断若为过滤结果，则显示保存按钮
        for(var key in self.search_param){
          if(self.search_param[key] !== "" && key!=="limit"){
            self.filtered = true;
            break;
          }
        }

        self.searching = false;
        self.items = resource.data;
        self.total = resource.total;
      });

      $state.go("list");
      $location.search(useful_params);
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
    SearchService.search(params);
  };
});

app.controller('Search',function($scope,SearchService){
  $scope.searchService = SearchService;
  $scope.advantage_search_open = false;
  $scope.key = "";

  $scope.toggleAdvantageSearch = function(){
    $scope.advantage_search_open = !$scope.advantage_search_open;
    // $scope.$apply();
  };


  $scope.simpleSearch = function(){
    SearchService.clear("all");
    SearchService.currentTemplate = "default";
    SearchService.search({
      name:$scope.key
    });
  }

  $scope.advantageSearch = function(){
    SearchService.from_side_bar = false;
    $scope.advantage_search_open = false;
    SearchService.search();
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
app.controller('List', function($scope,$state,$modal,$log,$location,NavData,SearchService){
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
    SearchService.search({
      "limit":[$scope.per,$scope.page*$scope.per]
    });
  };

  $scope.prevPage = function(){
    if($scope.page===0){return;}
    $scope.page -= 1;
    SearchService.search({
      "limit":[$scope.per,$scope.page*$scope.per]
    });
  };

  $scope.addRow = function(){
    console.log("oh yeah");
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

  SearchService.search();
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

  $scope.template = ["template/detailView",$stateParams.tpl,"html"].join(".");
});


app.controller('StatusCtrl',function($scope){
  $scope.$on("wuha",function(){
    console.log('ohyeah');
  });
  $scope.render = {
    color:function(val){
      return '<span style="color:'+val+'">'+val+'</span>'
    }
  }
});
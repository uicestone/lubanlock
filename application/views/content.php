<div class="main-content">
    <div class="page-content" ui-view>
        <!-- Search List -->
        <script type="text/ng-template" id="listView.html">
            <div class="row-fluid gridStyle">
                <div class="list-meta">
                    <button class="btn btn-primary btn-small icon-plus" ng-click="addNew()">添加记录</button>
                    <button class="btn btn-small icon-plus" ng-click="saveSearchResults()" ng-show="searchService.filtered && !searchService.from_side_bar">保存搜索结果</button>

                    <div class="paging">
        
                        <span class="count"><span class="num">{{page*per+start}}</span> 到 <span class="num">{{(page+1)*per < searchService.total ? (page+1)*per : searchService.total}}</span> 条记录， 共 <span class="num">{{searchService.total}}</span> 条</span>

                        <button class="btn btn-light btn-small icon-angle-left" ng-click="prevPage()"></button>
                        <button class="btn btn-light btn-small icon-angle-right" ng-click="nextPage()"></button>
                    </div>
                </div>
                <table class="table table-striped table-bordered table-hover" >
                    <thead>
                    <tr>
                        <td>名称</td>
                        <td>类型</td>
                        <td>标签</td>
                        <td>状态</td>
                    </tr>
                    </thead>
                    <tbody>
                    <tr ng-class-odd="'odd'" ng-class-even="'even'" ng-repeat="(index,item) in searchService.items" ng-click="showDetail(item)">
                        <td>{{item.name}}</td>
                        <td>{{item.type}}</td>
                        <td>{{item.tag}}</td>
                        <td>{{item.status}}</td>
                    </tr>
                    </tbody>
                </table>
            </div><!--/.row-fluid-->
        </script>

        <script type="text/ng-template" id="detailView.html">
            <div ng-include="template"></div>
        </script>
        <script type="text/ng-template" id="template/detailView.default.html">
                <h3>名字：<span sys-editable field-name="name" on-finish-edit="editDone(name,value)"></span></h3>
                <h3>类型：<span sys-editable field-name="type" on-finish-edit="editDone(name,value)"></span></h3>
                
                <div sys-grid editable grid-attr="meta"  grid-title="关联数据" grid-fields="name,content" grid-titles="名称,内容"
                    on-finish-edit="editDone(name,value,grid)"></div>

                <div sys-grid editable grid-attr="status" grid-title="状态" grid-fields="name,type,datetime,content,comment" grid-titles="名称,类型,日期,内容,备注"
                    on-finish-edit="editDone(name,value,grid)"
                    ng-controller="StatusCtrl"></div>

                <div sys-grid editable grid-attr="relative" grid-title="关系" grid-fields="name,num,relation,type" grid-titles="名称,编号,关系,类型"
                    on-finish-edit="editDone(name,value,grid)"></div>
        </script>
        <!-- Detail -->
    </div><!--/.page-content-->
</div><!--/.main-content-->

<div>
    <script type="text/ng-template" id="template-save.html">
        <div class="modal-header">
            <h3>取个名字</h3>
        </div>
        <div class="modal-body">
            <input type="text" ng-enter="ok(pageTemplate,pageName)" ng-model="pageName" />   
            <select ng-model="pageTemplate" ng-options="item for item in pageTemplates">
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" ng-click="ok(pageTemplate,pageName)">OK</button>
            <button class="btn btn-warning" ng-click="cancel()">Cancel</button>
        </div>
    </script>
</div>
<div class="navbar">
    <div class="navbar-inner">
        <div class="container-fluid">
            <a href="#" class="brand">
                <small>
                    <i class="icon-leaf"></i>
                    Ace Admin
                </small>
            </a><!--/.brand-->
            
            <div class="form-search input-append" id="global-search" ng-controller="Search">
                <input type="text" class="span5 search-query" ng-enter="simpleSearch()" ng-model="key">
                <div class="icon-angle-down bigger-110" ng-hide="advantage_search_open" ng-click="toggleAdvantageSearch()"></div>
                <button class="btn btn-purple btn-small" ng-click="simpleSearch()">
                    搜索
                    <i class="icon-search icon-on-right bigger-110" ></i>
                </button>
    
                <div id="advantage-search" ng-show="advantage_search_open" ng-cloak>

                    <div class="row-fluid">
                        <label for="form-field-type">类型</label>
                        <div>
                            <input class="input-xlarge" type="text" id="form-field-type" ng-model="searchService.search_param.type" />
                        </div>
                    </div>

                    <div class="row-fluid">
                        <label for="form-field-name">名称</label>
                        <div>
                            <input class="input-xlarge" type="text" id="form-field-name" ng-model="searchService.search_param.name" />
                        </div>
                    </div>

                    <div class="row-fluid">
                        <label for="form-field-tag">标签</label>
                        <div>
                            <input class="input-xlarge" type="text" id="form-field-tag" ng-model="searchService.search_param.tag" />
                        </div>
                    </div>

                    <div class="row-fluid">
                        <label for="form-field-related">关联</label>
                        <div>
                            <input class="input-xlarge" type="text" id="form-field-related" ng-model="searchService.search_param.related" />
                        </div>
                    </div>

                    <div class="row-fluid">
                        <label for="form-field-status">状态</label>
                        <div>
                            <input class="input-xlarge" type="text" id="form-field-status" ng-model="searchService.search_param.status" />
                        </div>
                    </div>

                    <div class="row-fluid">
                        <label for="form-field-info">资料</label>
                        <div>
                            <input class="input-xlarge" type="text" id="form-field-info" ng-model="search_param.info" />
                        </div>
                    </div>
    
                    <div class="row-fluid btn-container">
                        <button class="btn btn-info btn-small" ng-click="advantageSearch()">搜索<i class="icon-search icon-on-right bigger-110"></i></button>
                    </div>

                    <i class="icon-remove" ng-click="toggleAdvantageSearch()"></i>

                </div>
            </div>

            <!-- nav -->
            <ul class="nav ace-nav pull-right">

            <?if($this->user->isLogged()){?>
                <li>
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <i class="icon-envelope"></i>
                        <span class="badge badge-success"><?=$this->message->getNewMessages()?></span>
                    </a>
                </li>
                <li>
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                            
                        <a href="#profile"><?=$this->user->name?></a>
                    </a>
                </li>
				<li>
	                <a href="mailto:uicestone@gmail.com" title="请提出您宝贵的意见">意见反馈</a>
				</li>
                <li>
                    <a href="/logout">退出</a>
                </li>
            <?}else{?>
                <li class="green">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <i class="icon-envelope"></i>
                        <a href="/login">登陆</a>
                        <!-- <span class="badge badge-success">5</span> -->
                    </a>
                </li>
            <?}?>
            </ul>
            <!--/.ace-nav-->
        </div><!--/.container-fluid-->
    </div><!--/.navbar-inner-->
</div>
<div class="main-container container-fluid">
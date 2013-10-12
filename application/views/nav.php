<?php //$this->user->generateNav()?>
<div class="sidebar" id="sidebar" ng-controller="Sidebar">
<ul class="nav nav-list" ng-model="items" ng-cloak>
     
    <li ng-repeat="item in items track by $index" ng-click="search(item.params,item.info)">
        <a href="javascript:;">
            <i class="icon-dashboard"></i>
            <span class="menu-text"> {{item.info.pageName}} </span>
        </a>
    </li>
</ul>
</div>
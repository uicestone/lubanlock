<?php $this->view('header');?>
<a class="menu-toggler" id="menu-toggler" href="">
	<span class="menu-text"></span>
</a>

<div class="sidebar sidebar-fixed" id="sidebar" ng-controller="NavCtrl" ng-class="{'menu-min':config.nav_minimized}">

	<ul class="nav nav-list">

		<li ng-repeat="item in items" ng-class="{active:item.isActive}">
			<a ng-click="navigateTo(item)">
				<i class="icon-trash" ng-if="removable[item.id]" ng-mouseleave="makeRemovable(item, false)" ng-click="remove(item, $event)"></i>
				<i class="icon-{{item.meta.icon[0]}}" ng-hide="removable[item.id]" ng-mouseenter="makeRemovable(item)"></i>
				<span class="menu-text"> {{item.name}} </span>
			</a>
		</li>

		<?php if($this->user->isLogged('admin')){ ?>
		<li ng-class="{active:currentUrl==='/list'}">
			<a ng-click="navigateTo({path:'list'})">
				<i class="icon-cloud"></i>
				<span class="menu-text"> 所有数据 </span>
			</a>
		</li>
		<?php } ?>
		<?php if($this->user->isLogged('user-admin')){ ?>
		<li ng-class="{active:currentUrl==='/user'}">
			<a href="#/user">
				<i class="icon-user"></i>
				<span class="menu-text"> 用户 </span>
			</a>
		</li>
		<?php } ?>

	</ul>

	<div class="sidebar-collapse" id="sidebar-collapse">
		<i data-icon1="icon-double-angle-left" data-icon2="icon-double-angle-right" ng-class="{'icon-double-angle-left': !config.nav_minimized, 'icon-double-angle-right': config.nav_minimized}" ng-click="toggleMinimize()"></i>
	</div>

</div>

<div class="main-content">

	<div class="page-content" ng-view>
	</div>
</div>
<?php $this->view('footer'); ?>

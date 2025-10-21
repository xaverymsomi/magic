<div class="main" name="main">

    <!-- Menu Section  -->
    <div ng-controller="menuController" id="mabrexMenuContainer" ng-init="user_id = <?php echo $_SESSION['id']; ?>;
                                    getUserMenu(user_id)">
        <div id="mabrexLogoPlaceholder" class="hidden-sm hidden-xs bg-info">
            <img src="<?php echo URL; ?>/assets/images/rahisi/official_rahisi_logo_coloured.png" class="img img-responsive">
        </div>
        <div id="mabrexSideNavBar" style="height: 100dvh;" class="hidden-sm hidden-xs scrolled-div">
            <ul class="list-group">
                <li ng-repeat="menu in menus" class="list-group-item">
                    <a ng-click="loadPage(menu.link, menu.title, menu.id)" class="page-link" data-link="{{menu.link}}" title="{{menu.title}}">
                        <i class="pe pe-7s-{{menu.icon}} pe-2x pe-va pe-fw " ng-show="current != menu.id"></i> <span ng-show="current != menu.id"> {{menu.name}} </span>
                        <i class="pe pe-7s-{{menu.icon}} pe-2x pe-va pe-fw " ng-show="current == menu.id"></i> <span ng-show="current == menu.id" class=""> {{menu.name}} </span>
                        <i class="pe pe-7s-angle-right pe-2x pe-va pe-fw pull-right" ng-show="menu.submenus.length > 0 && current != menu.id"></i>
                        <i class="pe pe-7s-angle-down pe-2x pe-va pe-fw pull-right" ng-show="menu.submenus.length > 0 && current == menu.id"></i>
                    </a>
                    <ul class="mabrex-submenu" ng-show="current == menu.id && menu.submenus.length > 0">
                        <li ng-repeat="sub in menu.submenus">

                            <a ng-click="loadPage(sub.link, sub.title, menu.id)" class="page-link" data-link="{{sub.link}}" title="{{sub.title}}">
                                <i class="pe{{sub.icon}} pe-va pe-fw"></i> {{sub.name}}
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
        <!-- small Menu Section -->
        <div id="mabrexLogoPlaceholderMobile" class="hidden-md hidden-lg bg-info" style="padding-left: 7px;">
            <img src="<?php echo URL; ?>/assets/images/rahisi/official_rahisi_minimal_logo_coloured.png">
        </div>
        <div id="mabrexSideNavBarSmall" class="hidden-md hidden-lg">
            <ul class="list-group">
                <li ng-repeat="menu in menus" class="list-group-item">
                    <a ng-click="loadPage(menu.link, menu.title, menu.id)" class="page-link" data-link="{{menu.link}}" title="{{menu.title}}">
                        <i class="pe pe-7s-{{menu.icon}} pe-2x pe-va{{(current == menu.id|| current_link == menu.id) ? ' ' : ''}}"></i>
                    </a>
                    <ul class="mabrex-submenuSmall" ng-show="current == menu.id && menu.submenus.length > 0 && current_task == 'display'">
                        <li ng-repeat="sub in menu.submenus">
                            <a ng-click="loadPage(sub.link, sub.title, menu.id);
                                                                                        setSubMenu(menu.id)" class="page-link" data-link="{{sub.link}}" title="{{sub.title}}">
                                <i class="pe {{sub.icon}} pe-2x pe-va pe-fw"></i> {{sub.name}}
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>


        <!-- Nav Bar Top -->
        <div id="mabrexTopNavBar" class="navbar navbar-default navbar-fixed-top">
            <ul class="nav navbar-nav hidden-sm hidden-xs">
                <li id="mabrexMenuToggler" style="margin: 3px; padding: 6px 2px 6px 20px; cursor: pointer;"><i class="pe pe-7s-menu pull-left pe-2x pe-fw"></i></li>
                <?php
                //                if (!in_array($_SESSION["area"], [3])) {
                //                    echo '<li style="margin: 3px; padding: 10px 2px 6px 6px; cursor: pointer; font-size: 18px;" class="text-uppercase">' . $_SESSION["area_name"] . '</li>';
                //                }
                ?>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li style="padding-top:15px;" class="hidden-xs"><?php echo $_SESSION['username']; ?></li>
                <li class="dropdown pull-right">
                    <a style="margin-right: 10px; padding: 11px 10px 7px 10px;" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="pe pe-7s-user pe-2x pe-fw"></i></a>
                    <ul class="dropdown-menu">
                        <!--                                <li><a class="page-link" href="#"><i class="pe pe-7s-pen pe-fw pe-2x pe-va"></i><?php echo trans('manage_account'); ?></a></li>
                                    <li class="divider"></li>-->
                        <li><a class="page-link" href="<?php echo APP_DIR; ?>/User/password"><i class="pe pe-7s-key pe-rotate-90 pe-fw pe-2x pe-va"></i><?php echo trans('change_password'); ?>
                            </a></li>
 
                        <li class="divider"></li>
                        <li><a class="page-link" href="<?php echo APP_DIR; ?>/Logout"><i class="pe pe-7s-download pe-rotate-270 pe-fw pe-2x pe-va"></i> <?php echo trans('logout'); ?>
                            </a></li>
                    </ul>
                </li>
            </ul>
            <div class="progress" id="progress1" style="visibility:hidden;">
                <div class="loader">
                    <div class="bar"></div>
                </div>
            </div>
        </div>

        <div class="container-fluid" style="padding-left: 35px;">
            <div id="mabrexPageContentHolder" class="row">
                <div id="mabrexPageContent">
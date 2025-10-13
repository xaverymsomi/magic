<?php
/**
 * Description of Nav
 *
 * @author abdirahmanhassan
 */
class Nav {

    function __construct() {
        $this->menu = new Menu();
    }

    function showMenu() {
        Auth::checkLogin();
        echo '<ul class="parent-menu-level collapse" id="main-menu">';
        $this->initMenu();
        echo '</ul>';
    }

    function initMenu() {
        $navs = $this->menu->getMainMenu();
        foreach ($navs as $nav) {
            $subs = $this->menu->getSubMenu($nav['id']);
            $controller = $nav['name'];
            $icon = $nav['icon'];
            $this->generateMenu($subs, $controller, $icon);
        }
    }

    private function generateMenu($subs, $controller, $icon) {
        if (array_filter($subs)) { // has submenus display caret 
            echo '<li><a href="' . $controller . '" class="collapse-toggle"><i class="' . $icon . '"></i> ' . $controller;
            echo '<span class="fa fa-chevron-right"></span></a>';
            echo '<ul class="collapse-box" id="' . $controller . '">';
            foreach ($subs as $menu) {
                echo '<li><a href="' . URL . $controller . '/' . $menu['name'] . '">' . $menu['name'] . ' </a></li>';
            }
            echo '</ul></li>';
        } else {
            echo '<li><a href="' . URL . $controller . '"><i class="' . $icon. '"></i> ' . str_replace("_", " ", $controller) . '</a></li>';
        }
    }

}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(function(){
    //alert (localStorage.getItem('CurrentMenu'));
    if (localStorage.getItem('CurrentMenu') !== null) {
        if (localStorage.getItem('CurrentMenu') === 'mobile') {
            switchDesktopMenuOff();
            localStorage.setItem("CurrentMenu", "mobile");
        } else {
            switchMobileMenuOff();
            localStorage.setItem("CurrentMenu", "desktop");
        }
    } else {
        var width = $(window).width();
        if (width <= 960){
            switchDesktopMenuOff();
            localStorage.setItem("CurrentMenu", "mobile");
        } else {
            switchMobileMenuOff();
            localStorage.setItem("CurrentMenu", "desktop");
        }
    }
    
    // CUSTOM MENU
    
    $('#btnHideMenu').click(function () {
        if ($('.sidebar-nav').hasClass('sidebar-nav-shown')) {
            switchDesktopMenuOff();
            localStorage.setItem("CurrentMenu", "mobile");
        }
        else {
            switchMobileMenuOff();
            localStorage.setItem("CurrentMenu", "desktop");
        }
    });
    
//    $('#btnHideMenuMobile').click(function(){
//        if ($('.sidebar-nav-mobile').hasClass('sidebar-nav-mobile-shown')) {
//            $('.sidebar-nav-mobile').removeClass('sidebar-nav-mobile-shown').addClass('sidebar-nav-mobile-hidden');
//            $('#main').css('margin-left', '5px');
//        }
//        else {
//            $('.sidebar-nav-mobile').removeClass('sidebar-nav-mboile-hidden').addClass('sidebar-nav-mobile-shown');
//            $('#main').css('margin-left', '60px');
//        }
//    });
    
    $('#main-menu > li > a').click(function() {
        var span  = $(this).children('span');
        $('#main-menu > li > a').each(function(){
            var cspan  = $(this).children('span');
            if (!$(this).parent('li').hasClass('active-menu-item')){
                cspan.removeAttr('style').removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        });
        span.attr('style','color: #fff !important').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    });
    
    $('#main-menu-mobile > li > a').click(function() {
        var span = $(this).children('span');
        $('#main-menu-mobile > li > a').each(function(){
            var cspan = $(this).children('span');
            if (!$(this).parent('li').hasClass('active-menu-item')){
                cspan.removeAttr('style').removeClass('fa-chevron-right').addClass('fa-chevron-down');
            }
        });
        span.attr('style','color: #fff !important').removeClass('fa-chevron-right').addClass('fa-chevron-down');
    });
    
    $('.close').not('a#notes-close').click(function() {
        $(this).parent().remove();
    });
    //END OF CUSTOM MENU 
});

function switchDesktopMenuOff(){
    $('.sidebar-nav').removeClass('sidebar-nav-shown').addClass('sidebar-nav-hidden').css('display','none');
    $('#main').css('margin-left', '60px');
    $('.sidebar-nav-mobile').css({'margin-left': '0px', 'display': 'block'}).addClass('sidebar-nav-mobile-shown');
    $('.leftside').css('width', '55px');
}

function switchMobileMenuOff(){
    $('.sidebar-nav').removeClass('sidebar-nav-hidden').addClass('sidebar-nav-shown').css('display','block');
    $('#main').css('margin-left', '215px');
    $('.sidebar-nav-mobile').removeClass('sidebar-nav-mobile-shown').addClass('sidebar-nav-mobile-hidden').css('display','none');
    $('.leftside').css('width', '210px');
}
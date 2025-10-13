<?php
namespace Libs;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class DrawLabel {

    private static $labels = array('label-default', 'label-success', 'label-warning', 'label-info', 'label-danger', 'label-primary', 'label-danger', 'label-info', 'label-success', 'label-warning');

    function __construct() {
        
    }

    public static function getSmallLabel($id, $status) {
        $label = DrawLabel::$labels[$id];
        if (empty($label)) {
            $label = "grey";
        }
        
        return '<span class="label badge  muted" style="padding:4px; '
                . 'margin:5px;font-weight:bold;font-size:10px;background:#e5e5e5;">'
                . ' <span class="label ' . $label . '">'
                . '&nbsp&nbsp</span>' . $status . '</span></span>';
    }
    
      public static function getBigLabel($id, $status) {
        $badge = DrawLabel::$labels[$id];
        if (empty($badge)) {
            $badge = "grey";
        }
        
        return '<span class="label  ' . $badge . '" style="margin:0px;font-size:10px;padding:4px;">'. $status . '</span>';
    }

}

<?php

include('locale/' . $_SESSION['lang'] . '/lang.' . $_SESSION['lang'] . '.php');
$perm = Perm_Auth::getPermissions();
$returned = Session::get('returned') != null || Session::get('returned') != '' ? Session::get('returned') : 0;

echo '<div ng-controller="formController" class="btn-group btn-group-sm " ng-init="' . 'buttons=' . sizeof($this->buttons) . '; return_value=' . $returned . '" ng-show="buttons > 0" '
 . 'ng-model="buttons" style="margin-bottom:10px !important;">';

foreach ($this->buttons as $button) {
    if ($perm->verifyPermission(strtolower($button['action']))) { //check permission
        $action = "'" . $button['action'] . "'";
        echo '<button ng-click="showForm(' . $button['url'] . ', ' . $action . ')" class= "btn btn-' . $button['color']
        . '" data-name="' . $button['name']
        . '" data-action="' . $button['action']
        . '"  data-title= "' . $button['title']
        . '" data-mabrex-dialog="' . $button['url'] . '">';
        if (array_key_exists($button['action'], $lang)) {
            echo $lang[$button['action']] . '</button>';
        } else {
            echo $button['action'] . '</button>';
        }
    }
}

echo '</div>';

$actions = [];
if (sizeof($this->actions)) {//Checks permissions for action buttons
    foreach ($this->actions as $action) {
        if ($perm->verifyPermission(strtolower($action['action']))) {
            $actions[] = $action;
        }
    }
}
/**
 * table display
 */
echo '<div class="panel panel-default" ng-controller="profileController" ng-init="return_value=' . $returned . '">';
echo '<div class="panel-heading"><h4 class="panel-title">';
if (array_key_exists($this->title, $lang)) {
    echo $lang[$this->title] . '</h4></div>';
} else {
    echo $this->title . '</h4></div>';
}

$freq = getFrequency();
$subscription = getSubscribers();

echo '<div class="panel-body">';

echo '<div class="table-responsive"id="data-view">';
echo '<table class="table table-striped table-hover table-condensed" datatable="" >';
echo '<thead><tr><th>' . $lang['Subscribers'] . '</th>';
foreach ($freq as $fr) {

    if (array_key_exists($fr['txt_name'], $lang)) {
        echo '<th>' . $lang[$fr['txt_name']] . '</th>';
    } else {
        echo '<th>' . $fr['txt_name'] . '</th>';
    }
}
echo '<th></th></tr></thead>';
echo '<tbody>';
if (count($subscription) > 0) {
    showSubscriptions($subscription);
} 
echo '</tbody>';
echo '</table>';
echo '</div>';

function getFrequency() {
    $db = new Database();
    $sql = "SELECT * FROM mx_frequency";
    $result = $db->select($sql);
    return $result;
}

function getSubscribers() {
    $db = new Database();
    $sql = "SELECT `mx_user`.`id`, `mx_user`.`txt_name`,`mx_user`.`email`, GROUP_CONCAT(`opt_mx_frequency_id`, '-', `mx_report_type`.`txt_name`) AS type
                                FROM `mx_report_subscriber`
                                JOIN `mx_user` ON `mx_user`.`id` = `mx_report_subscriber`.`txt_mx_user_id`
                                JOIN `mx_report_type` ON `mx_report_type`.`id` = `mx_report_subscriber`.`opt_mx_report_type_id`
                                WHERE `mx_report_subscriber`.`opt_mx_active_id` =1 
                                GROUP BY `txt_mx_user_id`";

    $result = $db->select($sql);
    return $result;
}

function showSubscriptions($subscription) {
    foreach ($subscription as $sub) {
        $daily = array();
        $monthly = array();
        $weekly = array();
        $yearly = array();
        $quaterly = array();
        $tt = explode(',', $sub['type']);

        echo '<tr><td><span class="bold">' . $sub['txt_name'] . '</span><br/><em>' . $sub['email'] . '</em></td>';
        foreach ($tt as $t) {
            if (substr($t, 0, 1) == 1) {//daily
                $daily[] = substr($t, 2);
            } elseif (substr($t, 0, 1) == 2) {//weekly
                $weekly[] = substr($t, 2);
            } elseif (substr($t, 0, 1) == 3) {//monthly
                $monthly[] = substr($t, 2);
            } elseif (substr($t, 0, 1) == 4) { //quarterly
                $quaterly[] = substr($t, 2);
            } elseif (substr($t, 0, 1) == 5) {//yearly
                $yearly[] = substr($t, 2);
            }
        }
        echo '<td>';

        if (count($daily) > 0) {
            echo '<ul>';
            foreach ($daily as $day) {
                echo '<li>' . $day . '</li>';
            }
            echo '</ul>';
        } else {
            echo "-";
        }
        echo '</td>';
        echo '<td>';
        if (count($weekly) > 0) {
            echo '<ul>';
            foreach ($weekly as $week) {
                echo '<li>' . $week . '</li>';
            }
            echo '</ul>';
        } else {
            echo "-";
        }
        echo '</td>';
        echo '<td>';
        if (count($monthly) > 0) {
            echo '<ul>';
            foreach ($monthly as $month) {
                echo '<li>' . $month . '</li>';
            }
            echo '</ul>';
        } else {
            echo "-";
        }
        echo '</td>';
        echo '<td>';
        if (count($quaterly) > 0) {
            echo '<ul>';
            foreach ($quaterly as $qt) {
                echo '<li>' . $qt . '</li>';
            }
            echo '</ul>';
        } else {
            echo "-";
        }
        echo '</td>';
        echo '<td>';
        if (count($yearly) > 0) {
            echo '<ul>';
            foreach ($yearly as $yr) {
                echo '<li>' . $yr . '</li>';
            }
            echo '</ul>';
        } else {
            echo "-";
        }
        echo '</td>';
        echo '<td style="vertical-align: middle !important">';
        echo '<a id="EditSubscriber" href="#" data-mabrex="' . $sub['id'] . '" data-mabrex-class="Subscribers" data-mabrex-user=""><i class="fa fa-edit"></i></a></td>';
        echo '</tr>';
    }
}

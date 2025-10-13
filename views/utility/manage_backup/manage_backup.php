<div id="page-content">
<?php

/**
     * @filesource /utility/manage_backup/manage_backup
     * @author Fatma Azad
     */
    $returned = Session::get('returned') != null || Session::get('returned') != '' ? Session::get('returned') : 0;
    $perm = Perm_Auth::getPermissions();

    echo '<div ng-controller="formController" class="btn-group btn-group-sm" '
    . 'ng-init="buttons=' . sizeof($this->buttons) . '; return_value=' . $returned . '" ng-show="buttons > 0" ng-model="buttons" '
    . 'style="margin-bottom:15px !important;">';
    foreach ($this->buttons as $button) {
        if ($perm->verifyPermission(strtolower($button['action']))) { //check permission
            $action = "'" . $button['action'] . "'";
            echo '<button ng-click="showForm(' . $button['url'] . ', ' . $action . ')" class= "btn btn-' . $button['color']
            . '" data-name="' . $button['name']
            . '" data-action="' . $button['action']
            . '" data-title= "' . $button['title']
            . '" data-mabrex-dialog="' . $button['url'] . '">'
            . $button['name'] . '</button>';
        }
    }

    echo '</div>';

    $actions = [];
    if(sizeof($this->actions)) {//Checks permissions for action buttons
        foreach ($this->actions as $action) {
            if ($perm->verifyPermission(strtolower($action['action']))) {
                $actions[] = $action;
            }
        }
    }
    
    echo '<div class="panel panel-default" ng-controller="profileController" ng-init="return_value=' . $returned . '">';
    echo '<div class="panel-heading"><h4 class="panel-title">' . $this->title . '</h4></div>';
    echo '<div class="panel-body">';
    if ($this->resultData['recordsFiltered'] > 0) {
        // Add mabrex filter
        echo '<mabrex-filter mx-selected="' . $this->postData['length'] . '" mx-location="\'' . $this->postData['location'] . '\'" mx-title="\'Database Backup List\'" mx-current-link="\'' . $this->postData['current'] . '\'" mx-search-term="\'' . $this->postData['search'] . '\'" mx-total-records="' . $this->resultData['recordsTotal'] . '"></mabrex-filter>';

        $view = new DataView();
        echo '<div class="table-responsive" id="data-view">';
        echo '<table class="table table-striped table-hovered table-condensed">';
        echo $view->displayTHead($this->headings, $this->hidden, (sizeof($actions)? HAS_ACTION : NO_ACTION));
        echo $view->displayTBody($this->allRecords, $this->class, $this->table, $this->hidden, $actions, LBL_BIG);
        echo '</table>';
        echo '</div>';
        // Add mabrex pager
        echo '<mabrex-pager mx-filtered="' . $this->resultData['recordsFiltered'] . '" mx-total="' . $this->resultData['recordsTotal'] . '" mx-current-page="' . $this->resultData['currentPage'] . '" mx-pages="' . $this->resultData['totalPages'] . '" mx-page-buttons="10" mx-page-location="\'' . $this->postData['location'] . '\'" mx-page-title="\'Database Backup List\'" mx-page-current-link="\'' . $this->postData['current'] . '\'" mx-page-size="\'' . $this->postData['length'] . '\'" mx-page-search-term="\'' . $this->postData['search'] . '\'" mx-returned="' . $this->resultData['recordsReturned'] . '"></mabrex-pager>';
        echo '</div>';
    } else {
        echo '<div>No Database Backup Available</div>';
    }
    echo '</div>';
?>
</div>
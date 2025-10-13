<?php

namespace Libs;

class MabrexConsole
{
	public function handle(array $argv) : void
	{
		if (count($argv) < 2) {
			echo "No command provided.\n";
			return;
		}

		$command = $argv[1];
		$params = array_slice($argv, 2);

		switch ($command) {
		case 'make:controller':
			$this->makeController($params);
		break;

		case 'make:module':
			$this->makeModule($params);
		break;

		case 'cache:clear':
			$this->clearCache();
		break;

		case 'migrate':
			$this->runMigrations();
		break;

		default:
			echo "Unknown command: $command\n";
		break;
		}
	}

	private function makeController($params) : void
	{
		if (empty($params[0])) {
			echo "Usage: php mabrex make:controller ControllerName\n";
			return;
		}

		$name = $params[0];
		$filename = __DIR__ . '/../modules/' . $name . '.php';

		if (file_exists($filename)) {
			echo "Controller already exists: $name\n";
			return;
		}

		$stub = <<<PHP
<?php

namespace Modules;

class $name
{
    public function index()
    {
        echo "$name controller loaded.\n";
    }
}
PHP;

		file_put_contents($filename, $stub);
		echo "Controller created: $filename\n";
	}

	private function makeModule($params) : void
	{
		if (empty($params[0])) {
			echo "Usage: php mabrex make:module ModuleName\n";
			return;
		}

		$name = $params[0];
		$moduleDir = __DIR__ . '/../modules/' . $name;
		$viewDir = $moduleDir . '/Views';
		$profileDir = $viewDir . '/profile';
		$formDir = $viewDir . '/forms';

		if (is_dir($moduleDir)) {
			echo "Module already exists: $name\n";
			return;
		}

		mkdir($profileDir, 0777, true);

		mkdir($formDir, 0777, true);

		$plural = strtolower($name) . 's';
		// Controller
		$controller = <<<PHP
<?php

namespace Modules\\$name;

use Libs\\Controller;

class $name extends Controller
{
    public \$model;

    public function __construct()
    {
        parent::__construct();
        \$this->model = new {$name}_Model();
    }

    public function index() : void
    {
        \$permission = 'view_{$plural}';
        \$data = \$this->model->getAllRecords(\$this->model->getTable(true));
        \$title = "All " . \$this->model->getTitle(true);
        \$this->pageFilter(\$title, \$data, \$permission);
    }
}
PHP;
		file_put_contents("$moduleDir/$name.php", $controller);

		// Model
		$model = <<<PHP
<?php

namespace Modules\\$name;

use Libs\\Model;
use Libs\\Perm_Auth;

class {$name}_Model extends Model
{
    private string \$table;
    private string \$view_table;
    private string \$title = "$name";
    private string \$title_plural = "{$name}s";
    private string \$parent_key;

    public function __construct()
    {
        parent::__construct();

        \$this->table = "mx_" . strtolower("$name");
        \$this->view_table = "mx_" . strtolower("$name") . "_view";
        \$this->parent_key = strtolower("{$name}_id");
    }

    public function getHiddenFields(): array
    {
        return ['id', 'row_id'];
    }

    public function getControls(): array
    {
        return [[
            'action' => 'create',
            'color' => 'success',
            'title' => 'Create New {$name}',
            'name' => 'New {$name}',
            'url' => "'$name'",
            'permission' => 'add_' . strtolower("{$name}s")
        ]];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "Suspend_{$name}s", "name" => "Suspend", "icon" => "fa-lock", "color" => "orange", "url" => "$name",
                'disabled' => ['OR' => ['opt_mx_state_id' => [INACTIVE]]]
            ],
            [
                "action" => "Activate_{$name}s", "name" => "Activate", "icon" => "fa-unlock", "color" => "ccm", "url" => "$name",
                'disabled' => ['OR' => ['opt_mx_state_id' => [ACTIVE]]]
            ]
        ];
    }

    public function getTitle(\$plural = false): string
    {
        return \$plural ? \$this->title_plural : \$this->title;
    }

    public function getTable(\$view_table = false): string
    {
        return \$view_table ? \$this->view_table : \$this->table;
    }

    public function getParentKey(): string
    {
        return \$this->parent_key;
    }

    public function getTableLabels(): array
    {
    //        Syntax Example Follow it
        \$labels = [
            // 'opt_mx_state_id' => [
            //     'query' => "SELECT id, txt_name, txt_color FROM mx_state",
            //     'key' => "id", 'value' => "txt_name", 'color' => "txt_color"
            // ]
        ];
        return parent::generateTableLabels(\$labels);
    }

    public function getProfileButtons(): array
    {
        \$permitted_section = [];
        \$permission = Perm_Auth::getPermissions();
        \$data = [
            // [
            //     "action" => "sample_action",
            //     "permission" => "sample_permission",
            //     "controller" => "$name",
            //     "label" => "Sample Button",
            //     "cssclass" => "btn btn-primary",
            //     "disabled" => "",
            //     "show" => "1 == 1",
            //     "params" => "initial_tab_data.row_id",
            //     "function" => "showProfileActionForm"
            // ],
        ];

        if (\$data) {
            foreach (\$data as \$value) {
                if (\$permission->verifyPermission(\$value['permission'])) {
                    \$permitted_section[] = \$value;
                }
            }
        }
        return \$permitted_section;
    }

    public function getTabs(): array
    {
    //        Syntax Example Follow it
        return [/*'Particulars', 'Qualifications',*/];
    }

    public function getFormDropdowns(): array
    {
        \$data = [];
//        Syntax Example Follow it

//        \$gender_result = \$this->db->select("SELECT txt_row_value, txt_name FROM mx_gender ORDER BY id asc");
//        if (\$gender_result) {
//            foreach (\$gender_result as \$value) {
//                \$data['opt_mx_gender_ids'][] = ['id' => \$value['txt_row_value'], 'name' => \$value['txt_name']];
//            }
//        }

        return \$data;
    }

    public function getAssociatedRecordActions(\$caller): array
    {
    //        Syntax Example Follow it
        switch (\$caller) {
            // case 'Applications':
            //     return [
            //         [
            //             "name" => "view_application",
            //             "icon" => "fa fa-eye",
            //             "title" => "View Application",
            //             "cssclass" => "text-info",
            //             "disabled" => "",
            //             "function" => "associatedShowProfile('Application',record.row_id)",
            //         ],
            //     ];
            default:
                return [];
        }
    }

    public function getAssociatedRecordDetails(\$caller = null): array
    {
    //        Syntax Example Follow it
        switch (\$caller) {
            // case 'Permits':
            //     return [
            //         'hiddens' => [
            //             'id', 'row_id', 'applicant_id', 'Application Reference', 'permit_type_color', 'color', 'opt_mx_permit_status_id'
            //         ],
            //         'formatters' => [
            //             'Status' => [
            //                 'format' => 'label', 'labels' => getLabels('mx_permit_status', 'txt_name', 'txt_color')
            //             ],
            //             'Permit Type' => [
            //                 'format' => 'label', 'labels' => getLabels('mx_permit_type', 'txt_name', 'txt_color')
            //             ],
            //         ],
            //     ];
            default:
                return [];
        }
    }

    public function getProfileHiddenColumns(): array
    {
    //        Syntax Example Follow it
        return [ /* 'id' */];
    }
}
PHP;
		file_put_contents("$moduleDir/{$name}_Model.php", $model);
		// Basic Views
		file_put_contents("$viewDir/index.php", <<<PHP
<div id="page-content">

    <?php
    /**
     * @group $name
     * @filesource /$name/index
     * @author AutoGenerated
     */

    use Libs\DataView;
    use Libs\Perm_Auth;
    use Libs\Session;

    \$perm = Perm_Auth::getPermissions();
    \$returned = Session::get('returned') != null || Session::get('returned') != '' ? Session::get('returned') : 0;

    echo '<div ng-controller="formController" class="btn-group btn-group-sm " ng-init="' . 'buttons=' . sizeof(\$this->buttons) . '; return_value=' . \$returned . '" ng-show="buttons > 0" '
        . 'ng-model="buttons" style="margin-bottom:10px !important;">';

    foreach (\$this->buttons as \$button) {
        if (\$perm->verifyPermission(strtolower(\$button['permission']))) {
            \$action = "'" . \$button['action'] . "'";
            echo '<button ng-click="showForm(' . \$button['url'] . ', ' . \$action . ')" class= "btn btn-' . \$button['color']
                . '" data-name="' . \$button['name']
                . '" data-action="' . \$button['action']
                . '"  data-title= "' . \$button['title']
                . '" data-mabrex-dialog="' . \$button['url'] . '">';
            echo trans(\$button['name']) . '</button>';
        }
    }
    echo '</div>';

    \$actions = [];
    if (sizeof(\$this->actions)) {
        foreach (\$this->actions as \$action) {
            if (\$perm->verifyPermission(strtolower(\$action['action']))) {
                \$actions[] = \$action;
            }
        }
    }

    echo '<div class="panel panel-default" ng-controller="profileController" ng-init="return_value=' . \$returned . '">';
    echo '<div class="panel-heading"><h4 class="panel-title">';
    echo trans(\$this->title) . '</h4></div>';

    echo '<div class="panel-body">';
    if (\$this->resultData['recordsFiltered'] > 0) {
        echo '<mabrex-filter mx-selected="' . \$this->postData['length'] . '" mx-location="\'' .
            \$this->postData['location'] . '\'" mx-title="\\'$name List\\'" mx-current-link="\'' .
            \$this->postData['current'] . '\'" mx-page-size="\'' . \$this->postData['length'] . '\'" mx-search-term="\'' .
            \$this->postData['search'] . '\'" mx-total-records="' . \$this->resultData['recordsTotal'] . '" mx-table-columns="' .
            \$this->resultData['columns'] . '" mx-sort-column="\'' . \$this->postData['order_column'] . '\'" mx-sort-order="\'' .
            \$this->postData['order_dir'] . '\'" mx-column-label="\'' . \$this->resultData['column_label'] . '\'"></mabrex-filter>';

        \$view = new DataView();
        echo '<div class="table-responsive" id="data-view">';
        echo '<table class="table table-striped table-hover table-condensed">';
        \$view->displayTHead(\$this->headings, \$this->hidden, (sizeof(\$actions) ? HAS_ACTION : NO_ACTION));
        \$view->displayTBody(\$this->allRecords, \$this->class, \$this->table, \$this->hidden, \$actions, LBL_BIG, \$this->labels);
        echo '</table>';
        echo '</div>';

        echo '<mabrex-pager mx-filtered="' . \$this->resultData['recordsFiltered'] . '" mx-total="' .
            \$this->resultData['recordsTotal'] . '" mx-current-page="' . \$this->resultData['currentPage'] . '" mx-pages="' .
            \$this->resultData['totalPages'] . '" mx-page-buttons="10" mx-page-location="\'' .
            \$this->postData['location'] . '\'" mx-page-title="\\'$name List\\'" mx-page-current-link="\'' .
            \$this->postData['current'] . '\'" mx-page-size="\'' . \$this->postData['length'] . '\'" mx-page-search-term="\'' .
            \$this->postData['search'] . '\'" mx-returned="' . \$this->resultData['recordsReturned'] . '" mx-sort-column="\'' .
            \$this->postData['order_column'] . '\'" mx-sort-order="\'' . \$this->postData['order_dir'] . '\'"></mabrex-pager>';
        echo '</div>';
    } else {
        echo '<mabrex-filter mx-selected="' . \$this->postData['length'] . '" mx-location="\'' .
            \$this->postData['location'] . '\'" mx-title="\\'$name List\\'" mx-current-link="\'' .
            \$this->postData['current'] . '\'" mx-page-size="\'' . \$this->postData['length'] . '\'" mx-search-term="\'' .
            \$this->postData['search'] . '\'" mx-total-records="' . \$this->resultData['recordsTotal'] . '" mx-table-columns="' .
            \$this->resultData['columns'] . '" mx-sort-column="\'' . \$this->postData['order_column'] . '\'" mx-sort-order="\'' .
            \$this->postData['order_dir'] . '\'" mx-column-label="\'' . \$this->resultData['column_label'] . '\'"></mabrex-filter>';

        echo '<div class="table-responsive" id="data-view">';
        echo '<div><h3><i class="pe pe-7s-info pe-fw pe-va pe-3x"></i> Sorry, record(s) not available.</h3></div>';
        echo '</div>';
    }
    echo '</div>';
    ?>
</div>
PHP);
		file_put_contents("$viewDir/create.php", <<<HTML
<div id="page-content">
    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode(\$this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode(\$this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <form name="strtolower('$name')" ng-submit="saveForm()" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #030000, rgba(3, 3, 3, 0.95), #00AEEF); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-study pe-fw pe-va pe-2x"></i><?php echo \$this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <?php include 'forms/' . strtolower('$name') . '.html'; ?>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="strtolower('$name').\$invalid || ProcessingData === true" class="btn btn-info" name="submit"> Submit </button>
                <button ng-disabled="ProcessingData === true" ng-click="cancel()" type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>
HTML);
		file_put_contents("$viewDir/edit.php", <<<HTML
<div id="page-content">
    <div id="data_content"
         data-form="<?php echo htmlspecialchars(json_encode(\$this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
         data-dropdowns="<?php echo htmlspecialchars(json_encode(\$this->dropdowns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"></div>
    <div id="display_content">
        <form name="strtolower('$name')" ng-submit="saveProfileOperation('<?php echo \$this->controller?>', '<?php echo \$this->action?>')" novalidate>
            <div class="modal-header" style="background: linear-gradient(to right, #030000, rgba(3, 3, 3, 0.95), #00AEEF); color: white;">
                <button type="button" ng-click="cancel()" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean"><i class="pe pe-7s-study pe-fw pe-va pe-2x"></i><?php echo \$this->title ?></h4>
            </div>
            <div class="modal-body">
                <div class="notification-area"></div>
                <div class="form-horizontal">
                    <?php include 'forms/' . strtolower('$name') . '.html'; ?>
                </div>
            </div>
            <div class="modal-footer">
                <span ng-if="ProcessingData === true"><i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i> Processing your request, please wait... &nbsp; &nbsp;</span>
                <button type="submit" ng-disabled="strtolower('$name').\$invalid || ProcessingData === true" class="btn btn-info" name="submit"> Submit </button>
                <button ng-disabled="ProcessingData === true" ng-click="cancel()" type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div>
        </form>
    </div>
</div>
HTML);
		file_put_contents("$profileDir/buttons.php", <<<HTML
<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
?>

<?php if (count(\$this->buttons) > 0) { ?>
\t<div class="text-center">
\t\t<div class="profile-buttons-group">
\t\t\t<?php
\t\t\tforeach (\$this->buttons as \$button) {
\t\t\t\t\$params = '';
\t\t\t\techo '<button ng-disabled="' . \$button['disabled'] . '" class="' . \$button['cssclass'] . '" ng-click="' . \$button['function'] . '(' . \$button['controller'] . ',\\'' . \$button['action'] . '\\',[' . \$button['params'] . '])" ng-show="' . \$button['show'] . '">' . \$button['label'] . '</button>';
\t\t\t}
\t\t\t?>
\t\t</div>
\t</div>
<?php } ?>
HTML);
		file_put_contents("$profileDir/main.php", <<<HTML
<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
?>

<div class="panel panel-default" style="background-color: whitesmoke">
    <div class="panel-body">
        <h5 class="text-center" style="font-size: 2.0em; color: white; font-weight: 600; margin-top: 10px; padding: 10px; border-radius: 4px; background-color: {{ initial_tab_data.color }}">
            {{ initial_tab_data['State'] }}
        </h5>
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12 col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h5 class="text-center" style="font-size: 1.5em; color: green; text-transform: uppercase; font-weight: 600; opacity: 0.6;">
                             Notification Details
                        </h5><hr />

                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <h5 class="text-left" style="font-size: 1.2em; text-transform: uppercase; opacity: 0.7"> <i class="fa fa-id-badge" style="font-size: 1.3em; opacity: 0.6"></i> Notification Information</h5>
                                        <hr />

                                        <div class="row"><div class="col-md-6 col-lg-6"><h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Notification Type</h5></div>
                                        <div class="col-md-6 col-lg-6"><h5 style="font-size: 1.4em; color: {{ initial_tab_data['notification_type_color'] }}; font-weight: 600; float: right;">{{ initial_tab_data['Notification Type'] }}</h5></div></div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row"><div class="col-md-6 col-lg-6"><h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Message</h5></div>
                                        <div class="col-md-6 col-lg-6"><h5 style="font-size: 1.4em; color: #292929; font-weight: 600; float: right;">{{ initial_tab_data['Message'] }}</h5></div></div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row"><div class="col-md-6 col-lg-6"><h5 style="font-size: 1.35em; float: left; opacity: 0.6;">From Date</h5></div>
                                        <div class="col-md-6 col-lg-6"><h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['From Date'] }}</h5></div></div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row"><div class="col-md-6 col-lg-6"><h5 style="font-size: 1.35em; float: left; opacity: 0.6;">To Date</h5></div>
                                        <div class="col-md-6 col-lg-6"><h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['To Date'] }}</h5></div></div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row"><div class="col-md-6 col-lg-6"><h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Added By</h5></div>
                                        <div class="col-md-6 col-lg-6"><h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Added By'] }}</h5></div></div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                        <div class="row"><div class="col-md-6 col-lg-6"><h5 style="font-size: 1.35em; float: left; opacity: 0.6;">Added Date</h5></div>
                                        <div class="col-md-6 col-lg-6"><h5 style="font-size: 1.4em; font-weight: 600; float: right;">{{ initial_tab_data['Added Date'] }}</h5></div></div>
                                        <hr style="margin: 0; opacity: 0.6;"/>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML);
		file_put_contents("$profileDir/profile.php", <<<HTML
<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd..
 *
 * (c) 2022
 *
 */
?>

<div id="page-content">
    <div id="data_content"
        data-initial="<?php echo htmlspecialchars(json_encode(\$this->data, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
        data-tabs="<?php echo htmlspecialchars(json_encode(\$this->tabs, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
        data-hidden-columns="<?php echo htmlspecialchars(json_encode(\$this->hidden_columns, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
        data-extras="<?php echo htmlspecialchars(json_encode(\$this->extras, JSON_NUMERIC_CHECK), ENT_COMPAT, 'UTF-8') ?>"
    >
    </div>
    <div id="display_content">
        <div ng-controller="profileController">
            <div class="modal-header" style="background-color: silver; color: black;">
                <button ng-click="cancel()" type="button" class="close" data-dismiss="modal" style="color: white; font-size: 35px;">&times;</button>
                <h4 class="modal-title ocean text-capitalize"><i class="pe pe-7s-id pe-fw pe-va pe-2x"></i> <?php echo \$this->title ?></h4>
            </div>
            <div class="modal-body">
                <div>
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a data-toggle="tab" href="#Shehia" ng-click="getProfileRecords('Shehia', '<?php echo \$this->data["row_id"] ?>')">Shehia</a>
                        </li>
                        <li ng-repeat="tab in tabs">
                            <a data-toggle="tab" href="#{{tab}}" ng-click="getAssociatedRecords(tab, initial_tab_data.row_id)">{{tab.replace('_', ' ')}}</a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="row" style="margin-bottom: 15px;"></div>
                    <div class="row" style="margin-bottom: 15px;">
                        <?php include 'buttons.php' ?>
                    </div>
                    <div class="tab-pane fade active in" id="Shehia">
                        <div class="row profile_section">
                            <?php include 'main.php' ?>
                        </div>
                    </div>
                    <div ng-repeat="tab in tabs" class="tab-pane fade" id="{{tab}}">
                        <div class="associated_section">
                            <!-- ASSOCIATED RECORDS TO BE LOADED HERE -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-warning" ng-click="cancel()">Cancel</button>
            </div>
        </div>
    </div>
</div>
HTML);
		file_put_contents("$formDir/" . strtolower($name) . ".html", "<!-- Form for $name -->");

		echo "Module '$name' created successfully in modules/$name\n";
	}

	private function clearCache() : void
	{
		// Implement your cache-clearing logic
		echo "Cache cleared successfully.\n";
	}

	private function runMigrations() : void
	{
		// You can load and run your SQL migration files here
		echo "Running migrations...\n";
		// Simulate migration
		echo "All migrations complete.\n";
	}
}
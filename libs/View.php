<?php

/**
 * Description of View
 *
 * @author abdirahmanhassan
 */
namespace Libs;

class View {


    public string $title;
    public array $buttons;
    public $class;
    public $allRecords;
    public array $headings;
    public array $hidden;
    public array $actions;
    public $table;
    public $resultData;
    public $postData;
    public array $colors;
    public array $data;
    public array $dropdowns;
    public string $form_title;
    public array $disabled;
    public $primary_color;
    public $secondary_color;
    public array $tabs;
    public array $hidden_columns;
    public array $extras;
    public string $subtitle;
    public array $account_details;
    public $msg;
    public $sub;
    public $icon;
    public array $permission_details;
    public array $formHiddenFields;
    public array $fields;
    public string $controller;
    public string $action;
    public string $name;
    public $table_headers;
    public $caller;
    public $labels;
    public $show_cards;
    public $formatters;
    public $hiddens;
    public $form_headers;
    public array $report_types;
    public mixed $posted_data;
    public array $form_fields;
	public array $filtering_fields;

	public function render($module, $name, $noInclude = false): void
    {
        $my_theme = "rahisi";

        $user_id = isset($_SESSION['id']) ?? null;

        require MX17_APP_ROOT . "/views/header.php";

        if ($user_id) {
            require MX17_APP_ROOT . "/views/body.php";
        }

        require MX17_APP_ROOT . '/modules/' . $module . '/Views/' . $name . '.php';
        require MX17_APP_ROOT . "/views/footer.php";
    }

    public function renderFull($name, $noInclude = false): void
    {
        $my_theme = "rahisi";

        $user_id = $_SESSION['id'];
        require MX17_APP_ROOT . "/views/header.php";
        require MX17_APP_ROOT . "/views/body.php";
        require MX17_APP_ROOT . '/' . $name . '.php';
        require MX17_APP_ROOT . "/views/footer.php";
    }

    public function renderJson($module, $name, $noInclude = false): void
    {
        require MX17_APP_ROOT . '/modules/' . $module . '/Views/' . $name . '.php';
    }
   
}

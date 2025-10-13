<?php

/**
 * Description of DataView
 *
 * @author abdirahmanhassan
 */

namespace Libs;

use Modules\Project\Project;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DataView
{

    public function __construct()
    {

    }

    public static function displayTHead($headers, $hidden, $actions = FALSE)
    {
        $th = '';
        echo '<thead class="thead-red"><tr>';
        foreach ($headers as $heading) {
            if ($heading == "id") {
                //id heading
                echo '<th class="hide">id</th>';
            } elseif (!in_array($heading, $hidden) && ($heading !== 'row_id' && $heading !== "txt_row_value")) {
                $th = DataView::trimHeaders($heading);
                if (!in_array($heading, ["txt_id_number"]) && strripos($heading, '_id') !== false) {
                    if ($heading == 'int_parent_id') {
                        $th = 'Parent';
                    } elseif ($heading == 'int_from_council_id') {
                        $th = 'From Council';
                    } elseif ($heading == 'int_to_council_id') {
                        $th = 'To Council';
                    } elseif ($heading == 'opt_mx_complaint_status_id') {
                        $th = 'Status';
                    } elseif ($heading == 'opt_mx_complaint_source_id') {
                        $th = 'Source';
                    } elseif ($heading == 'opt_mx_complaint_priority_id') {
                        $th = 'Priority';
                    } else {
                        $th = str_replace("_", " ", substr(substr($th, 0, -3), 3));
                    }
                }

                if ($th == 'user') {
                    $th = "Created By";
                }
                echo '<th>' . ucwords(trans($th)) . '</th>';

            } else {
                echo '<th class="hide">' . ucwords($th) . '</th>';
            }
        }

        if ($actions) {
            echo '<th style="text-align:center;" id="class_controls">';
            echo ucwords(trans("Actions")) . '</th>';
        }
        echo '</tr></thead>';
    }

    //return the value of each property from given object to display

    public static function trimHeaders($th)
    {

        if ($th == "email") {
            $heading = $th;
        } elseif ($th == "password") {
            $heading = $th;
        } else {
            if ($th == 'int_from_council_id') {
                $heading = "From Council";
            } elseif ($th == 'int_to_council_id') {
                $heading = "To Council";
            } elseif ($th == 'working_hour_state_id') {
                $heading = "Status";
            } elseif (strpos($th, "_") == 3) {
                if ($th == "int_delivery_id") {
                    $heading = ucfirst(str_replace("_", " ", $th));
                } elseif ($th == 'txt_attended_by') {
                    $heading = "Approved By";
                } elseif ($th == 'dat_attended_date') {
                    $heading = "Approved Date";
                } else {
                    $heading = ucfirst(str_replace("_", " ", substr($th, 4)));
                }
            } else {
                $heading = $th;
            }
        }
        return $heading;
    }

    public static function displayTBody($object, $class, $cls_table, $hidden, $actions = array(), $label_size = 'getSmallLabel', $labels = array(), $is_pending = false, $formatters = [])
    {

        foreach ($object as $rows) {
            $my_field = "";
            $my_value = "";
            $primary_sim = "";
            $primary_sim_value = "";
            $class_color = "";
            echo '<tr class="mabrex-clickable-row ' . $class_color . '">';
            $url = str_replace("_Model", "", $class); //to use for profile dialog
            foreach ($rows as $field => $value) {
                $key = DataView::trimHeaders($field);
                if ($key == "id") { //primary key
                    echo '<td class="hide" data-label="ID" id="key" data-mabrex-id="' . $value . '" data-mabrex-class="' . $url . '" data-mabrex-table="' . $cls_table . '">'
                        . '<input type="checkbox" name="' . $class . '"></td>';
                    if ($is_pending) {
                        echo '<input type="hidden" class="show_pending" value="' . $value . '" />';
                    }
                    $object_id = $value;
                } else {
                    if (!in_array($field, $hidden) && ($field !== 'row_id' && $field !== "txt_row_value")) { // not hidden fields
                        if (is_array($labels) && array_key_exists($field, $labels)) {
                            $label_value = $labels[$field][$value]['value'];
                            $label_color = $labels[$field][$value]['color'];
                            if ($label_color != "") {
                                $label_value = "<span class='label' style='margin:0px;font-size:10px;padding:4px;background: $label_color'>$label_value</span>";
                            }
                            echo "<td data-label='{$field}'>{$label_value}</td>";
                        } else { //normal key
                            if (array_key_exists($key, $formatters)) {
                                if ($formatters[$key]['format'] == 'number') {
                                    echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">
                                            <strong class="blue">' . number_format(floatval($value), 2) . '</strong></td>';
                                } elseif ($formatters[$key]['format'] == 'date') {
                                    if ($value != "" && $value != null) {
                                        if (strlen($value) > 12) {
                                            echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . date("d M Y @ H:i:s", strtotime($value)) . '</strong></td>';
                                        } else {
                                            echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . date("d M Y", strtotime($value)) . '</strong></td>';
                                        }
                                    } else {
                                        echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . $value . '</td>';
                                    }
                                } elseif ($formatters[$key]['format'] == 'time') {
                                    if ($value != "" && $value != null) {
                                        echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . date("H:i:s", strtotime($value)) . '</strong></td>';
                                    } else {

                                        echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . $value . '</td>';
                                    }
                                }
                            } elseif (stripos($key, "mobile") !== FALSE || stripos($key, "sim") !== FALSE || stripos($key, "telephone") !== FALSE || stripos($key, "phone") !== FALSE) {
                                echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . DataView::formatPhoneNo($value) . '</td>';
                                if ($field == "txt_primary_sim") {
                                    $primary_sim = $field; //For SMS_Device action
                                    $primary_sim_value = $value; //if value is empty then SMS_Device action will be inactive
                                }
                            } elseif (stripos($field, "chk_") !== FALSE || $field == "int_require_dual_activity") {
                                $checked = ($value == 1) ? "Yes" : "No";
                                echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . $checked . '</td>';
                            } elseif (stripos($field, "_colour") !== FALSE) {
                                echo ' <td bgcolor="' . $value . '" data-label="' . ucwords(str_replace("_", " ", $key)) . '" style="color:white">' . $value . '</td>';
                            } elseif ($field == "int_escalated" || $field == "int_overdue") {
                                echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '"><span class="label label-' . ($value == 1 ? 'danger' : 'default') . '">' . ($value == 1 ? 'Yes' : 'No') . '</span></td>';
                            } else {
                                if ($key == "Action") {
                                    $value = DataView::getActionName($value);
                                }
                                echo ' <td data-label="' . ucwords(str_replace("_", " ", $key)) . '">' . html_entity_decode($value ? $value : '') . '</td>';
                            }
                        }
                    } else {

                        if ($field == "txt_row_value" || $field == "row_id") {
                            echo '<td class="hide" data-label="row_id" id="row_id" data-mabrex-row-id="' . $value . '" data-mabrex-class="' . $url . '" data-mabrex-table="' . $cls_table . '">';
                            if ($is_pending) {
                                echo '<input type="hidden" class="show_pending" value="' . $value . '"/>';
                            }

                            echo '</td>';

                            $row_id = $value;
                        } else {
                            echo '<td class="hide" data-label="ID" id="key" data-mabrex-id="' . $value . '" data-mabrex-class="' . $url . '" data-mabrex-table="' . $cls_table . '">'
                                . '<input type="checkbox" name="' . $class . '">';
                            if ($is_pending) {
                                echo '<input type="hidden" class="show_pending" value="' . $value . '"/>';
                            }
                            echo '</td>';
                        }
                    }
                }
            }

            if (count($actions) > 0) {
                DataView::generateActionButtons($actions, $object_id, $class, $cls_table, $rows, $row_id);
            }
            echo '</tr>';
        }
    }

    static function formatPhoneNo($tel)
    {
        if (preg_match('/^(\d{3})(\d{3})(\d{6})$/', $tel, $matches)) {
            $result = "+" . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            return $result;
        } else {
            return $tel;
        }
    }

    private static function getActionName($action)
    {
        $action_name = $action;
        if ($action == "post_edit") {
            $action_name = "update";
        } elseif ($action == "post_register_account") {
            $action_name = "Add Account";
        } elseif ($action == "post_add_float") {
            $action_name = "Add Float";
        } elseif ($action == "post_reset_pin") {
            $action_name = "Reset Pin";
        }
        if (!empty($action_name)) {
            if (substr($action_name, 0, 5) == "post_") {
                return ucwords(str_replace("_", " ", substr($action_name, 5)));
            } else {
                return ucwords(str_replace("_", " ", $action_name));
            }
        }

    }

    private static function generateActionButtons($actions, $object_id, $class, $cls_table, $row, $row_id)
    {
        echo '<td class="text-center mabrex-clickable-exclude" id="data-view-actions">';
        foreach ($actions as $control) {
            if (isset($control['disabled'])) {
                $status = self::evaluateDisabledActions($row, $control['disabled']);
            } else {
                $status = 0;
            }
            if ($status != 1) {
                echo '<a id="' . $control['name'] . '" style="text-decoration:none; margin:1px; padding:1px;" href="#" class="' . $control['color'] . '" '
                    . 'data-mabrex-row-id="' . $row_id . '" data-mabrex="' . $object_id . '" data-mabrex-table="' . $cls_table . '" '
                    . 'data-mabrex-url="' . $control['url'] . '" data-mabrex-class="' . $class . '" '
                    . ' data-mabrex-user="" data-mabrex-dialog="' . $control['url'] . '" data-action="' . $control['name'] . '">'
                    . '<i class="fa ' . $control['icon'] . ' text-centered mxtooltip" title="' . str_replace("_", " ", $control['name']) . '" style="vertical-align:middle;" ></i>'
                    . '</a>&nbsp';
            } else {
                echo '<a href="#" class="btn disabled" role="button" style="text-decoration:none; margin:1px; padding:1px;"><i class="fa ' . $control['icon'] . ' text-centered mxtooltip" title="' . str_replace("_", " ", $control['name']) . '" style="vertical-align:middle;" ></i></a>&nbsp';
            }
        }
        echo '</td>';
    }

    private static function evaluateDisabledActions($row, $disabled_values)
    {
        $evaluation = null;
        if (isset($disabled_values['OR'])) {
            $evaluation = 0;
            foreach ($disabled_values['OR'] as $key => $value) {
                $field_value = $row[$key] ?? '';
                $values_is_array = is_array($value);
                if ($values_is_array) {
                    foreach ($value as $item) {
                        $evaluated = $field_value == $item;
                        $evaluation = $evaluated || $evaluation;
                    }
                } else {
                    $evaluated = $field_value == $value;
                    $evaluation = $evaluated || $evaluation;
                }
            }
            unset($disabled_values['OR']);
        }

        if (isset($disabled_values['AND'])) {
            if ($evaluation == null) $evaluation = 1;
            foreach ($disabled_values['AND'] as $key => $value) {
                $field_value = $row[$key];
                $values_is_array = is_array($value);
                if ($values_is_array) {
                    foreach ($value as $item) {
                        $evaluated = $field_value == $item;
                        $evaluation = $evaluated && $evaluation;
                    }
                } else {
                    $evaluated = $field_value == $value;
                    $evaluation = $evaluated && $evaluation;
                }
            }
            unset($disabled_values['AND']);
        }

        if (count($disabled_values) > 0) {
            if ($evaluation == null) $evaluation = 1;
            foreach ($disabled_values as $key => $value) {
                $field_value = $row[$key] ?? '';
                $values_is_array = is_array($value);
                if ($values_is_array) {
                    foreach ($value as $item) {
                        $evaluated = $field_value == $item;
                        $evaluation = $evaluated && $evaluation;
                    }
                } else {
                    $evaluated = $field_value == $value;
                    $evaluation = $evaluated && $evaluation;
                }
            }
        }

        return $evaluation;
    }

    public static function toString($data)
    {
        foreach ($data as $key => $value) {
            $key = DataView::trimHeaders($key);
            if ($key == "id") {
                //
            } elseif (strpos($key, '_id') !== false) {
                $name = str_replace("_", " ", substr(substr($key, 0, -3), 3));
                echo '<div class="form-group"><label class="col-lg-3 blue">' . ucwords($name) . '</label><label class="col-lg-9">';
                $table = str_replace("_id", "", $key);
                $value = DataView::getDescription($value, $table);
                echo $value . '</label></div>';
            } else {
                echo '<div class="form-group"><label class="col-lg-3 blue">' . ucwords($key) . '</label><label class="col-lg-9">';
                echo $value . '</label></div>';
            }
        }
    }

    //function to get value of give id

    public static function getTableName($field)
    {
        $table = substr(DataView::strLastReplace("_id", "", $field), 4);
        if (in_array($table, ["approved_by", "recorded_by", "added_by", "made_by", "updated_by"])) {
            $table = "mx_user";
        }
        return $table;
    }

    static function strLastReplace($search, $replace, $subject)
    {
        $pos = strrpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    //retrieve data from database

    static function getDisabledAction()
    {
        return [
            ["name" => "Unavailable", "icon" => "fa-ban", "color" => "grey", "url" => ""]
        ];
    }

    private static function getFKValues($table, $fieldname, $id, $label_size, $colors)
    {

        if (isset($colors[$fieldname])) {
            $color_list = $colors[$fieldname];
            echo '<td data-label="' . ucwords(str_replace("_", " ", $table)) . '">' . DataView::colorLabels($color_list, $id, DataView::getDescription($id, $table)) . '</td>';
        } else if (DataView::requireLabel($fieldname)) {
            echo '<td data-label="' . ucwords(str_replace("_", " ", $table)) . '">' . DrawLabel::$label_size($id, DataView::getDescription($id, $table)) . '</td>';
        } else {

            if ($table == "mx_sms_language") {
                if ($id == 1) {
                    $image = "tz_flag.png";
                } else {
                    $image = "en_flag.png";
                }
                echo '<td style="text-align: center;" data-label="' . ucwords(str_replace("_", " ", $table)) . '"><img src="' . URL . '/assets/images/' . $image . '" width="25px;"></td>';
            } elseif ($table == "mx_visa_on_mobile") {
                if ($id == 1) {
                    $image = "visa_icon.png";
                } else {
                    $image = "umoja_icon.png";
                }
                echo '<td style="text-align: center;" data-label="' . ucwords(str_replace("_", " ", $table)) . '"><img src="' . URL . '/assets/images/' . $image . '" width="35px;"></td>';
            } elseif ($table == "delivery") {
                echo '<td data-label="' . ucwords(str_replace("_", " ", $table)) . '">' . $id . '</td>';
            } elseif ($fieldname == "opt_mx_application_source_id") {
                echo ' <td data-label="' . ucwords(str_replace("_", " ", $fieldname)) . '">';
                switch ($id) {
                    case 1:
                        echo '<i class="pe pe-7s-phone" style="font-size: 1.5em; color: #000"></i>';
                        break;
                    case 2:
                        echo '<i class="pe pe-7s-global" style="font-size: 1.5em; color: #000"></i>';
                        break;
                    case 3:
                        echo '<i class="pe pe-7s-call" style="font-size: 1.5em; color: #000"></i>';
                        break;
                    case 4:
                        echo '<i class="pe pe-7s-chat" style="font-size: 1.5em; color: #000"></i>';
                        break;
                    default:
                        echo '';
                        break;
                }
                echo '</td>';
            } else {
                echo '<td data-label="' . ucwords(str_replace("_", " ", $table)) . '">' . DataView::getDescription($id, $table) . '</td>';
            }
        }
    }

    private static function colorLabels($colors, $id, $status)
    {
        return '<span class="label" style="margin:0px;font-size:10px;padding:4px;background:' . $colors[$id] . '">' . $status . '</span>';
    }

    public static function getDescription($id, $table)
    {

        $array = DataView::loadData($table);
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($key == $id) {
                    return DataView::checkTranslation($value);
                }
            }
        }
    }

    private static function loadData($table)
    {
        $array = array();
        $database = new Database();
        $sql = "SELECT * FROM " . strtolower($table) . " ORDER BY id ASC";
        $result = $database->select($sql);

        foreach ($result as $value) {
            if (strtolower($table) == ("mx_district")) {
                $array[$value['id']] = $value['txt_name_sw'];
            } else {
                $array[$value['id']] = $value['txt_name'];
            }
        }
        unset($database);
        return $array;
    }

    public static function checkTranslation($value)
    {
        return trans($value);
    }

    private static function requireLabel($field)
    {
        $label_fields = array(
            'txt_delivery_status', 'opt_mx_status_id', 'opt_mx_center_status_id',
            'opt_mx_state_id', 'opt_mx_sent_id', 'dat_posted_date',
            'txt_status', 'opt_mx_application_status_id', 'opt_mx_published_id',
            'working_hour_state_id', 'opt_mx_payment_status_id',
            'opt_mx_enquiry_status_id', 'opt_mx_invoice_status_id', 'opt_mx_empty_leg_status_id',
            'opt_mx_quotation_aircraft_status_id', 'opt_mx_survey_status_id'
        );
        if (in_array($field, $label_fields)) {
            return TRUE;
        }
        return FALSE;
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }
        return $bytes;
    }

}

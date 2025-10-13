<?php

/**
 * Description of Form
 *
 * @author abdirahmanhassan
 */
//include 'Database.php';
date_default_timezone_set('Africa/Dar_es_Salaam');

class Form extends Helper {

    private static $attributes = array("txt" => "text", "tar" => "textarea", "chk" => "checkbox",
        "int" => "number", "mon" => "month", "tim" => "time", "opt" => "dropdown",
        "dat" => "date", "fil" => "file", "email" => "email",
        "password" => "password", "rad" => "radio", "dbl" => "number");

    public static function generateForm($class, $form, $hiddenFields = array()) {
        $fields = $class["properties"];
        $requiredFields = $class["required"];

        foreach ($fields as $field) {
            if (!in_array($field, $hiddenFields)) {//check if not hidden field                
                Form::getFormFields($field, $form, $requiredFields);
            } else { //hidden fields
                Form::getFormHiddenFields($field);
            }
        }
    }

    private static function getFormFields($field, $form, $requiredFields) {
        $fields = Form::getInputTypes($field);
        $input_type = $fields[0];
        $fieldname = $fields[1];
        if ($input_type == "textarea") {
            echo '<div class="form-group"><label for="' . $field . '" class="col-lg-3 control-label">'
            . ucwords(str_replace("_", " ", $fieldname))
            . '</label><div class="col-lg-9"><textarea class="form-control" cols="50" row="5" id="'
            . $field . '" placeholder="Write ' . str_replace("_", " ", $fieldname) . '" name="'
            . $field . '"></textarea></div></div>';
        } elseif ($input_type == "dropdown") {
            Form::getFormDropDown($fieldname);
        } else {
            $autocomplete_array = Form::requireAutocomplete($field);

            if ($autocomplete_array["status"] == "TRUE") {
                $table_name = $autocomplete_array["table"];
                Form::writeAutoCompleteFields($field, $requiredFields, $table_name, $input_type, $form, $fieldname);
            } else {
                Form::writeNormalFields($field, $requiredFields, $input_type, $form, $fieldname);
            }
        }
    }

    private static function getInputTypes($field) {
        if ($field == "email") {
            $key = "email";
            $fieldname = $key;
        } elseif ($field == "password") {
            $key = "password";
            $fieldname = $key;
        } elseif ($field == "id") {
            $key = "txt";
            $fieldname = $field;
        } else {
            if (strpos($field, "_") == 3) {
                $key = substr($field, 0, 3);
                $fieldname = substr($field, 4);
            } else {
                $key = $field;
                $fieldname = $field;
            }

            if (strpos($fieldname, '_id') !== false) {
                $fieldname = substr($fieldname, 0, -3);
            }
        }

        return array(Form::$attributes[$key], $fieldname);
    }

    private static function getFormHiddenFields($field) {
        echo '<input type="hidden" id="' . $field . '" name="' . $field . '" value="' . NULL . '" />';
    }

    private static function getFormDropdown($field) {
        $label = ucwords(substr(str_replace("id", " ", str_replace("_", " ", $field)), 2));
        echo '<div class="form-group">';
        echo '<label for="' . $field . '" class="col-lg-3 control-label">' . $label . '</label>';
        echo '<div class="col-lg-9"><select name="opt_' . $field . '_id" id="opt_' . $field . '_id" class="form-control">'; // Old name $table . 'id'
        Form::getDropdownValues($field);
        echo '</select></div></div>';
    }

    public static function getDropdownValues($table) {
        $label = ucwords(substr(str_replace("id", " ", str_replace("_", " ", $table)), 2));
        $array = parent::loadData($table);
        if (count($array) > 1) {
            echo '<option value=""> Select ' . $label . '</option>';
            foreach ($array as $key => $value) {
                echo "<option value=\"" . $key . "\">" . $value . "</option>";
            }
        } elseif (count($array) == 1) {
            foreach ($array as $key => $value) {
                echo "<option value=\"" . $key . "\">" . $value . "</option>";
            }
        } else {
            echo "<option>No data available</option>";
        }
    }

    public static function getReportDropdownValues($table, $name) {
        $label = ucwords(str_replace("_", " ", $name));
        $array = parent::loadData($table);
        if (count($array) > 1) {
            echo '<option value=""> Select ' . $label . '</option>';
            foreach ($array as $key => $value) {
                echo "<option value=\"" . $key . "\">" . $value . "</option>";
            }
        } elseif (count($array) == 1) {
            foreach ($array as $key => $value) {
                echo "<option value=\"" . $key . "\">" . $value . "</option>";
            }
        } else {
            echo "<option>No data available</option>";
        }
    }

    public static function editForm($object, $hiddenFields) {
        foreach ($object as $key => $value) {
            $fields = Form::getInputTypes($key);
            $input_type = $fields[0];
            $fieldname = $fields[1];

            if ($input_type == "textarea") {
                echo '<div class="form-group"><label for="' . $fieldname . '" class="col-lg-3 control-label">'
                . ucwords(str_replace("_", " ", $fieldname)) . '</label>'
                . '<div class="col-lg-9"><textarea class="form-control" cols="50" row="5" id="'
                . $key . '" name="' . $key . '">' . $value . '</textarea></div></div>';
            } elseif ($input_type == "dropdown") {
                Form::getDropdownEditValues($key, $value, $fieldname);
            } else {
                if (!in_array($key, $hiddenFields)) {
                    $autocomplete_array = Form::requireAutocomplete($key);
//
                    if ($autocomplete_array["status"] == "TRUE") {
                        $table_name = $autocomplete_array["table"];
                        Form::writeEditAutoCompleteFields($key, $fieldname, $input_type, $table_name, $value);
                    } else {
                        Form::writeEditNormalFields($key, $fieldname, $input_type, $value);
                    }
                } else {
                    echo '<div class="hide form-group"><label for="' . $fieldname . '" class="col-lg-3 control-label">'
                    . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9"><input class="form-control" type="hidden" id="'
                    . $key . '" name="' . $key . '" value="' . $value . '"/></div></div>';
                }
            }
        }
    }

    private static function getDropdownEditValues($field, $id, $label) {
        echo ' <div class="form-group"><label for="' . $field . '" class="col-lg-3 control-label">' . $label . '</label>';
        echo '<div class="col-lg-9">'
        . '<select class="form-control" name="' . $field . '"  id="' . $field . '" value="' . $id . '">';
        Form::getSelectedValue($id, $label);
        echo '</select></div></div>';
    }

    private static function getSelectedValue($id, $table) {
        $array = parent::loadData($table);
        if (count($array) > 1) {

            foreach ($array as $key => $value) {
                echo '<option value="' . $key . '"';
                if ($key == $id) {
                    echo 'selected= "selected"';
                }
                echo '>' . $value . "</option>";
            }
        } elseif (count($array) == 1) {
            foreach ($array as $key => $value) {
                echo "<option value=\"" . $key . "\">" . $value . "</option>";
            }
        }
    }

    /**
     * Upload the bill file to the /csv/ directory 
     */
    function uploadFile() {
        $target_dir = "./assets/images/";
        $target_file = $target_dir . basename($_FILES["file"]["name"]);
        $uploadOk = 1;
        $fileType = pathinfo($target_file, PATHINFO_EXTENSION);

        if (file_exists($target_file)) {
            echo '<script>$(document).ready(function() {errorHandler("Upload Failed, file already exists");});</script>';
            $uploadOk = 0;
        } elseif ($fileType != "pdf" && $fileType != "png" && $fileType != "jpg") {
            echo '<script>$(document).ready(function() {errorHandler("Upload Failed, only PDF files are allowed");});</script>';
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            echo '<script>$(document).ready(function() {errorHandler("Sorry, your file was not uploaded");});</script>';
        } else {
            move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
        }
    }

    function generateHorizontalFormHeader($class, $hiddenFields = array(), $buttons = true) {
        $fields = $class["properties"];

        echo '<thead><tr>';
        foreach ($fields as $field) {
            if (in_array($field, $hiddenFields)) {//check if hidden field                
                //no heading
            } else { //if not hidden fields
                $data = Form::getInputTypes($field);
                $fieldname = $data[1];
                if ($fieldname == "id") {
                    echo '<th class="hide">' . $fieldname . '</th>';
                } else {
                    echo '<th>' . $fieldname . '</th>';
                }
            }
        }
        if ($buttons == true) {
            echo '<th></th>';
        }
        echo '</tr></thead>';
    }

    function generateHorizontalFormFields($class, $hiddenFields = array()) {
        $fields = $class["properties"];
        $requiredFields = $class["required"];
        echo '<tbody><tr>';
        foreach ($fields as $field) {
            if (!in_array($field, $hiddenFields)) {//check if not hidden field                
                Form::getHorizontalFormFields($field, $requiredFields);
            } else { //hidden fields
                Form::getHorizontalFormHiddenFields($field);
            }
        }
        echo '<td><label class="button primary small data-mabrex-add-row text-center"><i class="fa fa-plus"></i></label></td>';
        echo '</tr></tbody>';
        //retrieve fields of given object
    }

    private static function getHorizontalFormFields($field, $requiredFields) {
        $fields = Form::getInputTypes($field);
        $input_type = $fields[0];
        $fieldname = $fields[1];
        if ($input_type == "textarea") {
            echo '<td><textarea cols="50" row="5" id="' . $field . '" placeholder="Write '
            . str_replace("_", " ", $fieldname) . '" name="' . $field . '"></textarea></td>';
        } elseif ($input_type == "dropdown") {
            Form::getHorizontalFormDropdown($fieldname);
        } else {
            if ($field == "id") {
                echo '<td style="display: none;"><input type="hidden" id="' . $field . '" name="' . $field
                . '" class="col-12"/></td>';
            } else {
                if (in_array($field, $requiredFields)) {
                    echo '<td><input type="' . $input_type . '" id="' . $field . '" placeholder="Enter '
                    . str_replace("_", " ", $fieldname) . '" name="' . $field
                    . '" class="col-12"  required/></td>';
                } else {
                    echo '<td><input type="' . $input_type . '" id="' . $field . '" placeholder="Enter '
                    . str_replace("_", " ", $fieldname) . '" name="' . $field . '" class="col-12" />'
                    . ($input_type == 'checkbox' ? ' <input type="hidden" name="' . $field . '" value="0" />' : '') . '</td>';
                }
            }
        }
    }

    private static function getHorizontalFormDropdown($field) {
        echo '<td><select name="opt_' . $field . '_id" id="opt_' . $field . '_id">'; // Old name $table . 'id'
        Form::getDropdownValues($field);
        echo '</select></td>';
    }

    private static function getHorizontalFormHiddenFields($field) {
        echo '<td style="display: none"><input type="hidden" id="' . $field . '" name="' . $field . '" value="' . NULL . '" /></td>';
    }

    private static function requireAutocomplete($field) {
        $autocomplete_array = [["name" => "txt_em_applicant_id", "table" => "em_applicant"],
            ["name" => "txt_approved_by", "table" => "mx_user"],
            ["name" => "txt_recorded_by", "table" => "mx_user"]
        ];
        foreach ($autocomplete_array as $autocomplete_fields) {
            if (in_array($field, $autocomplete_fields)) {
                return ["status" => "TRUE", "table" => $autocomplete_fields["table"]];
            }
        }
        return ["status" => "FALSE"];
    }

    //This is for generate form
    private static function writeAutoCompleteFields($field, $requiredFields, $table_name, $input_type, $form, $fieldname) {
        if (in_array($field, $requiredFields)) {
            echo '<div class="form-group"><label for="' . $field . '" class="col-lg-3 control-label">' . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9">'
            . '<input type="' . $input_type . '" id="' . $field
            . '_label" placeholder="Enter ' . str_replace("_", " ", $fieldname) . '" name="' . $field . '_label" '
            . ' class="autocomplete form-control ' . $field . '_label" data-table ="' . $table_name . '" '
            . 'ng-class="{{ ' . $form . '.' . $field . '.$invalid && !' . $form . '.' . $field . '.$pristine }}" '
            . 'ng-model="' . $field . '" required/>'
            . '<input type="hidden" id="' . $field . '" name="' . $field . '" class="' . $field . '"/>'
            . '</div></div>';
        } else {
            echo '<div class="form-group"><label for="' . $field . '" class="col-lg-3 control-label">' . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9">'
            . '<input type="' . $input_type . '" id="' . $field
            . '"_label placeholder="Enter ' . str_replace("_", " ", $fieldname) . '" name="' . $field . '_label" '
            . 'class="autocomplete form-control ' . $field . '_label" data-table ="' . $table_name . '"  '
            . 'ng-class="{{ ' . $form . '.' . $field . '.$invalid && !' . $form . '.' . $field . '.$pristine }}" '
            . 'ng-model="' . $field . '" />'
            . ($input_type == 'checkbox' ? ' <input type="hidden" name="' . $field . '" value="0" />' : '') . '</div>'
            . '<input type="hidden" id="' . $field . '" name="' . $field . '" class="' . $field . '"/>'
            . '</div></div>';
        }
    }

    //This is for generate form
    private static function writeNormalFields($field, $requiredFields, $input_type, $form, $fieldname) {
        if (in_array($field, $requiredFields)) {
            echo '<div class="form-group"><label for="' . $field . '" class="col-lg-3 control-label">' . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9">'
            . '<input type="' . $input_type . '" id="' . $field
            . '" placeholder="Enter ' . str_replace("_", " ", $fieldname) . '" name="' . $field . '" '
            . ' class="form-control" '
            . 'ng-class="{{ ' . $form . '.' . $field . '.$invalid && !' . $form . '.' . $field . '.$pristine }}" '
            . 'ng-model="' . $field . '" required/></div>'
            . '</div>';
        } else {
            echo '<div class="form-group"><label for="' . $field . '" class="col-lg-3 control-label">' . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9">'
            . '<input type="' . $input_type . '" id="' . $field
            . '" placeholder="Enter ' . str_replace("_", " ", $fieldname) . '" name="' . $field . '" '
            . 'class="form-control"  '
            . 'ng-class="{{ ' . $form . '.' . $field . '.$invalid && !' . $form . '.' . $field . '.$pristine }}" '
            . 'ng-model="' . $field . '" />'
            . ($input_type == 'checkbox' ? ' <input type="hidden" name="' . $field . '" value="0" />' : '') . '</div>'
            . '</div>';
        }
    }

    //This is for edit form
    private static function writeEditAutoCompleteFields($key, $fieldname, $input_type, $table_name, $value) {
        echo '<div class="form-group"><label for="' . $fieldname . '" class="col-lg-3 control-label">'
        . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9">'
        . '<input class="form-control autocomplete ' . $key . '_label" data-table ="' . $table_name . '" type="'
        . $input_type . '" id="' . $key . '_label" name="' . $key . '_label" value="' . DataView::getDescription($value, $table_name) . '"/>'
        . '<input type="hidden" id="' . $key . '" name="' . $key . '" class="' . $key . '"/>'
        . '</div></div>';
    }

    //This is for edit form
    private static function writeEditNormalFields($key, $fieldname, $input_type, $value) {
        echo '<div class="form-group"><label for="' . $fieldname . '" class="col-lg-3 control-label">'
        . ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9"><input class="form-control" type="'
        . $input_type . '" id="' . $key . '" name="' . $key . '" value="' . $value . '"/></div></div>';
    }

}

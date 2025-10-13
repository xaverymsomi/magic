<?php

/**
 * Description of Form
 *
 * @author abdirahmanhassan
 */
//include 'Database.php';
date_default_timezone_set('Africa/Dar_es_Salaam');

class EditFormGen {


    function getInputTypes($field) {
        $attributes = array("txt" => "text", "tar" => "textarea", "chk" => "checkbox",
        "int" => "number", "mon" => "month", "tim" => "time", "opt" => "dropdown",
        "dat" => "date", "fil" => "file", "email" => "email",
        "password" => "password", "rad" => "radio", "dbl" => "number");
        
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

        return array($attributes[$key], $fieldname);
    }

    function editForm($object, $hiddenFields, $fh) {

        foreach ($object as $key => $value) {
       
            $fields = $this->getInputTypes($key);
            $input_type = $fields[0];
            $fieldname = $fields[1];

            if ($input_type == "textarea") {
                fwrite($fh,'<div class="form-group">'.PHP_EOL);
                fwrite($fh, '<label for="' . $fieldname . '" class="col-lg-3 control-label">' . ucwords(str_replace("_", " ", $fieldname)) . '</label>'.PHP_EOL);
                fwrite($fh, '<div class="col-lg-9">'.PHP_EOL.'<textarea class="form-control" cols="50" id="' . $key . '" name="' . $key . '">' . $value . '</textarea>'.PHP_EOL.'</div>'.PHP_EOL.'</div>'.PHP_EOL);
            } elseif ($input_type == "dropdown") {
                $this->getDropdownEditValues($key, $value, $fieldname, $fh);
            } else {
                if (!in_array($key, $hiddenFields)) {
                    $autocomplete_array = $this->requireAutocomplete($key);
//
                    if ($autocomplete_array["status"] == "TRUE") {
                        $table_name = $autocomplete_array["table"];
                        $this->writeEditAutoCompleteFields($key, $fieldname, $input_type, $table_name, $value, $fh);
                    } else {
                        $this->writeEditNormalFields($key, $fieldname, $input_type, $value, $fh);
                    }
                } else {
                    fwrite($fh, '<div class="hide form-group">'.PHP_EOL);
                    fwrite($fh, '<label for="' . $fieldname . '" class="col-lg-3 control-label">'.PHP_EOL);
                    fwrite($fh, ucwords(str_replace("_", " ", $fieldname)) . '</label>'.PHP_EOL);
                    fwrite($fh, '<div class="col-lg-9">'.PHP_EOL);
                    fwrite($fh, '<input class="form-control" type="hidden" id="' . $key);
                    fwrite($fh, '" name="' . $key);
                    fwrite($fh, '" value="' . $value);
                    fwrite($fh,'"/>');
                    fwrite($fh, '</div>'.PHP_EOL.'</div>'.PHP_EOL);
                }
            }
        }
    }

    function getDropdownEditValues($field, $id, $label, $fh) {
        $label_name = ucwords(substr(str_replace("_", " ", $label), 2));
        fwrite($fh,'<div class="form-group">'.PHP_EOL.'<label for="' . $field . '" class="col-lg-3 control-label">' . $label_name . '</label>'.PHP_EOL);
        fwrite($fh, '<div class="col-lg-9">'.PHP_EOL);
        fwrite($fh, '<select class="form-control" name="' . $field . '"  id="' . $field . '">'.PHP_EOL);
        $this->getSelectedValue($id, $label, $fh);
        fwrite($fh, '</select></div></div>'.PHP_EOL);
    }

    function getSelectedValue($id, $table, $fh) {
        $array = $this->loadData($table);
        if (count($array) > 1) {

            foreach ($array as $key => $value) {
                fwrite($fh, '<option value="' . $key . '"');
                if ($key == $id) {
                    fwrite($fh, ' selected= "selected"');
                }
                fwrite($fh, '>'.PHP_EOL . $value . "</option>".PHP_EOL);
            }
        } elseif (count($array) == 1) {
            foreach ($array as $key => $value) {
                fwrite($fh, "<option value=\"" . $key . "\">" . $value . "</option>".PHP_EOL);
            }
        }
    }

    function requireAutocomplete($field) {
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

    //This is for edit form
    function writeEditAutoCompleteFields($key, $fieldname, $input_type, $table_name, $value, $fh) {
        fwrite($fh, '<div class="form-group">'.PHP_EOL.'<label for="' . $fieldname . '" class="col-lg-3 control-label">'.PHP_EOL);
        fwrite($fh, ucwords(str_replace("_", " ", $fieldname)) . '</label>'.PHP_EOL.'<div class="col-lg-9">'.PHP_EOL);
        fwrite($fh, '<input class="form-control autocomplete ' . $key . '_label" data-table ="' . $table_name . '" type="');
        fwrite($fh, $input_type . '" id="' . $key . '_label" name="' . $key . '_label" value="' . $this->getDescription($value, $table_name) . '"/>'.PHP_EOL);
        fwrite($fh, '<input type="hidden" id="' . $key . '" name="' . $key . '" class="' . $key . '"/>'.PHP_EOL);
        fwrite($fh, '</div>'.PHP_EOL.'</div>'.PHP_EOL);
    }

    //This is for edit form
    function writeEditNormalFields($key, $fieldname, $input_type, $value, $fh) {
        fwrite($fh, '<div class="form-group"><label for="' . $fieldname . '" class="col-lg-3 control-label">');
        fwrite($fh, ucwords(str_replace("_", " ", $fieldname)) . '</label><div class="col-lg-9"><input class="form-control" type="');
        fwrite($fh, $input_type . '" id="' . $key . '" name="' . $key . '" value="' . $value . '"/></div></div>');
    }
    
    function getDescription($id, $table) {
        $array = $this->loadData($table);
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($key == $id) {
                    return $value;
                }
            }
        }
    }
    
    function loadData($table) {
        $database = new Database();
        $sql = "SELECT * FROM " . strtolower($table) . " ORDER BY id ASC";
        $result = $database->select($sql);

        foreach ($result as $value) {
            $array[$value['id']] = $value['txt_name'];
        }
        unset($database);
        return $array;
    }

}

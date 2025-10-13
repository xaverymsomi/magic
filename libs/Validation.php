<?php

/**
 * Description of Validation
 *
 * @author abdirahmanhassan
 */
namespace Libs;

class Validation {

    private array $clean_data;

    function __construct() {
        $this->clean_data = array();
    }

    /**
     * 
     * param type $request
     * param type $required
     * return boolean
     */
    function validateForm($request, $required) {

        foreach ($request as $req => $value) {

            if (in_array($req, $required)) { //required field
                $this->checkRequired($value, $req);
            } else { //not required
                $this->checkNotRequired($value, $req);
            }

            if ($req == 'email' || $req == 'txt_bcc') {
                $this->checkEmail($req, $value);
            }
        }
        return $this->clean_data;
    }

    function cleanData($input) {
        $clean_data = htmlspecialchars(stripslashes(trim($input)));
        return $clean_data;
    }

    function regex($value) {
        if (preg_match("/^[A-Za-zàâçéèêëîïôûùüÿñæœ0-9 &_!%',.]+$/", $value ?? '')) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function checkRequired($value, $req) {

        if (strlen(trim($value)) == 0) { //check if its null or empty
            return false;
        } else {
            if ($req != "email" && $req != "txt_bcc") {
                if (stripos($req, "dat_") !== FALSE || stripos($req, "tar_") !== FALSE || stripos($req, "dbl_") !== FALSE || $req == "txt_row_value" || $req == "txt_secondary_colour" || stripos($req, "tim_") !== FALSE ) {
                    $this->clean_data[$req] = $value;
                } else {
                    //if ($this->regex($value)) {
                        $this->clean_data[$req] = $this->cleanData($value);
                    //}
                }
            }
        }
    }

    function checkNotRequired($value, $req) {

        if ($req != "email" && $req != 'txt_bcc') {
            if (stripos($req, "dat_") !== FALSE || stripos($req, "tar_") !== FALSE || stripos($req, "dbl_") !== FALSE || $req == "txt_row_value" || stripos($req, "tim_") !== FALSE || $req == "txt_vehicle_pic" || $req == "txt_plate_pic" ) {
                $this->clean_data[$req] = $value;
            } else {
                if ($this->regex($value)) {
                    $this->clean_data[$req] = $this->cleanData($value);
                }
            }
        }
    }

    function checkEmail($field, $value) {
        if ($value != null && $value != "") {
            if (!$this->validateEmail($value)) {
                return false;
            } else {
                $this->clean_data[$field] = $value;
            }
        }
    }

    function validateEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public static function checkArray($array, $type) {
        if (gettype($array) == 'array') {
            if (count($array) > 0) {
                foreach ($array as $item) {
                    if (gettype($item) != $type) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    public static function validator($posted_data, $required_params) {
        $data = [];
        foreach ($required_params as $param => $rules) {
            $item = null;
            if (isset($rules['required']) && $rules['required']) {
                if (!isset($posted_data[$param])) {
                    return ['status' => false, 'message' => "$param is required."];
                }
            }
            if (isset($posted_data[$param])) {
                if (isset($rules['filter'])){
                    if (gettype($rules['filter']) == 'string') {
                        $validators = explode(':', $rules['filter']);
                        $validator = $validators[0];
                        $type = isset($validators[1]) ? $validators[1] : null;

                        switch ($validator) {
                            case 'array':
                                $item = self::checkArray($posted_data[$param], $type);
                                break;
                            case 'float':
                                $item = filter_var($posted_data[$param], FILTER_VALIDATE_FLOAT);
                                break;
                            case 'int':
                                $item = filter_var($posted_data[$param], FILTER_SANITIZE_NUMBER_INT);
                                break;
                            case 'string':
                                $item = filter_var($posted_data[$param], FILTER_SANITIZE_SPECIAL_CHARS);
                                break;
                        }
                        if (!$item){
                            return ['status' => false, 'message' => "$param has illegal data type."];
                        }
                    } else {
                        $item = filter_var($posted_data[$param], $rules['filter']);
                        if (!$item){
                            return ['status' => false, 'message' => "$param has illegal data type."];
                        }
                    }
                }
            }
            $data[$param] = $item;
        }

        return ['status' => true, 'data' => $data];
    }

}

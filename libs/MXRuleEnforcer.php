<?php

class MXRuleEnforcer
{
    public static function  getRuleValue($rule){
        $db = new Database();
        $date = date('Y-m-d');
        $records = $db->select("SELECT 
                mx_rule.id, mx_rule_configuration.id as config_id, mx_rule.txt_name, 
                mx_rule.txt_description, 
                mx_rule.txt_type, 
                mx_rule_configuration.txt_value, 
                mx_rule_configuration.dat_effective_start_date, 
                mx_rule_configuration.dat_effective_end_date, 
                mx_rule_configuration.txt_row_value 
            FROM mx_rule
            JOIN mx_rule_configuration ON mx_rule.id = mx_rule_configuration.int_mx_rule_id
            
            AND mx_rule.txt_name = :rule 
            AND dat_effective_start_date <= :start_date
            AND (dat_effective_end_date >= :end_date OR dat_effective_end_date IS NULL)
            ", [ ":rule" => $rule, ":start_date" => $date, ":end_date" => $date]);

        if (count($records) > 0) {
            return $records[0]['txt_value'];
        } else {
            return "";
        }
    }

    public static function  checkRule($mobile){
        if (strlen($mobile) == 12 && substr($mobile, 0, 3) == '255') {
            return $mobile;
        } else if (strlen($mobile) == 10 && substr($mobile, 0, 1) == '0') {
            return '255' . substr($mobile, 1);
        }  else if (strlen($mobile) == 9 && substr($mobile, 0, 1) != '0') {
            return '255' . $mobile;
        } else {
            return false;
        }
    }
}

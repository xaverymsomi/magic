<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FormInputFilter
 *
 * @author jmisa
 */
class FormInputFilter
{

    public static function getDismiss()
    {
        $filters = [
            "id" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_card_id" => FILTER_SANITIZE_SPECIAL_CHARS
        ];

        return $filters;
    }

    public static function getInspectorFormInputFilters()
    {
        $filters = [
            "zan_id" => FILTER_SANITIZE_SPECIAL_CHARS,
        ];

        return $filters;
    }

    public static function getSaveInspectorFilters()
    {
        return [
            "txt_name" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_id_number" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_marital_status" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_phone" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_image" => FILTER_SANITIZE_SPECIAL_CHARS,
//            "txt_district" => "Magharibi B",
//            "txt_address" => "MUEMBE MAJOGOO,MNARANI-MJI",
            "txt_gender" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_place_of_birth" => FILTER_SANITIZE_SPECIAL_CHARS,
            "dat_date_of_birth" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_occupation" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_inspector_type_id" => FILTER_SANITIZE_SPECIAL_CHARS,
            "email" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_area_id" => FILTER_SANITIZE_SPECIAL_CHARS
        ];
    }

    public static function getOwnerFormInputFilters()
    {
        $filters = [
            "zan_id" => FILTER_SANITIZE_SPECIAL_CHARS,
        ];

        return $filters;
    }

    public static function getSaveOwnerFilters()
    {
        return [
            "txt_name" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_id_number" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_marital_status" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_phone" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_image" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_gender" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_place_of_birth" => FILTER_SANITIZE_SPECIAL_CHARS,
            "dat_date_of_birth" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_occupation" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_inspector_type_id" => FILTER_SANITIZE_SPECIAL_CHARS,
            "email" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_area_id" => FILTER_SANITIZE_SPECIAL_CHARS
        ];
    }
}

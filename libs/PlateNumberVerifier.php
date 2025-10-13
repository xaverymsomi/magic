<?php

/**
 * Description of PlateNumberVerifier
 *
 * @author abdirahmanhassan
 */
class PlateNumberVerifier {

    //put your code here

    function verifySMZPlates($plate_no) {
        
    }

    function verifyGovernmentPlates($plate_no) {
        
    }

    function verifyOtherPlates($plate_no) {
        
    }

    function verifyZPlates($plate_no) {

        $length = strlen($plate_no);
        if ($length == 6 && substr($plate_no, 0, 1) == 7 || substr($plate_no, 0, 1) == 2 || substr($plate_no, 0, 1) == 4) { //Z123FF
            $z_plate = "Z" . substr($plate_no, 1);
            if ($length > 6) {
                return $this->checkDuplicatesLastCharacters($z_plate);
            } else {
                //echo "Captured (" . $plate_no . ")" . " Modified to " . $z_plate . '<br>';
                return $this->checkZPlatesMidDigits($z_plate);
            }
        } else if ($length < 6 && substr($plate_no, 0, 1) == 7 || substr($plate_no, 0, 1) == 2) { //Z123FF {
            $z_plate = "Z" . substr($plate_no, 1);
            return $z_plate . '<br>';
        } elseif ( str_starts_with($plate_no, "Z") && $length < 6) {
            return $plate_no . " LESS DIGITS : " . $length . "<br>"; //. " - "  . $this->checkZPlatesMidDigits($plate_no);
        } elseif ( str_starts_with($plate_no, "Z") && $length > 6) {
            return $this->checkDuplicatesLastCharacters($plate_no);
        } else {
            return $plate_no;
        }
    }

    function checkZPlatesMidDigits($z_plate) {
        if (substr($z_plate, 4, 1) == "0" && substr($z_plate, 4, 1) != null) {
            $replacement = "D";
            return substr_replace($z_plate, $replacement, 4, 1);
        } elseif (substr($z_plate, 4, 1) == "8" && substr($z_plate, 4, 1) != null) {
            $replacement = "B";
            return substr_replace($z_plate, $replacement, 4, 1);
        } elseif (substr($z_plate, 4, 1) == "7" && substr($z_plate, 4, 1) != null) {
            $replacement = "Z";
            return substr_replace($z_plate, $replacement, 4, 1);
        } elseif (substr($z_plate, 4, 1) == "6" && substr($z_plate, 4, 1) != null) {
            $replacement = "G";
            return substr_replace($z_plate, $replacement, 4, 1);
        } elseif (substr($z_plate, 5, 1) == "6" && substr($z_plate, 5, 1) != null) {
            $replacement = "G";
            return substr_replace($z_plate, $replacement, 5, 1);
        } elseif (substr($z_plate, 5, 1) == "7" && substr($z_plate, 5, 1) != null) {
            $replacement = "Z";
            return substr_replace($z_plate, $replacement, 5, 1);
        } elseif (substr($z_plate, 5, 1) == "8" && substr($z_plate, 5, 1) != null) {
            $replacement = "B";
            return substr_replace($z_plate, $replacement, 5, 1);
        } elseif (substr($z_plate, 5, 1) == "0" && substr($z_plate, 5, 1) != null) {
            $replacement = "D";
            return substr_replace($z_plate, $replacement, 5, 1);
        }
    }

    function checkDuplicatesLastCharacters($plate) {
        if (substr($plate, 5, 1) == substr($plate, 6, 1)) {
            return substr_replace($plate, "", -1);
        } elseif (substr($plate, 4, 1) == substr($plate, 5, 1)) {
            return substr($plate, 0, -2) . substr($plate, -1);
        } else {
            return $plate;
        }
    }

    function fetchVehicleInfo($reg) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => ZRB_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "reg=" . $reg,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ["content-type:application/x-www-form-urlencoded"]
        ]);

        $response = curl_exec($curl);
     
        $err = curl_error($curl);

        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return json_encode(['vehicle_info' => $response, 'error' => $err, 'source' => 'ZRB', 'reg' => $reg, 'status' => $http_status]);
    }
    function getVehicleInfo($reg) {
        $url = ZRB_URL . "/vehicle/" . $reg;
// echo $url;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET"
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $roadtax = [];//$this->getRoadLicense($reg);

        if(isset($roadtax['total_road_licenses'])){
            if($roadtax['total_road_licenses'] > 0){
                $road_license = $roadtax['road_licenses'][0]['road_license'];
                $road_issued = $roadtax['road_licenses'][0]['license_issue_date'];
                $road_expiry = $roadtax['road_licenses'][0]['license_expiry_date'];
                $road_status = $roadtax['road_licenses'][0]['status'];
                $road['road_license'] = $road_license;
                $road['road_issued']= $road_issued;
                $road['expiry'] = $road_expiry;
                $road['road_status'] = $road_status;
                
            }else{
                $road['road_license'] = 'NA';
                $road['road_issued']= 'NA';
                $road['expiry'] = 'NA';
                $road['road_status'] = 'NA';
            }
        }else{
            $road['road_license'] = 'NA';
            $road['road_issued']= 'NA';
            $road['expiry'] = 'NA';
            $road['road_status'] = 'NA';
        }
        
        return json_encode(['vehicle_info' => $response, 'road_tax'=> $road, 'error' => $err, 'source' => 'ZRB', 'reg' => $reg, 'status' => $http_status]);
    }
    
    function checkExemptionStatus($reg){
        $exemption_list = array('SM','SL','UT', 'DF', 'RC', 'NW', 'W', 'SU', 'PT', 'JK', 'ST','KV');
        
        if(in_array($reg, $exemption_list)){
            return 1;
        }else{
            return 2;
        }
    }

}

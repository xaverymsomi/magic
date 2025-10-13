<?php

namespace Libs;

use Exception;

class DualControl
{

    private $_db;
    private $_model;
    private $_action;
    private $_dual;
    private $_old_data;
    private $_new_data;
    private $_user;
    private $_council;
    private $_group;
    private $_no_old_data_actions;
    private $record_id;
    private $_modelObject;
    private $_non_dual_no_old_data_actions;

    function __construct($model, $action)
    {
        $this->_db = new Database();
        $this->_model = $model;
        $this->_action = $action;
        $this->_user = filter_var($_SESSION['id'] ?? null, FILTER_SANITIZE_SPECIAL_CHARS);
        $this->_non_dual_no_old_data_actions = ['generate_report', 'changeMiscellaneous', 'updateSmsSetup', 'post_vaccination', 'saveMenu', 'post_save_vaccine_card_transfer', 'post_dosage', 'post_dosage_two'];
//        $this->_council = filter_var($_SESSION['council'], FILTER_SANITIZE_NUMBER_INT);
        $this->setActionsWithoutOldData();
    }

    private function setActionsWithoutOldData(): void
    {
        $data = $this->_db->select("SELECT DISTINCT txt_action FROM mx_dual_activity WHERE txt_action = 'save' 
                                    OR txt_action LIKE 'post_create%' OR txt_action LIKE 'post_add%' 
                                    OR (txt_action LIKE 'post_reset%' AND txt_action <> 'post_reset_pin') 
                                    OR txt_action LIKE 'post_float%' OR txt_action LIKE 'post_regist%' OR txt_action LIKE 'post_subscribe%' OR txt_action LIKE 'post_vaccination%'
                                    OR txt_action LIKE 'post_manage_class%' OR txt_action LIKE 'post_collection%' OR txt_action LIKE 'post_save%'
                                    OR txt_action LIKE 'post_backup_database'
                                    OR txt_action LIKE 'process_manage%' 
                                    OR txt_action LIKE 'save%' OR txt_action LIKE 'generate_%' OR txt_action LIKE 'get_%' ");
        if (count($data) > 0) {
            foreach ($data as $value) {
                $this->_no_old_data_actions[] = $value['txt_action'];
            }
        } else {
            $this->_no_old_data_actions = [];
        }
    }

    public function getResult()
    {
        $this->checkDualActivity();
        return $this->_dual;
    }

    private function checkDualActivity()//: bool
    {
        $result = $this->_db->select("SELECT * FROM mx_dual_activity WHERE txt_model = :model AND txt_action = :action AND int_require_dual_activity = :dual", [':model' => $this->_model, ':action' => $this->_action, ':dual' => 1]);
        if (count($result) > 0) {
            $this->_dual = true;

            // check if bcx perform action
//            if ($this->_council == 0) {
//                // get model institution id
//
//                if (isset($_POST) && in_array($this->_model, ['Institution', 'Service', 'Account'])) {
//                    $this->_new_data = $this->generatePOSTSaveablePHPJson($_POST);
//                } else {
//                    $this->_new_data = $this->generateSaveablePHPJson(file_get_contents("php://input"));
//                }
//
//                if (in_array($this->_action, $this->_no_old_data_actions)) {
//                    $this->_old_data = "";
//                } else {
//                    $this->_old_data = json_encode($this->getOldData());
//                }
//
//                $old_data = json_decode($this->_old_data, true);
//                $new_data = json_decode($this->_new_data, true);
//
//                if (!empty($this->_old_data)) {
//                    if (array_key_exists('opt_mx_council_id', $old_data)) {
//                        $this->_council = $old_data['opt_mx_council_id'];
//                    } else {
//                        if (array_key_exists('opt_mx_council_id', $new_data)) {
//                            $this->_council = $new_data['opt_mx_council_id'];
//                        }
//                    }
//                } else {
//                    if (array_key_exists('opt_mx_council_id', $new_data)) {
//                        $this->_council = $new_data['opt_mx_council_id'];
//                    }
//                }
//            }

            $group = $this->_db->select("SELECT mx_group.* FROM mx_group 
                                        JOIN mx_dual_activity_group ON mx_group.id = mx_dual_activity_group.opt_mx_group_id 
                                        AND mx_dual_activity_group.opt_mx_dual_activity_id = :activity 
                                        ",
                [':activity' => $result[0]['id']]);

            if (count($group) > 0) {
                $this->_group = $group[0]['id'];
                $this->processDualControl($result[0]);
            } else {
                $this->_dual = false;
                $this->processNonDualControl($result[0]);
            }
        } else {
            $this->_dual = false;
            $this->processNonDualControl();
        }
    }

    private function generatePOSTSaveablePHPJson($dataObject)
    {
        $keys = array_keys($dataObject);
        if (($this->_model == "Subscriber" || $this->_model == "User") && $this->_action == "save") {
            $this->record_id = $this->getPreSaveId();
            return "{\"id\":" . $this->record_id . "," . substr($keys[0], 1);
        }

        if (strpos($keys[0], '{') !== FALSE && sizeof($keys) == 1) {
            return $keys[0];
        }

        if ($this->_action == 'post_vaccination') {
            if (isset($dataObject['symptoms'])) {
                $dataObject['symptoms'] = json_encode($dataObject['symptoms']);
            }
        }
        return json_encode($dataObject);
    }

    private function getPreSaveId()
    {
        if ($this->_model == "Subscriber") {
            $this->_db->beginTransaction();
            try {
                $result = $this->_db->select("SELECT txt_next_id FROM mx_next_id WHERE txt_table = 'Subscriber'");
                $subscriber_id = $result[0]['txt_next_id'];
                $next_id = $subscriber_id + 1;
                $stmt = $this->_db->prepare("UPDATE mx_next_id SET txt_next_id = '$next_id' WHERE txt_table = 'Subscriber'");
                $stmt->execute();
                $this->_db->commit();
                return $subscriber_id;
            } catch (Exception $ex) {
                $this->_db->rollBack();
                echo $ex->getMessage();
            }
        } elseif ($this->_model == "User") {
            return 'US' . $this->generateRandomString(6);
        }
    }

    private function generateRandomString($length = 8): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    private function generateSaveablePHPJson($dataObject)
    {
        // perform pre operation
        $returnedObject = $dataObject;
        if (($this->_model == "Subscriber" || $this->_model == "User") && $this->_action == "save") {
            $data = json_decode($dataObject);
            $this->record_id = $this->getPreSaveId();
            $data->{'id'} = $this->record_id;
            $returnedObject = json_encode($data);
        }
        return $returnedObject;
    }

    private function getOldData()
    {
        if ($this->_new_data != null) {
            $decoded_data = json_decode($this->_new_data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['status' => false, "message" => "Error decoding JSON data"];
            }

            if (isset($decoded_data['id'])) {
                $id = $decoded_data['id'];
            } else {
                return ['status' => false, "message" => "ID key is missing in the provided data"];
            }

            $data = [];
            $table = "mx_" . strtolower($this->_model);

            if ($this->_model == "DualActivity") {
                $table = "mx_dual_activity";
            } elseif ($this->_model == "Result") {
                $table = "mx_application_result";
            } elseif ($this->_model == "Channel") {
                $table = "mx_payment_channel";
            } elseif ($this->_model == "SampleInformation") {
                $table = "mx_sample_information";
            } elseif ($this->_model == "EmailContent") {
                $table = "mx_email_content";
            } elseif ($this->_model == "SmsTemplate") {
                $table = "mx_sms_template";
            } elseif ($this->_model == "BusinessLocation") {
                $table = "mx_business_location";
            } elseif ($this->_model == "Department") {
                $table = "mx_institution_department";
            } elseif ($this->_model == "WorkingHours") {
                $table = "mx_center_working_hours";
            } elseif ($this->_model == "Vaccination") {
                $table = "mx_vaccine_application";
            } elseif ($this->_model == "CovidRank") {
                $table = "mx_covid_rank";
            } elseif ($this->_model == "InsuranceType1") {
                $table = "mx_insurance_type";
            } elseif ($this->_model == "InsuranceType") {
                $table = "mx_insurance_type";
            } elseif ($this->_model == "EVisa") {
                $table = "mx_visa_application";
            } elseif ($this->_model == "TransportCompany") {
                $table = "mx_transport_company";
            } elseif ($this->_model == "InsurancePriceConfiguration") {
                $table = "mx_insurance_price";
            } elseif ($this->_model == "Insurance") {
                $table = "mx_insuarence_application";
            }  elseif ($this->_model == "CommunityType") {
                $table = "mx_community_type";
            } elseif ($this->_model == "InsuranceType") {
                $table = "mx_insurance_type";
            } elseif ($this->_model == "DiscountPolicy") {
                $table = "mx_policy_configuration";
            } elseif ($this->_model == "PolicyGroup") {
                $table = "mx_policy_group";
            } elseif ($this->_model == "Center" && ($this->_action == 'post_activate_center_test_type' || $this->_action == 'post_suspend_center_test_type')) {
                $table = "mx_center_test_type";
            } elseif ($this->_model == "Permission") {
                switch ($this->_action) {
                    case 'post_savePermission':
                    case 'post_userGroup':
                        $table = "mx_login_credential_group";
                        break;
                    case 'post_saveUserPermission':
                        $table = "mx_login_credential_permission";
                        break;
                    case 'post_saveSection':
                        $table = "mx_section";
                        break;
                    case 'post_saveGroup':
                        break;
                    case 'post_savegroupPermission':
                        $table = "mx_group_permission";
                        break;
                }

                $user_id = $this->_db->select("SELECT * FROM mx_login_credential WHERE user_id =:id", [':id' => $id]);
                if ($user_id) {
                    $id = $user_id[0]['id'];
                }
            } elseif ($this->_model == "Account" && $this->_action == "post_collection_settlement") {
                $table = "mx_transaction";
            }

            if (strlen($id) > 20) {
                $data = $this->_db->select("SELECT * FROM " . $table . " WHERE txt_row_value = :id", [':id' => $id]);
            } else {
                $data = $this->_db->select("SELECT * FROM " . $table . " WHERE id = :id", [':id' => $id]);
            }

            if (count($data) > 0) {
                $this->record_id = $data[0]['id'];
                return $data[0];
            } else {
                return $data;
            }
        }
        return [];
    }

    private function processDualControl($result)//: bool
    {
//        if ($this->_council != 0) {
//            if (isset($_POST) && in_array($this->_model, ['Institution', 'Service', 'Account'])) {
//                $this->_new_data = $this->generatePOSTSaveablePHPJson($_POST);
//            } else {
//                $this->_new_data = $this->generateSaveablePHPJson(file_get_contents("php://input"));
//            }
//            if (in_array($this->_action, $this->_no_old_data_actions)) {
//                $this->_old_data = "";
//            } else {
//                $this->_old_data = json_encode($this->getOldData());
//            }
//        }
        $this->saveData($result);
    }

    private function saveData($result = [])//: bool
    {
//        if ($this->_action == "save" && $this->_model == 'Subscriber') {
//            $phone_number = $this->getSubscriberPhoneNumber();
//            if ($this->isSubscriberExists($phone_number, $this->_council)) {
//                echo json_encode(600);
//                return false;
//            } else {
//                $notify = $this->_dual == false ? 4 : 1;
//                $date = date('Y-m-d H:i:s');
//                $guid = $this->getGUID();
//
//                $this->_old_data = !empty($this->_old_data) ? str_ireplace("'", "&apos;", $this->_old_data) : '';
//                $this->_new_data = !empty($this->_new_data) ? str_ireplace("'", "&apos;", $this->_new_data) : '';
//
//                $qry = "INSERT INTO mx_audit_trail (txt_old_record, txt_new_record, txt_table, txt_action, dat_created_date, txt_added_by, int_notify, opt_mx_audit_trail_status_id, txt_record_id, txt_row_value) "
//                    . "VALUES(:old, :new, :table, :action, :date, :user, :notify, :status, :record_id, :guid)";
//                $stmt = $this->_db->prepare($qry);
//                $data = [
//                    ':old' => $this->_old_data,
//                    ':new' => $this->_new_data,
//                    ':table' => $this->_model,
//                    ':action' => $this->_action,
//                    ':date' => $date,
//                    ':user' => $this->_user,
//                    ':notify' => $notify,
//                    ':status' => 2,
//                    ':record_id' => $this->record_id,
//                    ':guid' => $guid
//                ];
//                $stmt->execute($data);
//
//                $data[':session'] = $_SESSION;
//                Log::auditor('AUDIT_QUERY', $qry);
//                Log::auditor('AUDIT_DATA', $data);
//
//                echo 'sending email';
//                return $this->sendApprovalRequestEmail($result, $guid);
//            }
//        } else {
        $notify = $this->_dual == false ? 4 : 1;
        $date = date('Y-m-d H:i:s');
        $guid = $this->getGUID();

        $this->_old_data = !empty($this->_old_data) ? str_ireplace("'", "&apos;", $this->_old_data) : '';
        $this->_new_data = !empty($this->_new_data) ? str_ireplace("'", "&apos;", $this->_new_data) : '';

        $qry = "INSERT INTO mx_audit_trail (txt_old_record, txt_new_record, txt_table, txt_action, dat_created_date, txt_added_by, int_notify, opt_mx_audit_trail_status_id, txt_record_id, txt_row_value) "
            . "VALUES(:old, :new, :table, :action, :date, :user, :notify, :status, :record_id, :guid)";
        $stmt = $this->_db->prepare($qry);
        $data = [
            ':old' => $this->_old_data,
            ':new' => $this->_new_data,
            ':table' => $this->_model,
            ':action' => $this->_action,
            ':date' => $date,
            ':user' => $this->_user,
            ':notify' => $notify,
            ':status' => 2,
            ':record_id' => $this->record_id,
            ':guid' => $guid
        ];
        $stmt->execute($data);

        $data[':session'] = $_SESSION;
        Log::auditor('AUDIT_QUERY', $qry);
        Log::auditor('AUDIT_DATA', $data);

        $this->sendApprovalRequestEmail($result, $guid);
//        }
    }

    public function getGUID(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    public function sendApprovalRequestEmail($dual_activity_result, $guid)
    {
        if (empty($dual_activity_result)) {
            $this->_dual = false;
            Log::sysErr('No dual activity record @ ' . __METHOD__);
            return true;
        }

        // get users of group
        $result = $this->_db->select("SELECT *
            FROM mx_login_credential_group
            JOIN mx_login_credential
            ON mx_login_credential.id = mx_login_credential_group.opt_mx_login_credential_id
            AND opt_mx_login_credential_id = 163");

        Log::sysLog('No users found belonging to the specified group. Email not sent. -' .json_encode($result));
//        $result = $this->_db->select("SELECT * FROM mx_login_credential_group JOIN mx_login_credential ON mx_login_credential.id = mx_login_credential_group.opt_mx_login_credential_id WHERE opt_mx_group_id = :group", [':group' => $this->_group]);

        if ($result) {
            try {
                $token = base64_encode($guid);
                $duplicates = 0;

                $dc_data = [];

                $phpArray = json_decode(file_get_contents("php://input"), true);

                $dc_sql = 'SELECT ' . $dual_activity_result['txt_column'] . ' FROM ' . $dual_activity_result['txt_table'] . ' WHERE txt_row_value = :id';
                $dc_data = $this->_db->select($dc_sql, [':id' => $phpArray['id']]);
                if (!$dc_data) {
                    Log::sysErr(['message' => 'Failed to execute query', 'location' => __METHOD__, 'query' => $dc_sql, 'php_input' => $phpArray]);
                    return false;
                }

                $dual_activity_id = filter_var($dual_activity_result['id'], FILTER_SANITIZE_SPECIAL_CHARS);

                $mail = new MXMail();

                foreach ($result as $user) {
                    $dual_activity_data = [
                        'opt_mx_dual_activity_id' => $dual_activity_id,
                        'opt_mx_login_credential_id' => filter_var($user['opt_mx_login_credential_id'], FILTER_SANITIZE_SPECIAL_CHARS),
                        'txt_token' => $token,
                        'txt_column_value' => $dc_data[0][$dual_activity_result['txt_column']],
                        'dat_activity_triggered_date' => date('Y-m-d H:i:s'),
                        'int_activity_triggered_by' => filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS),
                        'txt_row_value' => $this->getGUID(),
                    ];

                    // Query to check if the combo already exists
                    $combo_check_query = "SELECT * FROM mx_dual_activity_log 
                      WHERE opt_mx_dual_activity_id = :dual_activity_id 
                      AND opt_mx_login_credential_id = :login_credential_id 
                      AND txt_column_value = :column_value";

                    $combo_check_result = $this->_db->select($combo_check_query, [
                        ':dual_activity_id' => $dual_activity_data['opt_mx_dual_activity_id'],
                        ':login_credential_id' => $dual_activity_data['opt_mx_login_credential_id'],
                        ':column_value' => $dual_activity_data['txt_column_value'],
                    ]);

                    // Check if the combination exists
                    if (count($combo_check_result) > 0) {
                        $duplicates++;
                        Log::sysLog('Skipping duplicate entry for Dual Activity Log: [' . json_encode($dual_activity_data) . ']');
                        continue;
                    }

                    // If not exists, INSERT INTO DUAL_ACTIVITY_LOG
                    $insert_qry = "INSERT INTO mx_dual_activity_log
                     (opt_mx_dual_activity_id, opt_mx_login_credential_id, txt_token, txt_column_value, dat_activity_triggered_date, int_activity_triggered_by, txt_row_value)
                     VALUES (:dual_activity_id, :login_credential_id, :token, :column_value, :triggered_date, :triggered_by, :row_value)";

                    $stmt = $this->_db->prepare($insert_qry);
                    $data = [
                        ':dual_activity_id' => $dual_activity_data['opt_mx_dual_activity_id'],
                        ':login_credential_id' => $dual_activity_data['opt_mx_login_credential_id'],
                        ':token' => $dual_activity_data['txt_token'],
                        ':column_value' => $dual_activity_data['txt_column_value'],
                        ':triggered_date' => $dual_activity_data['dat_activity_triggered_date'],
                        ':triggered_by' => $dual_activity_data['int_activity_triggered_by'],
                        ':row_value' => $dual_activity_data['txt_row_value'],
                    ];
                    $stmt->execute($data);

                    $user_data = $this->_db->select("SELECT * FROM mx_login_credential WHERE id = :id", [':id' => $user['opt_mx_login_credential_id']]);
                    $recipient = $user_data[0]['txt_username'];

//                    return $mail->sendEmail(13, $recipient, null, ['_url', '_token', '_section', '_action', '_council'], [URL, $token, $this->cleanData($this->_model), $this->cleanData($this->_action), $this->getInstitutionName()]);
                    return $mail->sendEmail(13, $recipient, null, ['_url', '_token', '_section', '_action', '_reference'], [URL, $token, $this->cleanData($this->_model), $this->cleanData($this->_action), $dual_activity_data['txt_column_value']]);
                }
                Log::sysLog('Total Duplicate Dual Entry Logs Skipped: ' . $duplicates);
            } catch (Exception $ex) {
                Log::sysErr(['message' => $ex->getMessage(), 'trace' => $ex->getTrace()]);
            }
        }
        Log::sysLog('No users found belonging to the specified group. Email not sent.');
    }

    private function cleanData($data)
    {
        if (substr($data, 0, 4) == 'post') {
            return ucwords(str_replace("_", " ", substr($data, 4)));
        } else {
            return ucwords(str_replace("_", " ", $data));
        }
    }

    private function processNonDualControl($result = [])
    {
        if (strpos($this->_action, 'post_') !== FALSE || strpos($this->_action, 'process_') !== FALSE ||
            strpos($this->_action, 'generate_') !== FALSE || strpos($this->_action, 'save') !== FALSE ||
            strpos($this->_action, 'save_') !== FALSE || in_array($this->_action, ['save', 'update']) || in_array($this->_action, $this->_non_dual_no_old_data_actions)) {
            if (isset($_POST) && in_array($this->_model, ['Institution', 'Service', 'Subscriber', 'Account', 'Council', 'Vaccination'])) {
                $this->_new_data = $this->generatePOSTSaveablePHPJson($_POST);
            } else {
                $this->_new_data = $this->generateSaveablePHPJson(file_get_contents("php://input"));
            }
            if (in_array($this->_action, $this->_no_old_data_actions) || in_array($this->_action, $this->_non_dual_no_old_data_actions)) {
                $this->_old_data = "";
            } else {
                $this->_old_data = json_encode($this->getOldData());
            }
            $this->saveData($result);
        }
    }

    private function getSubscriberPhoneNumber()
    {
        $json = json_decode($this->_new_data);
        if (isset($json->txt_mobile)) {
            return $json->txt_mobile;
        }
        return null;
    }

    private function isSubscriberExists($phone_number, $council): bool
    {
        $result = $this->_db->select("SELECT * FROM mx_merchant WHERE txt_mobile=:mobile AND opt_mx_council_id =:council", [':mobile' => $phone_number, ':council' => $council]);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    private function getInstitutionName()
    {
//        print_r('COUNCIL: ' . $this->_council);exit;
        try {
            if (!empty($this->_council)) {
                $result = $this->_db->select("SELECT txt_name FROM mx_council WHERE id = $this->_council");
                return $result[0]['txt_name'];
            } else {
                return '';
            }
        } catch (Exception $ex) {
            $this->_db->rollBack();
            echo $ex->getMessage();
        }
    }

}

<?php

namespace Modules\Core\Auth;

use Core\Controller;
use Core\Request;
use Exception;
use Libs\ApiLib;
use Libs\ApiLog;
use Libs\JWT;

class AuthController extends Controller
{
    protected $lib;
    protected $db;

    public function login()
    {
        $requestObj = new Request();
        $request = $requestObj->getBody();

        if (empty($request['username']) || empty($request['password'])) {
            ApiLib::handleResponse('Credentials not provided', [], 100);
        }

        $username = $request['username'];
        $password = $request['password'];

        $hashed_password = ApiLib::createHashedPassword(HASH_ALGO, $password, PASS_SALT);

        $result = $this->syslogin($username, $hashed_password);

        ApiLog::sysLog('LOGIN-CREDENTIALS: ' . json_encode($result));

        if (sizeof($result) <= 0) {
            ApiLib::handleResponse('Invalid Username/Password', $result, 100);
        }

        $login_type = null;

        switch ($result[0]['txt_domain']) {
            case 'mx_user':
                $login_type = 'user';
                break;
            case 'mx_institution':
                $login_type = 'institution';
                break;
            case 'mx_staff':
                $login_type = 'staff';
                break;
            case 'mx_inspector':
                $login_type = 'inspector';
                break;
            case 'mx_applicant':
                $login_type = 'applicant';
                break;
            case 'mx_agency':
                $login_type = 'agency';
                break;
            default:
                ApiLib::handleResponse('Invalid Domain', [], 100);
                break;
        }

        $response_data = [
            'login_credential_id' => $result[0]['txt_row_value'],
            'user_id' => $result[0]['user_id'],
            'username' => $result[0]['txt_username'],
            'domain' => $result[0]['txt_domain'],
            'login_type' => $login_type
        ];

        ApiLog::sysLog('RESPONSE-DATA: [' . json_encode($response_data) . ']');
        $this->completeLoginProcess($response_data);

        $response = ['user_details' => $response_data];
        ApiLib::handleResponse('You have successfully logged in', $response);
    }

    private function syslogin(string $username, string $password): array
    {
        $sql = "SELECT 
                    *,txt_row_value AS [row_id] FROM mx_login_credential 
                WHERE txt_username = :username AND password= :password 
                    AND opt_mx_status_id IN (1) 
                    AND txt_domain IN('mx_institution', 'mx_staff', 'mx_inspector', 'mx_applicant', 'mx_agency')";
        $result = $this->select($sql, array(':username' => $username, ':password' => $password));

        if ($result) {
            return $result;
        }
        return [];
    }

    public function completeLoginProcess(&$response_data)
    {
        //Get Data
        $sql = parent::select(
            "SELECT * FROM " . $response_data['domain'] . " WHERE id = :id",
            [':id' => $response_data['user_id']]
        );
        ApiLog::sysLog('LOGIN-DATA: [' . json_encode($sql) . ']');

        if (count($sql) <= 0) {
            ApiLib::handleResponse('Invalid Username/Password', [], 100, __METHOD__);
        }

        $result = $sql[0];

        $headers = array('alg' => 'RS512', 'typ' => 'JWT');
        $payload = [
            'username' => $response_data['domain'] == 'mx_applicant' ? $result['txt_first_name'] . ' ' . $result['txt_last_name'] : $result['txt_name'],
            'uid' => ApiLib::getGUID(),
            'user_id' => $result['txt_row_value'],
            'cred_id' => $response_data['login_credential_id'],
            'exp' => (time() + 3600000),
            'domain' => $response_data['domain']
        ];

        $response_data['user_id'] = $payload['user_id'];
        $response_data['username'] = $payload['username'];
        ApiLog::sysLog('JWT-PAYLOAD: [' . json_encode($payload) . ']');

        if ($response_data['domain'] == 'mx_staff') {
            $staff_sql = parent::select(
                "SELECT TOP 1
                        mx_institution.txt_row_value                         AS institution_id,
                        mx_institution.txt_name                              AS institution_name,
                        IIF(mx_institution.int_is_exempted = 1, 'YES', 'NO') AS is_exempted,
                        UPPER(mx_authority.txt_name)                         AS authority,
                        mx_institution_status.txt_name                       AS institution_status
                    FROM mx_login_credential
                        JOIN mx_staff ON mx_login_credential.user_id = mx_staff.id AND mx_login_credential.txt_domain = 'mx_staff'
                        JOIN mx_staff_institution ON mx_staff.id = mx_staff_institution.opt_mx_staff_id
                        JOIN mx_institution ON mx_institution.id = mx_staff_institution.opt_mx_institution_id
                        JOIN mx_institution_status ON mx_institution_status.id = mx_institution.opt_mx_institution_status_id
                        JOIN mx_authority ON mx_authority.id = mx_institution.opt_mx_authority_id
                    WHERE mx_login_credential.txt_row_value = :id",
                [':id' => $response_data['login_credential_id']]
            );

            if (count($staff_sql) <= 0) {
                ApiLog::sysLog('[RED-FLAG - UNASSIGNED-STAFF]: [' . json_encode($response_data) . ']');
                ApiLib::handleResponse('You are not assigned to any institution. Contact your Administrator for more info.', [], 100, __METHOD__);
            }

            $response_data['institution_id'] = $staff_sql[0]['institution_id'];
            $response_data['institution_name'] = $staff_sql[0]['institution_name'];
            $response_data['is_exempted'] = $staff_sql[0]['is_exempted'];
            $response_data['authority'] = $staff_sql[0]['authority'];
            $response_data['institution_status'] = $staff_sql[0]['institution_status'];
        }

        if ($response_data['domain'] == 'mx_institution') {
            // check if institution status is Approved
            $institution_sql = parent::select(
                "SELECT TOP 1
                        mx_institution.txt_row_value                         AS institution_id,
                        mx_institution.txt_name                              AS institution_name,
                        IIF(mx_institution.int_is_exempted = 1, 'YES', 'NO') AS is_exempted,
                        UPPER(mx_authority.txt_name)                         AS authority,
                        mx_institution.opt_mx_institution_status_id          AS institution_status_id,
                        mx_institution_status.txt_name                       AS institution_status
                    FROM mx_login_credential
                        JOIN mx_institution ON mx_institution.id = mx_login_credential.user_id AND mx_login_credential.txt_domain = 'mx_institution'
                        JOIN mx_institution_status ON mx_institution_status.id = mx_institution.opt_mx_institution_status_id
                        JOIN mx_authority ON mx_authority.id = mx_institution.opt_mx_authority_id
                    WHERE mx_login_credential.txt_row_value = :id",
                [':id' => $response_data['login_credential_id']]
            );

            if (count($institution_sql) <= 0) {
                ApiLog::sysLog('[RED-FLAG - UNASSIGNED-STAFF]: [' . json_encode($response_data) . ']');
                ApiLib::handleResponse('You are not assigned to any institution. Contact your Administrator for more info.', [], 100, __METHOD__);
            }

            $response_data['institution_id'] = $institution_sql[0]['institution_id'];
            $response_data['institution_name'] = $institution_sql[0]['institution_name'];
            $response_data['is_exempted'] = $institution_sql[0]['is_exempted'];
            $response_data['authority'] = $institution_sql[0]['authority'];
            $response_data['institution_status'] = $institution_sql[0]['institution_status'];

            $institution_status_id = $institution_sql[0]['institution_status_id'];

            if ($institution_status_id == REJECTED_INSTITUTION) {
                ApiLog::sysLog('[REJECTED-INSTITUTION]: [' . json_encode($response_data) . ']');
//                ApiLib::handleResponse('You cannot login because your registration has been rejected. Contact your Administrator for more info.', [], 100, __METHOD__);
            } elseif ($institution_status_id == APPROVED_INSTITUTION) {
                ApiLog::sysLog('[APPROVED-INSTITUTION]: [' . json_encode($response_data) . ']');
            } else {
                ApiLog::sysLog('[PENDING-INSTITUTION]: [' . json_encode($response_data) . ']');
//                ApiLib::handleResponse('You cannot login because your registration is pending approval. Contact your Administrator for more info.', [], 100, __METHOD__);
            }
        }

        $response_data['token'] = JWT::generate_jwt($headers, $payload);
    

        if ($response_data['domain'] == 'mx_agency') {
                // check if agency status is Approved
                $agency_sql = parent::select(
                    "SELECT TOP 1
                            mx_agency.txt_row_value                  AS agency_id,
                            mx_agency.txt_name                       AS agency_name,
                            mx_agency.opt_mx_agency_status_id        AS agency_status_id,
                            mx_agency_status.txt_name                AS agency_status
                        FROM mx_login_credential
                            JOIN mx_agency ON mx_agency.id = mx_login_credential.user_id AND mx_login_credential.txt_domain = 'mx_agency'
                            JOIN mx_agency_status ON mx_agency_status.id = mx_agency.opt_mx_agency_status_id
                        WHERE mx_login_credential.txt_row_value = :id",
                    [':id' => $response_data['login_credential_id']]
                );

                $response_data['agency_id'] = $agency_sql[0]['agency_id'];
                $response_data['agency_name'] = $agency_sql[0]['agency_name'];
                $response_data['agency_status'] = $agency_sql[0]['agency_status'];

                $agency_status_id = $agency_sql[0]['agency_status_id'];

                if ($agency_status_id == INACTIVE_AGENCY) {
                    ApiLog::sysLog('[INACTIVE-AGENCY]: [' . json_encode($response_data) . ']');
//                    ApiLib::handleResponse('You cannot login because your registration has been rejected. Contact your Administrator for more info.', [], 100, __METHOD__);
                } elseif ($agency_status_id == ACTIVE_AGENCY) {
                    ApiLog::sysLog('[ACTIVE-AGENCY]: [' . json_encode($response_data) . ']');
                } else {
                    ApiLog::sysLog('[PENDING-AGENCY]: [' . json_encode($response_data) . ']');
//                    ApiLib::handleResponse('You cannot login because your registration is pending approval. Contact your Administrator for more info.', [], 100, __METHOD__);
                }
            }

            $response_data['token'] = JWT::generate_jwt($headers, $payload);
        }

    public function getLoginId($cred_id)
    {
        $login_credential = $this->select("SELECT * FROM mx_login_credential WHERE txt_row_value=:id", [':id' => filter_var($cred_id, FILTER_SANITIZE_SPECIAL_CHARS)]);
        return $login_credential[0]['id'];
    }

    public function changePassword()
    {
        $this->pdo->beginTransaction();

        $bearer = JWT::get_bearer_token();
        if ($bearer == null) {
            ApiLib::handleResponse('Forbidden! Unauthorized access!', [], 403);
        }

        $user_details = JWT::get_token_data($bearer);
        if ($user_details == null) {
            ApiLib::handleResponse('Forbidden! Unauthorized access!', [], 403);
        }

        $data = Request::getBody();
        ApiLog::sysLog('DATA: ' . json_encode($data));

        $required_params = [
            'user_id' => ['required' => false, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'password' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'new_password' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
            'retype_password' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
        ];

        $validator = ApiLib::validator($data, $required_params);
        ApiLog::sysLog('VALIDATOR: ' . json_encode($validator));
        if (!$validator['status']) {
            $this->pdo->rollBack();
            ApiLib::handleResponse($validator['message'], [], 100, __METHOD__);
        }

        //Prepare user data and posted data
        $posted_data = $validator['data'];

        $_old_password = ApiLib::createHashedPassword(HASH_ALGO, $data['password'], PASS_SALT);
        $_new_password = $data['new_password'];
        $_confirm_password = $data['retype_password'];

        $mobile_column_name = null;
        $email_column_name = null;
        $sql = '';

        if (strtolower($user_details->domain) == 'mx_institution') {
            $mobile_column_name = 'txt_contact_phone';
            $email_column_name = 'txt_contact_email';
            $sql = 'SELECT TOP 1 mx_institution.*
                    FROM mx_login_credential
                    JOIN mx_institution ON mx_institution.id = mx_login_credential.user_id 
                        AND mx_login_credential.txt_domain = :domain
                    WHERE mx_login_credential.txt_row_value = :login_credential_id AND password = :old_password';
        } elseif (strtolower($user_details->domain) == 'mx_staff') {
            $mobile_column_name = 'txt_mobile';
            $email_column_name = 'txt_email';
            $sql = 'SELECT TOP 1 mx_staff.*
                    FROM mx_login_credential
                    JOIN mx_staff ON mx_staff.id = mx_login_credential.user_id 
                        AND mx_login_credential.txt_domain = :domain
                    WHERE mx_login_credential.txt_row_value = :login_credential_id AND password = :old_password';
        } elseif (strtolower($user_details->domain) == 'mx_agency') {
            $mobile_column_name = 'txt_mobile';
            $email_column_name = 'txt_email';
            $sql = 'SELECT TOP 1 mx_agency.*
                    FROM mx_login_credential
                    JOIN mx_agency ON mx_agency.id = mx_login_credential.user_id 
                        AND mx_login_credential.txt_domain = :domain
                    WHERE mx_login_credential.txt_row_value = :login_credential_id AND password = :old_password';
        } elseif (strtolower($user_details->domain) == 'mx_inspector') {
            $mobile_column_name = 'txt_mobile';
            $sql = 'SELECT TOP 1 mx_inspector.*
                    FROM mx_login_credential
                    JOIN mx_inspector ON mx_inspector.id = mx_login_credential.user_id 
                        AND mx_login_credential.txt_domain = :domain
                    WHERE mx_login_credential.txt_row_value = :login_credential_id AND password = :old_password';
        }

        //Get user details
        $user_info = parent::select(
            $sql,
            [
                ':domain' => strtolower($user_details->domain),
                ':login_credential_id' => filter_var($user_details->cred_id, FILTER_SANITIZE_SPECIAL_CHARS),
                ':old_password' => filter_var($_old_password, FILTER_SANITIZE_SPECIAL_CHARS)
            ]
        );

        ApiLog::sysLog('USER-INFO: ' . json_encode($user_info));
        if (count($user_info) <= 0) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('Old password is incorrect', [], 100, __METHOD__);
        }

        if ($_new_password != $_confirm_password) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('New password and confirm password didn\'t match', [], 100, __METHOD__);
        }

        $sql = "UPDATE mx_login_credential 
                    SET mx_login_credential.password=:pwd
                    FROM mx_login_credential
                WHERE  user_id = :user_id";

        ApiLog::sysLog('CHANGE-PASSWORD-SQL: ' . json_encode($sql));
        $stmt = parent::prepare($sql);
        $params = [
            ':pwd' => ApiLib::createHashedPassword(HASH_ALGO, $_new_password, PASS_SALT),
            ':user_id' => $user_info[0]['id'],
        ];

        ApiLog::sysLog('CHANGE-PASSWORD-UPDATES: ' . json_encode($params));
        $result = $stmt->execute($params);
        if (!$result) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('An error occurred. Failed to change password.', [], 100, __METHOD__);
        }

        $this->pdo->commit();

        $labels = ['_name', '_password'];
        $values = [$user_info[0]['txt_name'], $_new_password];

        $notification_data = [
            'source_id' => 2,
            'labels' => $labels,
            'values' => $values,
            'mobile' => !empty($mobile_column_name) ? $user_info[0][$mobile_column_name] : null,
            'email' => !empty($email_column_name) ? $user_info[0][$email_column_name] : null,
        ];


        // save notification data
        $save_notification = $this->saveNotificationData($notification_data);
        if (!$save_notification) {
            ApiLib::handleResponse('Password changed successfully but failed to send notification', [], 200, __METHOD__);
        }
        ApiLib::handleResponse('Password changed successfully. Please login with your new password.');
    }

    public function resetPassword()
    {
        $this->pdo->beginTransaction();
        $data = Request::getBody();
        ApiLog::sysLog('DATA: ' . json_encode($data));

        $required_params = [
            'username' => ['required' => true, 'filter' => FILTER_SANITIZE_SPECIAL_CHARS],
        ];

        $validator = ApiLib::validator($data, $required_params);
        ApiLog::sysLog('VALIDATOR: ' . json_encode($validator));

        if (!$validator['status']) {
            $this->pdo->rollBack();
            ApiLib::handleResponse($validator['message'], [], 100, __METHOD__);
        }

        $posted_data = $validator['data'];

        $user_data = parent::select(
            "SELECT * FROM mx_login_credential 
                        WHERE txt_username = :username AND 
                    txt_domain IN('mx_institution','mx_staff','mx_inspector','mx_agency')",
            [':username' => $posted_data['username']]
        );

        if (!$user_data) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('No record found', [], 100, __METHOD__);
        }

        $user_details = $this->select(
            "SELECT * FROM " . $user_data[0]['txt_domain'] . " WHERE id= :id",
            [':id' => $user_data[0]['user_id']]
        );

        ApiLog::sysLog('USER-DATA: ' . json_encode($user_data));

        if (count($user_data) <= 0) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('Invalid Username', [], 100, __METHOD__);
        }

        if ($user_data[0]['opt_mx_status_id'] != 1) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('Failed to reset password. User is not active.', [], 100, __METHOD__);
        }

        $new_password = ApiLib::generateRandomNo(4);

        // Update User Password
        $params = [
            ':pwd' => ApiLib::createHashedPassword(HASH_ALGO, $new_password, PASS_SALT),
            ':id' => filter_var($user_data[0]['user_id'], FILTER_SANITIZE_NUMBER_INT)
        ];

        $stmt = $this->prepare('UPDATE mx_login_credential SET password=:pwd WHERE user_id=:id');

        $result = $stmt->execute($params);
        ApiLog::sysLog('USER-PASSWORD-UPDATE: ' . $result);

        if (!$result) {
            $this->pdo->rollBack();
            ApiLib::handleResponse('Failed to reset password', [], 100, __METHOD__);
        }

        $mobile_column_name = null;
        $email_column_name = null;

        if (strtolower($user_data[0]['txt_domain']) == 'mx_institution') {
            $mobile_column_name = 'txt_contact_phone';
            $email_column_name = 'txt_contact_email';
        } elseif (strtolower($user_data[0]['txt_domain']) == 'mx_staff') {
            $mobile_column_name = 'txt_mobile';
            $email_column_name = 'txt_email';
        } elseif (strtolower($user_data[0]['txt_domain']) == 'mx_inspector') {
            $mobile_column_name = 'txt_mobile';
        }

        $this->pdo->commit();

        $labels = ['_name', '_password'];
        $values = [$user_details[0]['txt_name'], $new_password];

        $notification_data = [
            'source_id' => 2,
            'labels' => $labels,
            'values' => $values,
            'mobile' => !empty($mobile_column_name) ? $user_details[0][$mobile_column_name] : null,
            'email' => !empty($email_column_name) ? $user_details[0][$email_column_name] : null,
        ];

        // save notification data
        $save_notification = $this->saveNotificationData($notification_data);
        if (!$save_notification) {
            ApiLib::handleResponse('Password reset successfully but failed to send notification', [], 200, __METHOD__);
        }

        ApiLib::handleResponse('Password reset successfully', [], 200, __METHOD__);
    }
}
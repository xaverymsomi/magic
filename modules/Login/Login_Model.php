<?php

namespace Modules\Login;

use Libs\CaptchaLib;
use Libs\Database;
use Libs\Hash;
use Libs\Log;
use Libs\Model;
use Libs\MXMailGun;
use Libs\MXSms;
use Libs\SmsSender;
use Libs\Session;

class Login_Model extends Model
{
    private string $title = "Dashboard";

    function getTitle() : string
    {
        return $this->title;
    }

    public function initiateLogin($email, $password, $return_url, $captcha_string) : void
    {
        $redirect_loc = URL . '/' . $return_url;
        //check for captcha
        $captcha = new CaptchaLib();
        $captcha_response = $captcha->testCapture($captcha_string);
        unset($captcha);

        if ($captcha_response['status'] == 200) {
            $hashed_password = Hash::create(HASH_ALGO, $password, PASS_SALT);
            $result = $this->sysLogin('mx_login_credential', $email, $hashed_password);

	        if (isset($result['error'])) {
		        // Block login if already active elsewhere
		        Session::init();
		        Session::set('returned', $result['error']); // optional custom code
		        Session::set('error_message', $result['error']);
		        header('location: ' . $redirect_loc);
		        exit;
	        }
            if (sizeof($result) > 0) {
				if(empty($result[1]['dat_date_last_reset'])){
					$date = $result[1]['dat_added_date'];
				}else{
					$date = $result[1]['dat_date_last_reset'];
				}
                $validity = $this->isValidPassword($date);
                // login
                Session::init();
                $cookie_name = "theme";
                $cookie_value = $result[2]['txt_name'];
                setcookie($cookie_name, $cookie_value, time() + 31556926, "/"); // 31556926 = 1 year

                $cookie_primary = "primary";
                $cookie_secondary = "secondary";

                $schemes = array();

                setcookie($cookie_secondary, $result[2]['txt_secondary_colour'], time() + 31556926, "/");
                setcookie($cookie_primary, $result[2]['txt_primary_colour'], time() + 31556926, "/");
                setcookie("scheme", serialize($schemes), time() + 31556926, "/");

                Session::set('rp_signed_in', true);
                Session::set('username', $result[1]['txt_name']);
                Session::set('user_id', $result[1]['user_id']);
                Session::set('id', $result[1]['id']);
                Session::set('domain', $result[1]['txt_domain']);
                Session::set('role', $result[0]['opt_mx_group_id']);
                Session::set('validity', $validity);
                $_SESSION['LAST_ACTIVITY'] = time();
                Session::set('login_type', 'user');

                $state_update = $this->updateUserState($result[1]['id'], $result[1]['int_token']);
                if ($state_update) {
                    Log::sysLog('Login Successful');
                } else {
                    Session::init();
                    Session::set('returned', 10); //login failed
                }
            } else {
                Session::init();
                Session::set('returned', 10); //login failed
            }
	        header('location: ' . $redirect_loc);
        } else {
            Session::init();
            Session::set('returned', 1993); //login failed
            header('location: ' . URL);
        }
    }

    function sysLogin($table, $email, $password)
    {
        $user_id = "";
        $sql = "SELECT * FROM " . $table . " WHERE txt_username = :email AND password= :password AND opt_mx_status_id=1";
        $result = $this->db->select($sql, array(':email' => $email, ':password' => $password));
        $results = [];
        if (sizeof($result) > 0) {
	        // Check if already logged in
	        if ($result[0]['int_active'] == 1) {
		        return ['error' => 'User is already logged in on another device.'];
	        }

            $name = $this->db->select(" SELECT * FROM " . $result[0]['txt_domain'] . " WHERE id =:id ", [':id' => $result[0]['user_id']]);
            if ($name) {
                foreach ($name[0] as $key => $value) {
                    if ($key !== 'id') {
                        $result[0][$key] = $value;
                    }
                }
                $sql = "SELECT * FROM mx_login_credential_group WHERE opt_mx_login_credential_id= :id";
                $user_id = $result[0]['user_id'];
                $group = $this->db->select($sql, array(':id' => $result[0]['id']));

                if (sizeof($group) > 0) {
					$authority = [];
                    $theme = [['txt_name' => 'bcx', 'txt_primary_colour' => '000000', 'txt_secondary_colour' => 'ff0000']];

                    if (!empty($authority) && !empty($authority[0])) {
                        // call authority data
                        $authority_sql = "SELECT * FROM mx_authority WHERE id = :authority_id";
                        $authority_data = $this->db->select($authority_sql, array(':authority_id' => $authority_sql[0]['opt_mx_authority_id']));

                        // extract colors
                        $primary_color = $authority_data[0]['txt_color'] ?? '000000';
                        $secondary_color = $authority_data[0]['txt_portal_color'] ?? 'ff0000';

                        $theme = [['txt_name' => 'bcx', 'txt_primary_colour' => $primary_color, 'txt_secondary_colour' => $secondary_color]];
                        $results = [$group[0], $result[0], $theme[0], $authority[0]];
                    } else {
                        $results = [$group[0], $result[0], $theme[0], []];
                    }
                }
                return $results;
            } else {
                return $result;
            }
        } else {
            return [];
        }
    }

    private function isValidPassword($last_reset) : bool
    {
	    $today = date('Y-m-d'); //Y-m-d H:i:s
        $date1 = date_create($today);
        $date2 = date_create($last_reset);
        $diff = date_diff($date1, $date2);

        $policy = $this->getCouncilPasswordPolicy(1);

        if ($diff->days >= $policy) {
            return false;
        } else {
            return true;
        }
    }

    function getCouncilPasswordPolicy($rule)
    {
        $db = new Database();
        $configuration = $db->select("SELECT txt_value FROM mx_rule_configuration WHERE int_mx_rule_id = :rule", [':rule' => $rule]);
        if ($configuration) {
            if (count($configuration) > 0) {
                return $configuration[0]['txt_value'];
            }
        }
        return 30;
    }

	private function updateUserState($user_id, $token) : bool
	{
		$db = new Database();

		$query = "UPDATE mx_login_credential 
              SET int_active = :active, int_token = :token 
              WHERE id = :id";

		$stmt = $db->prepare($query);
		return $stmt->execute([
			':active' => ACTIVE,
			':token' => is_numeric($token) ? $token : null,
			':id'     => $user_id
		]);
	}

	public function recover($email) : void
    {
        // Check if the email exists and proceed with recovery
        $db = new Database();
        $sql = "SELECT * FROM mx_user WHERE email=:email";
        $data = $db->select($sql, array(':email' => $email));
        if (sizeof($data)) {
            // Check if the user is active
            if ($data[0]['opt_mx_state_id'] == 1) {
                $user = $data[0];
                $link = URL . '/login/reset?udid=' . md5(1290 * 3 + $data[0]['int_token']);

                $otp = $this->generateRandomString(8);
                $hashed_password = Hash::create(HASH_ALGO, $otp, PASS_SALT);

                $sql = "UPDATE mx_login_credential SET password=? WHERE user_id=?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$hashed_password, $data[0]['id']]);

                $sms = new MXSms();
                $sms->sendTemplateSMS(2, $data[0]['txt_mobile'], $data[0]['id'], null, null, ['_link', '_name', '_password'], [$link, $user['txt_name'], $otp], 1);

                $mail = new MXMailGun();
                $mail->sendEmail(2, $email, null, ['_link', '_name', '_password'], [$link, $user['txt_name'], $otp]);
            } else {
                Session::set('returned', 6060); //User is not active
            }
        } else {
            Session::set('returned', 6061); //User does not exist
        }
        header('location: ' . URL);
    }

    public function reset($usr, $password, $password_match) : void
    {
        // Get the required user
        session_start();
        $db = new Database();
        $data = $db->select("SELECT * FROM mx_user WHERE  CONVERT(VARCHAR(32), (HashBytes('MD5', CONVERT (VARCHAR(32), int_token + 1290 * 3))),2) = '" . $usr . "'");
        Session::init();
        if (sizeof($data)) {
            // Check if the user is active
            if ($data[0]['opt_mx_status_id'] == 1) {
                if ($password == $password_match) {
                    $hashed_password = Hash::create(HASH_ALGO, $password, PASS_SALT);
                    $new_token = $this->generateRandomNo();
                    $stmt = $db->prepare("UPDATE mx_user SET dat_date_last_reset='" . date('Y-m-d H:i:s') . "', int_token ='" . $new_token . "' WHERE id = '" . $data[0]['id'] . "'");
                    $stmt->execute();
                    $sql = "UPDATE mx_login_credential SET password=:pwd WHERE user_id=:user_id";
                    $statement = $db->prepare($sql);
                    $statement->execute([':pwd' => $hashed_password, ':user_id' => $data[0]['id']]);
                    Session::set('returned', 6000); //Recovery Success
                } else {
                    Session::set('returned', 6063); //Recovery Success
                }
            } else {
                Session::set('returned', 6060); //User is not active
            }
        } else {
            Session::set('returned', 6061); //User does not exist
        }
        header('location: ' . URL);
    }
}

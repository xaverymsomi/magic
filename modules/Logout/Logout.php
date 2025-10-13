<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Modules\Logout;

use Libs\Auth;
use Libs\Database;
use Libs\Model;

/**
 * Description of Logout
 *
 * @author abdirahmanhassan
 */
class Logout extends Model {

    public function __construct() {
        Auth::checkLogin();
        if (!isset($_SESSION)) {

            session_start();
        }

        $logged = $_SESSION['rp_signed_in'];
        if ( $logged ) {
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]
                );
            }
			$dat_date_last_reset = date("Y-m-d H:i:s");
            session_destroy();
            $this->updateUserState($_SESSION['id'], $dat_date_last_reset);
            header('location: ' . URL);
            exit;
        }
        session_destroy();
    }

	private function updateUserState($user_id, $date) : void
	{
		$db = new Database();
		$new_token = $this->generateRandomNo();
		$query = "UPDATE mx_login_credential 
              SET int_active = 0 , 
                  dat_date_last_reset = '" . filter_var($date, FILTER_SANITIZE_SPECIAL_CHARS) . "', 
                  int_token = '" . filter_var($new_token, FILTER_SANITIZE_SPECIAL_CHARS) . "'
              WHERE id = '" . filter_var($user_id, FILTER_SANITIZE_SPECIAL_CHARS) . "'";

		$stmt = $db->prepare($query);
		$stmt->execute();
	}


	//put your code here
}

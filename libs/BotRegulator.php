<?php

/**
 * Description of BotRegulator
 *
 * @author Developer
 */

// MAKE SURE YOU HAVE THE BELLOW FILES IN YOUR CONFIG FILE
/*
    define('TRANSACTION_MAXIMUM', 3000000);
    define('TOPUP_MAXIMUM', 5000000);
    define('DAILY_ALLOWED_TRANSACTION', 3000000);
*/

class BotRegulator {
    private $amount;
    private $db;
    
    function __construct($amount, $db) {
        $this->amount = $amount;
        $this->db = $db != null ? $db : new Database();
    }

    // Checks if the amount provided is within the regulated BOT allowed amount per transaction
    // Retunrs TRUE if the amount is valid or FALSE if the amount not valid
    public function validateTransaction() {
        return $this->amount <= TRANSACTION_MAXIMUM;
    }
    
    // Checks if the amount provided is within the regulated BOT allowed amount per Customer Topup transaction
    // Retunrs TRUE if the amount is valid or FALSE if the amount not valid
    public function validateTopUp($account) {
        $response = 108;
        // Check account current balance
        if($this->amount > DAILY_ALLOWED_TRANSACTION) {
            $response = 106;
        } else {
            $response = $this->validateDailyTransaction($account);
        }
        
        return $response;
    }
    
    private function validateDailyTransaction($account) {
        // Check if there is another topup that were made today and act accordingly
        //$db = new Database();
        $sql = "SELECT SUM(dbl_amount) AS total FROM mx_transaction WHERE txt_destination_account = :account AND dat_transaction_date = :date";
        $params = [':account' => $account, ':date' => date('Y-m-d')];
        $result = $this->db->select($sql, $params);
        $amount = $result[0]['total'];
        if (($amount + $this->amount) > DAILY_ALLOWED_TRANSACTION) {
            return 106;
        } else {
            return $this->validateAccountBalance($account);
        }
    }
    
    private function validateAccountBalance($account) {
        //$db = new Database();
        $sql = "SELECT dbl_balance FROM mx_account WHERE int_account_number = :account";
        $params = [':account' => $account];
        $result = $this->db->select($sql, $params);
        
        if (count($result) > 0) {
            $balance = $result[0]["dbl_balance"];
            if (($balance + $this->amount) > TOPUP_MAXIMUM) { // balance will exceed allowed amount
                return 107;
            } else {
                return 200;
            }
        } else {
            return 102;
        }
    }
}

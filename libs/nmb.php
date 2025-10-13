<?php

/* * *****************************************************
 * Copyright (C) 2015 Business Connexion (T) 
 * 
 * This file is part of Uhuru Pay API.
 * 
 * This source can not be copied and/or distributed without the express
 * permission of the owner

 * ***************************************************** */

Class transaction extends CRUD {

    protected $msg;      //Request message Object
    protected $db;           //Database connection
    protected $db_bcx;
    protected $messageCode;
    protected $agent_id;
    protected $card_no;
    protected $service_name;
    protected $partner_id;
    protected $pin;
    protected $amount;
    protected $bcx_crypto;
    
    private $request_json;
    private $payloadInXml;
    private $card;
    private $card_uid;
    private $amount_deposited;
    private $service_id = '4';
    private $service = 'Top Up';
    private $request_id;
    private $terminal_id;
    private $source_ip;
    private $client_name;
    private $terminal_location;
    private $id;
    private $terminal_operator_id;
    private $timestamp;
    private $user_id;
    private $source_id;
            
            

    function __construct($db, $db_bcx) {
        $this->db = $db;
        $this->db_bcx = $db_bcx;             
        $this->bcx_crypto = new BcxErcisCrypto(); 
        $rawJSON = file_get_contents("php://input");
        $request_json = json_decode($rawJSON, true);
        $this->service_id = $request_json['Request']['Header']['ServiceID'];
        $this->terminal_id = $this->msg['Request']['Header']['TerminalID'];
        $this->source_ip = $this->msg['Request']['Header']['ClientNameOrIP'];
        $this->client_name = $this->msg['Request']['Header']['ClientNameOrIP'];
        $this->terminal_location = $this->msg['Request']['Header']['TerminalLocation'];
        $this->id = $this->msg['Request']['Header']['Id'];
        $this->terminal_operator_id = $this->msg['Request']['Header']['TerminalOperatorID'];
        $this->timestamp = $this->msg['Request']['Header']['TimeStamp'];
        $this->user_id = $this->msg['Request']['Header']['UserID'];
        $this->source_id = $this->msg['Request']['Header']['UserID'];
        
    }

    //public function processMessage($rawJSON){
    public function processMessage() {
        $response = null;

        switch ($this->service_id) {
            case 4:
                $response = $this->processTopUpRqst();
                break; //connected
            default:
                break;
        }
        //}
    }

    //Top up Request
    public function processTopUpRqst() {
        $ServiceID = $this->msg['Request']['Header']['ServiceID'];
        $TerminalID = $this->msg['Request']['Header']['TerminalID'];
        $sourceIP = $this->msg['Request']['Header']['ClientNameOrIP'];
        $ClientNameOrIP = $this->msg['Request']['Header']['ClientNameOrIP'];
        $TerminalLocation = $this->msg['Request']['Header']['TerminalLocation'];
        $Id = $this->msg['Request']['Header']['Id'];
        $TerminalOperatorID = $this->msg['Request']['Header']['TerminalOperatorID'];
        $TimeStamp = $this->msg['Request']['Header']['TimeStamp'];
        $UserID = $this->msg['Request']['Header']['UserID'];
        $sourceID = $this->msg['Request']['Header']['UserID'];


        $sourceTimestamp = $this->msg['Request']['Header']['TimeStamp'];


        $source_details = $this->get_api_source_details($sourceIP, $sourceID);


        if (!empty($source_details)) {
            $encrypted_payload = $this->msg['Request']['Body']['Payload'];
            $signed_payload = $this->msg['Request']['Body']['Signature'];

            $serverPrivate_key_path = $source_details[0] . $source_details[1];
            $clientPublic_key_path = $source_details[0] . $source_details[2];
            ;
            //verify by public key			 
            $bcx_priv_key = $this->ercisCryptor->get_key_from_file($serverPrivate_key_path, true, true, 'bcx');
            $client_pub_key = $this->ercisCryptor->get_key_from_file($clientPublic_key_path, false, false, null);

            if ($this->ercisCryptor->Verify($encrypted_payload, $signed_payload, $client_pub_key)) {
                $this->log_event('Success-Payload Request Verified', '');
                $this->log_event('encry payload ', $encrypted_payload);

                if ($this->ercisCryptor->crypto_parameter_decrytor($this->msg['Request']['Body']['CryptoInformation']['CryptoKey'], $bcx_priv_key, true)) {
                    $this->log_event('Key ', 'Key success Decr');
                    $cryptoKey = $this->ercisCryptor->crypto_parameter_decrytor($this->msg['Request']['Body']['CryptoInformation']['CryptoKey'], $bcx_priv_key, true);
                    $this->log_event('Decrypted Decoded Key ', $cryptoKey);
                }
                if ($this->ercisCryptor->crypto_parameter_decrytor($this->msg['Request']['Body']['CryptoInformation']['CryptoIv'], $bcx_priv_key, true)) {
                    $this->log_event('Iv ', 'Key success Decr');
                    $cryptoIv = $this->ercisCryptor->crypto_parameter_decrytor($this->msg['Request']['Body']['CryptoInformation']['CryptoIv'], $bcx_priv_key, true);
                    $this->log_event('Decrypted Decoded IV ', $cryptoIv);
                }

                if ($this->ercisCryptor->decrypt_payload($encrypted_payload, $cryptoKey, $cryptoIv)) {
                    $payload = $this->ercisCryptor->decrypt_payload($encrypted_payload, ($cryptoKey), ($cryptoIv));
                    $this->log_event('DecryPayload ', strlen($payload) . ' = ' . ($payload));


                    $payloadInXml = $this->json_decode_values($payload);
                    $card = $payloadInXml['Payload']['card_no'];
                    $card_uid = $this->getCardUID($card);
                    $amount_deposited = $payloadInXml['Payload']['amount'];
                    $transaction_id = $this->TransactionIDGenereted();

                    $service_id = '4';
                    $service = 'Top Up';
                    $request_id = $payloadInXml['Payload']['request_id'];

                    //Check Agent Type (If is from Bank or Normal Agent)
                    if ($source_details[4] == 3) {
                        $IP = $this->msg['Request']['Header']['ClientNameOrIP'];
                        $agent_id = $this->get_bankagent_id($IP);
                        $operator_id = $this->get_bankagent_id($IP);
                        $device_imei = $this->msg['Request']['Header']['ClientNameOrIP'];

                        //check if card in use
                        $card_uid_existance = $this->check_card_if_in_use($card_uid);
                        $valid_tag_id = $this->check_customer_card_uid($card_uid); //valid_tag_id	


                        if ($card_uid_existance != null) {

                            $customer_balance = $this->balance_returned($valid_tag_id);

                            if ($amount_deposited < 999) {
                                $status_code = '104';
                                $message = 'Deposit less amount';
                            } else if (($amount_deposited + $customer_balance) > 500000) {
                                $status_code = '104';
                                $message = 'Deposit Exceed Account Maximum Amount';
                                $response = array();
                                $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $status_code, $message);
                                $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
                                $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();

                                //step 2. Encrypt Payload using Key and Iv
                                $encryptedPayload = $this->ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
                                //3. Encrypt Key and IV using partiner public Key
                                $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
                                $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);

                                //4.Sign of encrypted payload using My Private Key
                                $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
                                //5. Construct Respone
                                $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $sourceTimestamp, $ServiceID);
                                $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
                                $status_code = $this->construct_responce_statuscode_Json($status_code);

                                $response = $this->construct_responce_in_Json($header, $body, $status_code);

                                $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
                                $this->ercisCryptor->freeKeyMemory($client_pub_key);
                                $this->log_event('Response ', $response);
                                echo $response;
                            } else {

                                $agent_id = $this->get_bankagent_id($this->msg['Request']['Header']['ClientNameOrIP']);
                                //activate account
                                $account = $this->get_customer_account_status($card_uid);
                                $status = $account['validity'];
                                $accountID = $account['id'];

                                if ($status == 2) {
                                    $this->update_customer_account_status($accountID);
                                    $this->recharge_amount_on_nfc_tag($amount_deposited, $valid_tag_id);
                                    $customer_balance = $this->balance_returned($valid_tag_id);
                                    $transaction_type = "1";
                                    $card_number = $this->getCustomerCardNumber($card_uid);
                                    $this->record_customer_transaction_per_agent($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $request_id, $service_id);
                                    $this->debit_amount_on_agent_account($amount_deposited, $agent_id);
                                    $this->record_partner_transaction_per_topup($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $agent_id, $service_id);
                                    $message = 'Top up Done Successfully';
                                    $status_code = '000';
                                    $response = array();
                                    $response = $this->serviceImplementedResponse($request_id, $service, $agent_id, $transaction_id, $amount_deposited, $customer_balance, $card_number, $message);
                                    //echo json_encode(["response" => [$response]]);	
                                    $payload = json_encode(["response" => [$response]]);
                                    //step 1. Generate Random Key and IV
                                    $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
                                    $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();
                                    $this->log_event('Key= ', $cryptoKey_);
                                    $this->log_event('IV= ', $cryptoIv_);
                                    //step 2. Encrypt Payload using Key and Iv
                                    $encryptedPayload = $ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
                                    //3. Encrypt Key and IV using partiner public Key
                                    $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
                                    $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);
                                    $this->log_event('Key1-Encr ', $encryptedCryptoKey);
                                    $this->log_event('IV-Encr', $encryptedCryptoIv);
                                    //step 2. Encrypt Payload using Key 
                                    //4.Sign of encrypted payload using My Private Key
                                    $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
                                    //5. Construct Respone
                                    $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $CurrentTimestamp, $ServiceID);
                                    $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
                                    $status_code = $this->construct_responce_statuscode_Json($status_code);
                                    $response = $this->construct_responce_in_Json($header, $body, $status_code);

                                    $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
                                    $this->ercisCryptor->freeKeyMemory($client_pub_key);
                                    echo $response;
                                } else {
                                    $this->recharge_amount_on_nfc_tag($amount_deposited, $valid_tag_id);
                                    $customer_balance = $this->balance_returned($valid_tag_id);
                                    $transaction_type = "1";
                                    $card_number = $this->getCustomerCardNumber($card_uid);
                                    $this->record_customer_transaction_per_agent($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $request_id, $service_id);
                                    $this->debit_amount_on_agent_account($amount_deposited, $agent_id);
                                    $this->record_partner_transaction_per_topup($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $agent_id, $service_id);
                                    $message = 'Top up Done Successfully';
                                    $status_code = '000';
                                    $account_no = 'NA';
                                    $response = array();
                                    $response = $this->getServiceResponse("PASS", "BILLPAY_RESPONSE", $status_code, $message, $account_no, $request_id, $service, $agent_id, $transaction_id, $amount_deposited, $customer_balance, $card_number, $message);
                                    //echo json_encode(["response" => [$response]]); 							    $payload=json_encode(["response" => [$response]]);	
                                    //step 1. Generate Random Key and IV
                                    $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
                                    $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();
                                    $this->log_event('Key1', $cryptoKey_);
                                    $this->log_event('IV', $cryptoIv_);

                                    //step 2. Encrypt Payload using Key and Iv
                                    $encryptedPayload = $this->ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
                                    //3. Encrypt Key and IV using partiner public Key
                                    $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
                                    //$this->log_event('encryptedCryptoKey ',$encryptedCryptoKey);
                                    //$this->log_event('client_pub_key ',$client_pub_key);


                                    $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);

                                    $this->log_event('Key1-Encr ', $encryptedCryptoKey);
                                    $this->log_event('IV-Encr', $encryptedCryptoIv);
                                    //$this->log_event('step 3 ','33333333333333333333333333333333333333333333333333');
                                    //4.Sign of encrypted payload using My Private Key
                                    $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
                                    //5. Construct Respone
                                    $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $TimeStamp, $ServiceID);
                                    $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
                                    $status_code = $this->construct_responce_statuscode_Json($status_code);

                                    $response = $this->construct_responce_in_Json($header, $body, $status_code);

                                    $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
                                    $this->ercisCryptor->freeKeyMemory($client_pub_key);
                                    $this->log_event('Response ', $response);
                                    echo $response;
                                }
                            }
                        } else {
                            $resp_code = '56';
                            $message = 'Invalid Card';
                            $response = array();
                            $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                            echo $response;
                        }
                    } else {
                        $pin = $payloadInXml['Payload']['pin'];
                        $operator_id = $payloadInXml['Payload']['operator_id'];
                        $device_imei = $payloadInXml['Payload']['device_imei'];
                        //$this->log_event('operator_id ',($operator_id));
                        $agent_id = $this->get_agent_id($operator_id);

                        $this->log_event('card ', ($card_uid));
                        $this->log_event('agent_id ', ($agent_id));

                        $card_uid_existance = $this->check_card_if_in_use($card_uid);

                        $valid_tag_id = $this->check_customer_card_uid($card_uid); //valid_tag_id


                        if ($card_uid_existance != null) {
                            $valid_pin = $this->check_enter_pin($operator_id, $pin);

                            if ((int) $amount_deposited < 999 && empty($amount_deposited)) {
                                $resp_code = '104';
                                $message = 'Deposit less amount';
                                $response = array();
                                $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                                echo $response;
                            } else if ($valid_pin == true) {
                                $agent_id = $this->get_agent_id($operator_id);
                                $balance = $this->get_agent_account_balance($agent_id);
                                $available_balance = $balance;

                                if ($available_balance >= $amount_deposited) {

                                    //activate account
                                    $account = $this->get_customer_account_status($card_uid);
                                    $status = $account['validity'];
                                    $accountID = $account['id'];


                                    if ($status == 2) {
                                        $this->update_customer_account_status($accountID);
                                        $this->recharge_amount_on_nfc_tag($amount_deposited, $valid_tag_id);
                                        $customer_balance = $this->balance_returned($valid_tag_id);
                                        $transaction_type = "1";
                                        $card_number = $this->getCustomerCardNumber($card_uid);
                                        $this->record_customer_transaction_per_agent($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $request_id, $service_id);
                                        $this->debit_amount_on_agent_account($amount_deposited, $agent_id);
                                        $this->record_partner_transaction_per_topup($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $agent_id, $service_id);
                                        $message = 'Top up Done Successfully';
                                        $status_code = '000';
                                        $response = array();
                                        $response = $this->serviceImplementedResponse($request_id, $service, $agent_id, $transaction_id, $amount_deposited, $customer_balance, $card_number, $message);
                                        //echo json_encode(["response" => [$response]]);	
                                        $payload = json_encode(["response" => [$response]]);
                                        //step 1. Generate Random Key and IV
                                        $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
                                        $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();

                                        //step 2. Encrypt Payload using Key and Iv
                                        $encryptedPayload = $ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
                                        //3. Encrypt Key and IV using partiner public Key
                                        $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
                                        $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);

                                        //4.Sign of encrypted payload using My Private Key
                                        $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
                                        //5. Construct Respone
                                        $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $CurrentTimestamp, $ServiceID);
                                        $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
                                        $status_code = $this->construct_responce_statuscode_Json($status_code);
                                        $response = $this->construct_responce_in_Json($header, $body, $status_code);

                                        $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
                                        $this->ercisCryptor->freeKeyMemory($client_pub_key);
                                        echo $response;
                                    } else {
                                        $this->recharge_amount_on_nfc_tag($amount_deposited, $valid_tag_id);
                                        $customer_balance = $this->balance_returned($valid_tag_id);
                                        $transaction_type = "1";
                                        $card_number = $this->getCustomerCardNumber($card_uid);
                                        $this->record_customer_transaction_per_agent($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $request_id, $service_id);
                                        $this->debit_amount_on_agent_account($amount_deposited, $agent_id);
                                        $this->record_partner_transaction_per_topup($amount_deposited, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $agent_id, $service_id);
                                        $message = 'Top up Done Successfully';
                                        $status_code = '000';
                                        $account_no = 'NA';
                                        $response = array();
                                        $response = $this->getServiceResponse("PASS", "BILLPAY_RESPONSE", $status_code, $message, $account_no, $request_id, $service, $agent_id, $transaction_id, $amount_deposited, $customer_balance, $card_number, $message);
                                        //echo json_encode(["response" => [$response]]); 							    $payload=json_encode(["response" => [$response]]);	
                                        //step 1. Generate Random Key and IV
                                        $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
                                        $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();
                                        //$this->log_event('cryptoKey_ ',$cryptoKey_);
                                        //$this->log_event('cryptoIv_ ',$cryptoIv_);
                                        //step 2. Encrypt Payload using Key and Iv
                                        $encryptedPayload = $this->ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
                                        //3. Encrypt Key and IV using partiner public Key
                                        $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
                                        //$this->log_event('encryptedCryptoKey ',$encryptedCryptoKey);
                                        //$this->log_event('client_pub_key ',$client_pub_key);


                                        $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);
                                        //$this->log_event('step 3 ','33333333333333333333333333333333333333333333333333');
                                        //4.Sign of encrypted payload using My Private Key
                                        $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
                                        //5. Construct Respone
                                        $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $TimeStamp, $ServiceID);
                                        $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
                                        $status_code = $this->construct_responce_statuscode_Json($status_code);

                                        $response = $this->construct_responce_in_Json($header, $body, $status_code);

                                        $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
                                        $this->ercisCryptor->freeKeyMemory($client_pub_key);
                                        $this->log_event('Response ', $response);
                                        echo $response;
                                    }
                                } else {
                                    $resp_code = '102';
                                    $message = 'Agent Account Insufficient Balance';
                                    $response = array();
                                    $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                                    //echo json_encode(["response" => [$response]]);
                                    $response = $this->serviceImplementedResponse($request_id, $service, $agent_id, $transaction_id, $amount_deposited, $customer_balance, $card_number, $message);
                                    //echo json_encode(["response" => [$response]]); 							    $payload=json_encode(["response" => [$response]]);	
                                    //step 1. Generate Random Key and IV
                                    $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
                                    $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();
                                    //$this->log_event('cryptoKey_ ',$cryptoKey_);
                                    //$this->log_event('cryptoIv_ ',$cryptoIv_);
                                    //step 2. Encrypt Payload using Key and Iv
                                    $encryptedPayload = $this->ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
                                    //3. Encrypt Key and IV using partiner public Key
                                    $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
                                    //$this->log_event('encryptedCryptoKey ',$encryptedCryptoKey);
                                    //$this->log_event('client_pub_key ',$client_pub_key);


                                    $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);
                                    //$this->log_event('step 3 ','33333333333333333333333333333333333333333333333333');
                                    //4.Sign of encrypted payload using My Private Key
                                    $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
                                    //5. Construct Respone
                                    $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $TimeStamp, $ServiceID);
                                    $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
                                    $status_code = $this->construct_responce_statuscode_Json($status_code);

                                    $response = $this->construct_responce_in_Json($header, $body, $status_code);

                                    $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
                                    $this->ercisCryptor->freeKeyMemory($client_pub_key);
                                    $this->log_event('Response ', $response);
                                }
                            } else {
                                $resp_code = '103';
                                $message = 'Operator Account Incorrect PIN';
                                $response = array();
                                $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                                echo $response;
                            }
                        } else {
                            $resp_code = '56';
                            $message = 'Invalid Card';
                            $response = array();
                            $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                            echo $response;
                        }
                    }
                } else {
                    $this->log_event('Error', 'Decryption Failure');
                    $request_id = 'NA';
                    $service = 'Top up';
                    $agent_id = 'NA';
                    $resp_code = '119';
                    $message = 'Decryption Failure';
                    $response = array();
                    $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                    echo $response;
                }
            } else {
                $this->log_event('Error', 'Verification Failure');
                $request_id = 'NA';
                $service = 'Top up';
                $agent_id = 'NA';
                $resp_code = '109';
                $message = 'Verification Failure';
                $response = array();
                $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
                echo $response;
            }
        } else {
            $this->log_event('Error', 'Wrong-User-ID');
            $request_id = 'NA';
            $service = 'Top up';
            $agent_id = 'NA';
            $resp_code = '110';
            $message = 'Error Wrong Source ID';
            $response = array();
            $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $resp_code, $message);
            echo $response;
        }
    }

    //Check if card is already in use
    public function check_card_if_in_use($card_uid) {
        $sql = 'SELECT B.nfc_tag_id FROM tbl_customer_account_details as A INNER JOIN valid_nfc_tags as B ON A.valid_tag_id=B.id WHERE B.nfc_tag_id=:nfc_tag_id';

        $parameters = array(
            'nfc_tag_id' => TRIM($card_uid)
        );

        $result = $this->fetch_query_result_bcx($sql, $parameters, false);

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    //Check balance
    public function balance_returned($valid_tag_id) {
        $sql = "SELECT amount FROM tbl_customer_account_details WHERE valid_tag_id=:valid_tag_id";

        $parameters = array(
            'valid_tag_id' => $valid_tag_id
        );

        $result = $this->fetch_query_result_bcx($sql, $parameters, false);

        if (empty($result)) {
            return null;
        }

        return $result['amount'];
    }

    //Get id with card uid from application that will be inserted on tbl_customer_account_details
    public function check_customer_card_uid($card_number) {
        $sql = "SELECT `id` FROM `valid_nfc_tags` WHERE `nfc_tag_id`=:nfc_tag_id";

        $parameters = array(
            'nfc_tag_id' => $card_number
        );

        $result = $this->fetch_query_result_bcx($sql, $parameters, false);

        if (empty($result)) {
            return null;
        }

        return $result[0];
    }

    //check operator pin 
    public function check_enter_pin($operator_id, $pin) {
        $password = hash('sha256', $pin);
        $sql = 'SELECT * FROM tbl_distr_munis_portal_agent_device_operators
		WHERE operator_id=:operator_id AND operator_password=:password AND validity=1';

        $parameters = array(
            'password' => $password,
            'operator_id' => $operator_id
        );

        $operator = $this->fetch_query_result($sql, $parameters, false);

        if (empty($operator)) {
            return false;
        }
        return true;
    }

    //get agent balance 
    public function get_agent_account_balance($agent_id) {
        $sql = 'SELECT amount FROM tbl_partner_accounts
		WHERE partner_id=:agent_id AND validity=1';

        $parameters = array(
            'agent_id' => $agent_id
        );

        $balance = $this->fetch_query_result($sql, $parameters, false);

        if (empty($balance)) {
            return null;
        }
        return $balance['amount'];
    }

    //get agent id
    public function get_agent_id($operator_id) {
        $sql = 'SELECT `partner_ID` FROM `tbl_distr_munis_portal_agent_device_operators`
				WHERE id=:operator_id AND validity=1';

        $parameters = array(
            'operator_id' => $operator_id
        );

        $agent_id = $this->fetch_query_result($sql, $parameters, false);

        if (empty($agent_id)) {
            return null;
        }
        return $agent_id['partner_ID'];
    }

    //get bank agent Id
    public function get_bankagent_id($ID) {
        $sql = 'SELECT `id` FROM `partner`
				WHERE partner_number=:partner_number AND validity=1';

        $parameters = array(
            'partner_number' => $ID
        );

        $agent_id = $this->fetch_query_result($sql, $parameters, false);

        if (empty($agent_id)) {
            return null;
        }
        return $agent_id['id'];
    }

    public function getCustomerCardNumber($card_uid) {
        $sql = "SELECT card_number FROM `valid_nfc_tags` WHERE nfc_tag_id=:card_uid";

        $parameters = array(
            'card_uid' => $card_uid
        );

        $result = $this->fetch_query_result_bcx($sql, $parameters, false);

        if (empty($result)) {
            return null;
        }
        return $result[0];
    }

    public function record_customer_transaction_per_agent($amount, $transaction_type, $nfc_tag_id, $transaction_id, $device_imei, $operator_id, $request_id, $service_id) {
        $sql = 'INSERT INTO tbl_customer_transactions
																 (
																   amount_paid,	
															       transaction_type,
															       nfc_tag_id,
															       transaction_id,
																   request_id,
															       terminal_time,
															       system_time,
																   device,
																   operator_id,
																   service_id
																 ) 
													  VALUES     (
													  			   :amount_paid,
															       :transaction_type,
															       :nfc_tag_id,
															       :transaction_id,
																   :request_id,
															       NOW(),
															       NOW(),
																   :device_imei,
																   :operator_id,
																   :service_id
															     )';


        $parameters = array(
            'amount_paid' => $amount,
            'transaction_id' => $transaction_id,
            'transaction_type' => $transaction_type,
            'nfc_tag_id' => $nfc_tag_id,
            'device_imei' => $device_imei,
            'request_id' => $request_id,
            'service_id' => $service_id,
            'operator_id' => $operator_id
        );

        $status = $this->execute_query_bcx($sql, $parameters);



        return $status;
    }

    public function debit_amount_on_agent_account($amount, $agent_id) {
        $status = TRUE;
        $sql = "update tbl_partner_accounts set amount = amount - $amount, 	last_status='D' WHERE partner_id='$agent_id'";
        try {
            $result = $this->db_bcx->prepare($sql);
            $result->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return $status;
    }

    //record top up transaction per agent 
    public function record_partner_transaction_per_topup($amount, $transaction_type, $card_uid, $transaction_id, $device_imei, $operator_id, $agent_id, $service_id) {
        $sql = 'INSERT INTO tbl_partner_transaction
																 (
																   amount,	
															       transaction_type,
															       nfc_tag_id,
															       transaction_id,
															       terminal_time,
															       device_id,
																   partner_id,
																   operator_id,
																   service_id
																 ) 
													  VALUES     (
													  			   :amount_paid,
															       :transaction_type,
															       :nfc_tag_id,
															       :transaction_id,
															       NOW(),
																   :device_imei,
																   :agent_id,
																   :operator_id,
																   :service_id
															     )';


        $parameters = array(
            'amount_paid' => $amount,
            'transaction_id' => $transaction_id,
            'transaction_type' => $transaction_type,
            'nfc_tag_id' => $card_uid,
            'device_imei' => $device_imei,
            'operator_id' => $operator_id,
            'agent_id' => $agent_id,
            'service_id' => $service_id
        );

        $status = $this->execute_query_bcx($sql, $parameters);



        return $status;
    }

    public function recharge_amount_on_nfc_tag($amount, $valid_tag_id) {
        $status = TRUE;
        $sql = "update tbl_customer_account_details set amount = amount + $amount, 	last_status='C' WHERE valid_tag_id='$valid_tag_id'";
        try {
            $result = $this->db_bcx->prepare($sql);
            $result->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return $status;
    }

    //update customer account status
    public function update_customer_account_status($ID) {
        $status = TRUE;
        $sql = "update tbl_customer_account_details set validity = 1 WHERE id='$ID'";
        try {
            $result = $this->db_bcx->prepare($sql);
            $result->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        return $status;
    }

    //get account status 
    public function get_customer_account_status($card_uid) {
        $sql = 'SELECT a.id,a.validity FROM tbl_customer_account_details as a INNER JOIN valid_nfc_tags as b on a.valid_tag_id=b.id where b.nfc_tag_id=:card_uid';

        $parameters = array(
            'card_uid' => $card_uid
        );

        $account = $this->fetch_query_result($sql, $parameters, false);

        if (empty($account)) {
            return null;
        }
        return $account;
    }

    function serviceImplementedResponse($request_id, $service_id, $agent_id, $receipt_no, $amount, $balance, $card_no, $message) {
        $response = [
            "status" => "PASS",
            "type" => "BILLPAY_RESPONSE",
            "transaction_id" => $request_id,
            "agent_id" => $agent_id,
            "date" => date("Y-m-d"),
            "time" => date("H:i:s"),
            "receipt_no" => $receipt_no,
            "amount" => $amount,
            "balance" => "Tsh." . number_format($balance) . "/=",
            "card_no" => $card_no,
            "service_id" => $service_id,
            "service_provider" => "BCX",
            "account_no" => "NA",
            "result" => "TF",
            "resp_code" => '000',
            "resp_desc" => "Successfully",
            "flag" => "N",
            "message" => $message];

        return $response;
    }

    function getServiceResponse($status, $type, $resp_code, $resp_desc, $account_no, $request_id, $service_id, $agent_id, $receipt_no, $amount, $balance, $card_no, $message) {
        $response = '{"response":{
			"status":"' . $status . '",
			"type":"' . $type . '",
			"transaction_id":"' . $request_id . '",
			"agent_id":"' . $agent_id . '",
			"date":"' . date("Y-m-d") . '",
			"time":"' . date("H:i:s") . '",
			"receipt_no":"' . $receipt_no . '",
			"amount":"Tsh.' . number_format($amount) . '/=",
			"balance":"Tsh.' . number_format($balance) . '/=",
			"card_no":"' . $card_no . '",
			"service_id" :"' . $service_id . '",
			"service_provider" : "BCX",
			"account_no" :"' . $account_no . '",
			"result" :"TF",
			"resp_code":"' . $resp_code . '",
			"resp_desc":"' . $resp_desc . '",
			"flag" : "N",
	        "message":' . $message . '}}';

        return $response;
    }

    function errorReturnedResponse($request_id, $service_id, $agent_id, $resp_code, $message) {
        $response = '{"response":{
		"status":"FAIL",
		"type":"ERROR_RESPONSE",
		"transaction_id":"' . $request_id . '",
		"agent_id":"' . $agent_id . '",
		"date":"' . date("Y-m-d") . '",
		"time":"' . date("H:i:s") . '",
		"service_id":"' . $service_id . '",
		"service_provider" : "BCX",
		"resp_code":"' . $resp_code . '",
		"resp_desc":"Error",
		"flag" : "N",
        "message":"' . $message . '"}}';
        return $response;
    }

    function verifyKeys() {
        $response = array();
        $response = $this->errorReturnedResponse($request_id, $service, $agent_id, $status_code, $message);
        $cryptoKey_ = $this->ercisCryptor->get_crypto_key();
        $cryptoIv_ = $this->ercisCryptor->get_crypto_iv();

        //step 2. Encrypt Payload using Key and Iv
        $encryptedPayload = $this->ercisCryptor->encrypt_payload($response, base64_decode($cryptoKey_), base64_decode($cryptoIv_)); //encoded return
        //3. Encrypt Key and IV using partiner public Key
        $encryptedCryptoKey = $this->ercisCryptor->crypto_parameter_encrytor($cryptoKey_, $client_pub_key, false);
        $encryptedCryptoIv = $this->ercisCryptor->crypto_parameter_encrytor($cryptoIv_, $client_pub_key, false);

        //4.Sign of encrypted payload using My Private Key
        $signedEncryptedPayload = $this->ercisCryptor->sign_payload($encryptedPayload, $bcx_priv_key); //encoded return
        //5. Construct Respone
        $header = $this->construct_responce_header_Json($UserID, $ClientNameOrIP, $TerminalLocation, $TerminalID, $Id, $TerminalOperatorID, $sourceTimestamp, $ServiceID);
        $body = $this->construct_responce_body_Json($encryptedPayload, $signedEncryptedPayload, $encryptedCryptoKey, $encryptedCryptoIv);
        $status_code = $this->construct_responce_statuscode_Json($status_code);

        $response = $this->construct_responce_in_Json($header, $body, $status_code);

        $this->ercisCryptor->freeKeyMemory($bcx_priv_key);
        $this->ercisCryptor->freeKeyMemory($client_pub_key);
        $this->log_event('Response ', $response);
        echo $response;
    }

}

?>
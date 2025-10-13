<?php

/* * ***************************************************
 *  Author: Peter Paul
 *  Date:2017/08/15
 *  Description:Encryption, Decryption, Sign and Verification Class
 *  Method: SHA-256,
 *  Updated: 19/08/2018
 *  Update Description: formating in and out put of base64_decode/base64_encode
 * ***************************************************** */
define('AES_256_CBC', 'aes-256-cbc');

Class BcxCrypto {

    //private $dbConn ;
    private $cryptoKey;
    private $cryptoIv;
    private $signature;

    public function __construct() {
        try {
            $this->cryptoKey = base64_encode($this->generate_crypto_key());
            $this->cryptoIv = base64_encode($this->generate_crypto_iv());
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // generate roundom cryto key
    public function generate_crypto_key() {
        return openssl_random_pseudo_bytes(32);
    }

    // generate roundom cryto Iv
    public function generate_crypto_iv() {
        return openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
        //return openssl_random_pseudo_bytes(8);
    }

    // get roundom cryto key
    public function get_crypto_key() {
        return $this->cryptoKey;
    }

    // get roundom cryto Iv
    public function get_crypto_iv() {
        return $this->cryptoIv;
    }

    //get private  key from certificate file
    public function get_private_key() {
        return openssl_pkey_get_private($keyPemFilePath);
    }

    //get public  key from certificate file
    public function getPublicKey($pemCertFilePath) {
        return openssl_pkey_get_public($pemCertFilePath);
    }

    //encypt payload
    public function encrypt_payload($payload_data, $rondumCryptKey, $rondumCryptIv) {
        return base64_encode(openssl_encrypt($payload_data, AES_256_CBC, ($rondumCryptKey), 0, ($rondumCryptIv)));
    }

    //decypt payload
    public function decrypt_payload($payload_data, $rondumCryptKey, $rondumCryptIv) {
        return openssl_decrypt(base64_decode($payload_data), AES_256_CBC, ($rondumCryptKey), 0, ($rondumCryptIv));
        //return openssl_decrypt($payload_data, AES_256_CBC, ($rondumCryptKey), 0, ($rondumCryptIv));
    }

    //encypt crypto parameters
    public function crypto_parameter_encrytor($cryptoParameterValue, $p_PEMKey, $isPrivateKey) {
        
        //$key = $this->get_key_from_file('/api/bank/crypto/bcx_to_nmb/' .$p_PEMKey, false, false, null);
        //echo '<p>PEM: ' . $key . '</p>';
        $ENCRYPT_BLOCK_SIZE = 512;
        $encrypted = '';
        $cryptoParameter = str_split(base64_decode($cryptoParameterValue), $ENCRYPT_BLOCK_SIZE);

        foreach ($cryptoParameter as $chunk) {
            
            $partialEncrypted = '';
            //using for example OPENSSL_PKCS1_PADDING as padding
            if ($isPrivateKey == true) {
                $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $p_PEMKey, OPENSSL_PKCS1_PADDING);
            } else {
                $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $p_PEMKey, OPENSSL_PKCS1_PADDING);
            }

            if ($encryptionOk === false) {
                return false;
            }
            //also you can return and error. If too big this will be false
            $encrypted = $encrypted . $partialEncrypted;
        }

        return base64_encode($encrypted);
    }

    //decypt crypto parameters
    public function crypto_parameter_decrytor($cryptoParameterData, $p_PEMKey, $isPrivateKey) {
        $DECRYPT_BLOCK_SIZE = 512;
        $decrypted = '';
        //decode must be done before spliting for getting the binary String
        $data = str_split(base64_decode($cryptoParameterData), $DECRYPT_BLOCK_SIZE);
        foreach ($data as $chunk) {
            $partial = '';
            //be sure to match padding	
            if ($isPrivateKey == true) {
                $decryptionOK = openssl_private_decrypt($chunk, $partial, ($p_PEMKey), OPENSSL_PKCS1_PADDING);
            } else {
                $decryptionOK = openssl_public_decrypt($chunk, $partial, ($p_PEMKey), OPENSSL_PKCS1_PADDING);
            }

            if ($decryptionOK === false) {
                echo "Failed";
                return false;
            }

            //here also processed errors in decryption. If too big this will be false
            $decrypted .= $decrypted . $partial;
        }
        return $decrypted;


        // $payload = array();
        // $temp = str_split(base64_decode($cryptoParameterData),512);
        // foreach ($temp as $key => $value) {
        // openssl_private_decrypt($value, $dencrypted, ($p_PEMKey),OPENSSL_PKCS1_PADDING);
        // array_push($payload, $dencrypted);
        // if (!$dencrypted) {
        // echo 'Failed<br>';
        // }
        // }
        // $result =implode($payload);
        // echo strlen($result).'<br>';
        // return $result;
    }

    //sign encrypt_payloadpayload  wiyh private key
    public function sign_payload($payload_data, $key) {
        //compute signature with SHA-256
        openssl_sign(base64_decode($payload_data), $this->signature, $key, OPENSSL_ALGO_SHA1);
        return base64_encode($this->signature);
    }

    public function sign_payload_plain($payload_data, $key) {
        //compute signature with SHA-256
        openssl_sign($payload_data, $this->signature, $key, OPENSSL_ALGO_SHA1);
        return base64_encode($this->signature);
    }

    //get key from file
    public function get_key_from_file($certficatePath, $isPrivateKey, $protected, $password) {
        // echo 'get_key_from_file-'.$certficatePath.'<br>' ;

        $fp = fopen(MX17_APP_ROOT . APP_DIR . $certficatePath, "r");
        $p_key = fread($fp, 8192);
        fclose($fp);
        if ($isPrivateKey == true) {
            //return openssl_get_privatekey($p_key);
            if ($protected == true) {
                return openssl_get_privatekey($p_key, $password);
            } else {
                return openssl_get_privatekey($p_key);
            }
        } else {
            //return openssl_get_publickey($p_key);
            if ($protected == true) {
                return openssl_get_publickey($p_key, $password);
            } else {
                return openssl_get_publickey($p_key);
            }
        }
    }

    //Check signature
    public function verify($payload_data, $payload_signature, $p_key) {
        $ok = openssl_verify(base64_decode($payload_data), base64_decode($payload_signature), $p_key, OPENSSL_ALGO_SHA1);
        if ($ok == 1) {
            //echo "signature ok (as it should be)\n";
            return true;
        } elseif ($ok == 0) {
            //echo "bad (there's something wrong)\n";
            return false;
        } else {
            //echo "ugly, error checking signature\n";
            return false;
        }
    }

    public function verify_plain($payload_data, $payload_signature, $p_key) {
        try {
            $ok = openssl_verify($payload_data, base64_decode($payload_signature), $p_key, OPENSSL_ALGO_SHA1);
            echo htmlentities('Message: ' . $ok);
            if ($ok == 1) {
                echo "signature ok (as it should be)\n";
                return true;
            } elseif ($ok == 0) {
                echo "bad (there's something wrong)\n";
                return false;
            } else {
                echo "ugly, error checking signature\n";
                return false;
            }
        } catch (Exception $e) {
            echo htmlentities('Message: ' . $e->getMessage());
        }
    }

    //free the key from memory
    public function freeKeyMemory($pkeyid) {
        openssl_free_key($pkeyid);
    }

    //get base64_encoded string
    public function getBase64EncodeString($stringData) {
        return base64_encode($stringData);
    }

    //get base64_decoded string
    public function getBase64DecodedString($stringBase64Data) {
        base64_decode($stringBase64Data);
    }

    function RsaEncode($msg, $publicKey) {
        $temp = str_split($msg, 512);
        $payload = array();
        foreach ($temp as $key => $value) {
            openssl_public_encrypt($value, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);
            array_push($payload, $encrypted);
        }
        $imp_payload = implode($payload);
        $encoded_payload = base64_encode($imp_payload);
        return $encoded_payload;
    }

    function RsaDecode($response, $publicKey) {
        $payload = array();
        $payload2 = base64_decode($response);
        $temp = str_split($payload2, 128);
        foreach ($temp as $key => $value) {
            openssl_public_decrypt($encrypted, $value, $publicKey, OPENSSL_PKCS1_PADDING);
            //openssl_private_decrypt($value, $encrypted, $publicKey);
            array_push($payload, $encrypted);
            if (!$encrypted) {
                echo "Failed";
            }
        }
        $payload = implode($payload);
        $result = utf8_encode($payload);
        return $result;
    }

    function RsaDecodeWithPrivate($cipher, $privateKey) {
        $payload = array();
        $payload2 = base64_decode($cipher);
        $temp = str_split($payload2, 512);
        foreach ($temp as $key => $value) {
            openssl_private_decrypt($value, $dencrypted, $privateKey, OPENSSL_PKCS1_PADDING);
            array_push($payload, $dencrypted);
            if (!$dencrypted) {
                echo "Failed";
            }
        }
        $result = implode($payload);
        $result = base64_encode($result);
        return $result;
    }

}

?> 
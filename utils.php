<?php
class Utils {
    public static $cipher = 'aes-128-gcm';
    protected static $key = '478fy5348fy4hfr8h4rfh7srifu4sh34r';

    protected static function getKey() {
        return substr(sha1(self::$key, true), 0, 16);
    }

    public static function setKey($key) {
        self::$key = $key;
    }

    public static function encrypt($string) {
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($string, self::$cipher, self::getKey(), $options=0, $iv, $tag);
        return base64_encode(json_encode(['iv' => base64_encode($iv), 'tag' => base64_encode($tag), 'data' => $ciphertext]));
    }

    public static function decrypt($encryptedData) {
        $data = json_decode(base64_decode($encryptedData));
        $original_plaintext = openssl_decrypt($data->data, self::$cipher, self::getKey(), $options=0, base64_decode($data->iv), base64_decode($data->tag));
        return $original_plaintext;
    }

    public static function printAsJson($content) {
        header('Content-Type: application/json');
        die(json_encode($content));
    }
}
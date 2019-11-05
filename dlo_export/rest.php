<?php

class Rest {
    private $ch;

    public function __construct() {
        $this->ch = curl_init();

        curl_setopt_array($this->ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_VERBOSE => false,
            CURLOPT_COOKIESESSION => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => 'tmp/cookies.txt',
            CURLOPT_COOKIEFILE => 'tmp/cookies.txt'
        ));
    }

    public function post_request($url, $data = array()) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, count($data));
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($data));

        return curl_exec($this->ch);
    }

    public function get_request($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);

        return curl_exec($this->ch);
    }

    public function json($content) {
        return json_decode($content, true);
    }
}
<?php

class Cookies {
    protected $_filePath;
    protected $_cookies;

    public function __construct($file = 'tmp/cookies.txt') {
        $this->_filePath = $file;
    }

    public function get($key) {
        return $this->_cookies[$key];
    }

    public function parseCookies($file = false) {
        if($file)
            $this->_filePath = $file;

        $this->_cookies = array();

        $lines = file($this->_filePath);

        // iterate over lines
        foreach($lines as $line) {
            // we only care for valid cookie def lines
            if(!($line[0] == '#' && $line[1] == ' ') && substr_count($line, "\t") == 6) {

                // get tokens in an array
                $tokens = explode("\t", $line);
                // trim the tokens
                $tokens = array_map('trim', $tokens);
                // let's convert the expiration to something readable
                $tokens[4] = date('Y-m-d h:i:s', $tokens[4]);

                $cookieName = $tokens[5];
                $cookieValue = $tokens[6];
                $cookieDomain = $tokens[0];

                $this->_cookies[$cookieName] = ['name' => $cookieName, 'value' => $cookieValue, 'domain' => $cookieDomain];

            }
        }
    }
}
<?php
class HvALDAP {
    protected $config = [];
    protected $ldapLink;

    function __construct($configuration = []) {
        $this->config = $configuration;
    }

    protected function connect() {
        if(!$this->ldapLink = ldap_connect($this->config['host'])) {
            throw new Exception('Cannot connect to host');
        }

        ldap_set_option($this->ldapLink,LDAP_OPT_PROTOCOL_VERSION,3);
        ldap_set_option($this->ldapLink,LDAP_OPT_REFERRALS,0);
        ldap_set_option($this->ldapLink, LDAP_OPT_DEBUG_LEVEL, 7);
        ldap_set_option($this->ldapLink, LDAP_OPT_NETWORK_TIMEOUT, 1);

        // verify binding
        if(!$ldapbind = ldap_bind($this->ldapLink, $this->config['ldap_tree'], $this->config['password'])) {
            throw new Exception('LDAP bind failed. User details correct?');
        }
    }

    public function search($query) {
        if(!$result = ldap_search($this->ldapLink, $this->config['ldap_btree'], $query)) {
            throw new Exception('LDAP search failed.');
        }

        if(!$data = ldap_get_entries($this->ldapLink, $result)) {
            throw new Exception('LDAP get entries failed.');
        }

        return $data;
    }

    public function process() {
        if(isset($_POST['user']) && isset($_POST['password']) && isset($_POST['key'])) {
            Utils::setKey($_POST['key']);
            $token = Utils::encrypt(json_encode(['user' => $_POST['user'], 'password' => base64_decode($_POST['password'])]));
            Utils::printAsJson(['success' => true, 'data' => $token]);
        }

        if(isset($_POST['token']) && isset($_POST['key']) && isset($_POST['query'])) {
            Utils::setKey($_POST['key']);
            $decryptedData = json_decode(Utils::decrypt($_POST['token']));

            $this->config['user'] = $decryptedData->user;
            $this->config['password'] = $decryptedData->password;
            $this->config['ldap_tree'] = 'CN=' . $decryptedData->user . ',' . $this->config['ldap_tree'];

            try {
                $this->connect();
                return $this->search($_POST['query']);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            throw new Exception('No post data');
        }
    }
}

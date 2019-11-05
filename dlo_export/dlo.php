<?php

DEFINE('BRIGHTSPACE_PATH', 'https://dlo.mijnhva.nl');
DEFINE('SIGN_IN_PATH', 'https://login.hva.nl/');

require __DIR__.'/rest.php';
require __DIR__.'/cookies.php';


class DLO {
    private $_rest;
    private $_cookies;

    public function __construct() {
        $this->_rest = new Rest();
        $this->_cookies = new Cookies();
    }

    public function login($username, $password) {
        // First visit the page to initialize mostly the cookies
        $firstInit = $this->Rest()->get_request(BRIGHTSPACE_PATH);

        // Strip the action path to sign-in from this first visit
        preg_match('/action=\"\/(adfs\/(.*?))\"/', $firstInit, $formActionMatches);
        $formActionPath = $formActionMatches[1];

        // Do login POST request to mijnhva
        $loginMijnHva = $this->Rest()->post_request(SIGN_IN_PATH . $formActionPath, ['UserName' => $username, 'Password' => $password]);

        if(preg_match('/Incorrect user ID/', $loginMijnHva))
            throw new Exception("Username/password invalid");

        // Get the tokens from the login
        preg_match('/action=\"(.*?)\">/', $loginMijnHva, $surfconextFormMatches);
        preg_match('/name=\"SAMLResponse\" value=\"(.*?)\"/', $loginMijnHva, $SAMLResponseFormMatches);

        $surfconextFormAction = $surfconextFormMatches[1];
        $SAMLResponseForm = $SAMLResponseFormMatches[1];

        // Do some magic with surfconext and the tokens
        $surfconextAccess = $this->Rest()->post_request($surfconextFormAction, ['SAMLResponse' => $SAMLResponseForm]);

        preg_match('/action=\"(.*?)\">/', $surfconextAccess, $surfconextFormMatches);
        preg_match('/name=\"SAMLResponse\" value=\"(.*?)\"/', $surfconextAccess, $SAMLResponseFormMatches);
        preg_match('/name=\"RelayState\" value=\"(.*?)\"/', $surfconextAccess, $relayStateFormMatches);

        $dloSsoFormAction = $surfconextFormMatches[1];
        $dloSAMLResponseForm = $SAMLResponseFormMatches[1];
        $dloRelayStateForm = $relayStateFormMatches[1];

        // Login with SSO
        $surfconextAccess = $this->Rest()->post_request($dloSsoFormAction, ['SAMLResponse' => $dloSAMLResponseForm, 'RelayState' => $dloRelayStateForm]);
        // Remove whitespaces
        $surfconextAccessCleaned = preg_replace('/\s+/', ' ', $surfconextAccess);

        // Finally, login process, get last redirect url
        preg_match('/window.location = \'(.*?)\'/', $surfconextAccessCleaned, $processLoginActionUrlMatches);
        $processLoginActionUrl = $processLoginActionUrlMatches[1];

        $dloHomepage = $this->Rest()->get_request(BRIGHTSPACE_PATH . $processLoginActionUrl);

        return (bool)preg_match('/Session.UserId/', $dloHomepage);
    }

    public function getCourses($courseTypeCode = 'Course Offering') {
        $groupsPage = $this->Rest()->json($this->Rest()->get_request(BRIGHTSPACE_PATH . '/d2l/api/lp/1.10/enrollments/myenrollments/'));
        $allMyGroups = [];

        while(true) {
            $allMyGroups = $allMyGroups + $groupsPage['Items'];

            if(!empty($groupsPage['PagingInfo']['Bookmark']))
                $groupsPage = $this->Rest()->json($this->Rest()->get_request(BRIGHTSPACE_PATH . '/d2l/api/lp/1.10/enrollments/myenrollments/?bookmark=' . $groupsPage['PagingInfo']['Bookmark']));
            else
                break;
        }

        $returnGroups = [];
        foreach($allMyGroups as $index => $group)
            if($group['OrgUnit']['Type']['Code'] == $courseTypeCode)
                $returnGroups[] = $group;

        return $returnGroups;
    }

    public function getGroupsByCourseId($courseId) {
        /*$classListRequest = $this->Rest()->json($this->Rest()->get_request(BRIGHTSPACE_PATH . '/d2l/api/le/1.10/41216/classlist/'));
        $classList = [];
        foreach($classListRequest as $user)
            $classList[$user['Identifier']] = $user['FirstName'] . ' ' . $user['LastName'];*/


        $categories = $this->Rest()->json($this->Rest()->get_request(BRIGHTSPACE_PATH . '/d2l/api/lp/1.10/' . $courseId . '/groupcategories/'));
        $courseCategoryGroupData = [];

        foreach($categories as $category) {
            $categoryGroupList = [];
            $categoryGroups = $this->Rest()->json($this->Rest()->get_request(BRIGHTSPACE_PATH . '/d2l/api/lp/1.10/' . $courseId . '/groupcategories/' . $category['GroupCategoryId'] . '/groups/'));

            foreach($categoryGroups as $group) {

                $categoryGroupList[] = $group;
            }

            $courseCategoryGroupData[] = [
                'category' => $category,
                'groups' => $categoryGroupList
            ];
        }

        return $courseCategoryGroupData;
    }



    private function Rest() {
        return $this->_rest;
    }

    private function Cookies() {
        return $this->_cookies;
    }

}
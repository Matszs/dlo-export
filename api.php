<?php

require __DIR__ . '/dlo_export/dlo.php';
require __DIR__ . '/ldap/ldap.php';
require __DIR__ . '/utils.php';


try {
    if (isset($_POST['token']) && isset($_POST['key']) && isset($_POST['action'])) {
        Utils::setKey($_POST['key']);

        $decryptedData = json_decode(Utils::decrypt($_POST['token']));


        switch ($_POST['action']) {
            case "getUsers":
                $dlo = new DLO();
                if ($dlo->login($decryptedData->user . '@ad.hva.nl', $decryptedData->password)) {
                    $users = $dlo->getUsersByCourseId($_POST['courseId']);
                    $studentList = [];
                    foreach ($users as $user) {
                        $studentList[$user['OrgDefinedId']] = $user['FirstName'] . ' ' . $user['LastName'];
                    }
                    Utils::printAsJson(['success' => true, 'data' => $studentList]);
                }
                break;
            case "getCourses":
                $dlo = new DLO();
                if ($dlo->login($decryptedData->user . '@ad.hva.nl', $decryptedData->password)) {
                    $courses = $dlo->getCourses();
                    $courseList = [];
                    foreach ($courses as $course) {
                        $courseList[$course['OrgUnit']['Id']] = $course['OrgUnit']['Name'];
                    }
                    Utils::printAsJson(['success' => true, 'data' => $courseList]);
                }
                break;
            case "ldap":
                $ldap = new HvALDAP([
                    'host' => 'ad.hva.nl',
                    'ldap_tree' => 'OU=Medewerker,OU=Gebruikers,DC=ad,DC=hva,DC=nl',
                    'ldap_btree' => 'ou=gebruikers,dc=ad,dc=hva,dc=nl'
                ]);

                try {
                    $data = $ldap->process();
                    //print_r($data[0]);
                    $studentIdList = [];
                    for ($i = 0; $i < $data['count']; $i++) {
                        if(isset($data[$i]['hvastudentnumber'][0])) {
                            $studentIdList[] = $data[$i]['hvastudentnumber'][0];
                        } else if(isset($data[$i]['name'][0])){
                            $studentIdList[] = $data[$i]['name'][0];
                        }
                    }

                    Utils::printAsJson(['success' => true, 'data' => $studentIdList]);
                } catch (Exception $e) {
                    Utils::printAsJson(['success' => false, 'error' => $e->getMessage()]);
                }
            default:
                Utils::printAsJson(['success' => false, 'error' => "Incorrect or no action given"]);
        }
    }
} catch (Exception $e) {
    echo 'DLO connection failed: ' . $e->getMessage();
}
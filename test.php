<?php

require __DIR__.'/dlo_export/dlo.php';

try {
    $dlo = new DLO();
    if($dlo->login('motten@ad.hva.nl', '')) {
        $myCourses = $dlo->getCourses(); // 41600 == Software for Science, 41216 = Databases, 41384 = Testing

        $categories = $dlo->getGroupsByCourseId(41384);

        foreach($categories as $category) {
            echo $category['category']['Name'] . "\n\n";

            foreach($category['groups'] as $group) {
                echo "  ====> " . $group['Name'] . "\n";
            }

            echo "\n";
        }
    }
} catch (Exception $e) {
    echo 'DLO connection failed: ' . $e->getMessage();
}
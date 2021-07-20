<?php

const SCHOOLS_DOMAIN = 'https://www.bildung.berlin.de/Schulverzeichnis/';
const SCHOOLS_CATALOG_URL = 'https://www.bildung.berlin.de/Schulverzeichnis/SchulListe.aspx';
const SCHOOLS_STUDENT_INFO_URL = 'https://www.bildung.berlin.de/Schulverzeichnis/schuelerschaft.aspx?view=ndh';
const SCHOOLS_INSPECTION_URL = 'https://www.bildung.berlin.de/Schulverzeichnis/schulinspektion.aspx';

$cookieFile = tempnam("/tmp", "CURLCOOKIE");

$connection = curl_init();
curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
curl_setopt($connection, CURLOPT_COOKIESESSION, true);
curl_setopt($connection, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($connection, CURLOPT_COOKIEFILE, $cookieFile);

$schools = [];

curl_setopt($connection, CURLOPT_URL, SCHOOLS_CATALOG_URL);
$listPageHtml = curl_exec ($connection);

$listPage = new DOMDocument();
@$listPage->loadHTML('<?xml encoding="utf-8" ?>' . $listPageHtml);

$schoolsCount = $listPage->getElementById("DataListSchulen")->getElementsByTagName('tr')->count();

/** @var \DOMElement $school */
foreach ($listPage->getElementById("DataListSchulen")->getElementsByTagName('tr') as $school) {
    $schoolUrl = SCHOOLS_DOMAIN . $school->getElementsByTagName('a')->item(0)->attributes->getNamedItem('href')->textContent;
    $schoolId = (int)preg_replace('/[^0-9]/', '', $schoolUrl);

//    if ($schoolId < 21715 || $schoolId > 21720) {
//        continue;
//    }

    $schoolData = [
        'id' => $schoolId,
        'name' => trim($school->getElementsByTagName('a')->item(0)->textContent),
        'type' => trim($school->getElementsByTagName('span')->item(0)->textContent),
        'district_primary' => trim($school->getElementsByTagName('span')->item(1)->textContent),
        'district_secondary' => trim($school->getElementsByTagName('span')->item(2)->textContent),
        'url' => str_replace(" ", '%20', $schoolUrl)
    ];

    /** School details */

    curl_setopt($connection, CURLOPT_URL, $schoolData['url']);
    $schoolDetailsHtml = curl_exec($connection);

    $schoolDetailsPage = new DOMDocument();
    @$schoolDetailsPage->loadHTML('<?xml encoding="utf-8" ?>' . $schoolDetailsHtml);

    $schoolData['address'] = null;

    if ($schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_lblStrasse')
        && $schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_lblOrt')) {
        $schoolData['address'] = sprintf(
            '%s, %s',
            $schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_lblStrasse')->textContent,
            $schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_lblOrt')->textContent
        );
    }


    $schoolData['web'] = null;
    $schoolUrl = $schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_HLinkWeb');

    if ($schoolUrl && $schoolUrl->attributes->getNamedItem('href')) {
        $schoolData['web'] = $schoolUrl->attributes->getNamedItem('href')->textContent;
    }

    $schoolData['languages'] = null;

    if ($schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_lblSprachen')) {
        $schoolData['languages'] = $schoolDetailsPage->getElementById('ContentPlaceHolderMenuListe_lblSprachen')->textContent;
    }

    /** School students statistics */

    curl_setopt($connection, CURLOPT_URL, SCHOOLS_STUDENT_INFO_URL);
    $studentsInfoHtml = curl_exec($connection);

    $studentsInfoPage = new DOMDocument();
    @$studentsInfoPage->loadHTML('<?xml encoding="utf-8" ?>' . $studentsInfoHtml);

    $studentsStatsTable = $studentsInfoPage->getElementById('ContentPlaceHolderMenuListe_GridViewNDH');

    $schoolData['students_total'] = null;
    $schoolData['students_non_german_total'] = null;
    $schoolData['students_non_german_percentage'] = null;

    if ($studentsStatsTable && $studentsStatsTable->getElementsByTagName('tr')->count() > 1) {
        $schoolData['students_total'] = (int)$studentsStatsTable
            ->getElementsByTagName('tr')
            ->item(2)
            ->childNodes
            ->item(0)
            ->textContent;
        $schoolData['students_non_german_total'] = (int)$studentsStatsTable
            ->getElementsByTagName('tr')
            ->item(2)
            ->childNodes
            ->item(3)
            ->textContent;
        $schoolData['students_non_german_percentage'] = (float)str_replace(',', '.',
            trim(
                $studentsStatsTable
                    ->getElementsByTagName('tr')
                    ->item(2)
                    ->childNodes
                    ->item(4)
                    ->textContent
            )
        );
    }

    /** School inspection */

    curl_setopt($connection, CURLOPT_URL, SCHOOLS_INSPECTION_URL);
    $inspectionPageHtml = curl_exec($connection);

    $inspectionPage = new DOMDocument();
    @$inspectionPage->loadHTML('<?xml encoding="utf-8" ?>' . $inspectionPageHtml);

    $schoolInspectionList = $inspectionPage->getElementById('ContentPlaceHolderMenuListe_pnlSchulinspektion');
    $schoolData['inspections'] = [];

    if ($schoolInspectionList) {
        /** @var \DOMElement $inspection */
        foreach ($inspectionPage->getElementById('ContentPlaceHolderMenuListe_pnlSchulinspektion')->getElementsByTagName('a') as $inspection) {
            $schoolData['inspections'][] = [
                'title' => trim($inspection->textContent),
                'url' => trim($inspection->attributes->getNamedItem('href')->textContent)
            ];
        }
    }

    $schools[$schoolId] = $schoolData;

    echo sprintf("School %s parsed, %s schools left\n", $schoolData['name'], $schoolsCount - sizeof($schools));

    sleep(1);
}

$json = json_encode(array('data' => array_values($schools)));
file_put_contents("../data//data.json", $json);

echo "\nDone\n";

<?php

$data = json_decode(file_get_contents('./data.json'), true);

foreach ($data['data'] as $index => $school) {
    $data['data'][$index]['lat'] = null;
    $data['data'][$index]['lng'] = null;
    $data['data'][$index]['google_maps_place_id'] = null;

    if (!$school['address']) {
        continue;
    }

    $geoCodeData = json_decode(file_get_contents(sprintf(
        'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s',
        urlencode($school['address']),
        'AIzaSyARB6Twrs71Jy3Oh-AkA7qD4LrvaAHe0Zk',
    )), true);

    if (!$geoCodeData) {
        continue;
    }

    $data['data'][$index]['lat'] = $geoCodeData['results'][0]['geometry']['location']['lat'] ?? null;
    $data['data'][$index]['lng'] = $geoCodeData['results'][0]['geometry']['location']['lng'] ?? null;
    $data['data'][$index]['google_maps_place_id'] = $geoCodeData['results'][0]['place_id'] ?? null;

    echo sprintf("School %s parsed\n", $school['name']);
}

$json = json_encode($data);
file_put_contents("data-formatted.json", $json);

echo "\nDone\n";

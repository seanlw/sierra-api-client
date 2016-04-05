#!/usr/bin/php -q
<?php
include('Sierra.php');

$api = new Sierra(array(
    'endpoint' => 'SIERRA_ENDPOINT',
    'key' => 'API_KEY',
    'secret' => 'MY_SECRET'
));


$res = $api->query('bibs/6349196/marc', array(), true);


print_r($res);

?>
<?php
function hello($eventData) : array
{
    $response = ['msg' => 'hello from PHP '.PHP_VERSION];
    $response['eventData'] = $eventData;
    $response['data'] = json_decode($eventData);
    return $response;
}
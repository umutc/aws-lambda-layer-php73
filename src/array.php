<?php

require_once __DIR__ . '/lib/ArrayStats.php';

use ArrayStatistics\ArrayStats;
use \ArrayStatistics\GraphItem;


function stats($eventData): array
{
    $data = json_decode($eventData);
    $response = [];
    try {
        foreach ($data->points as $key => $point) {
            $arrayStats = new ArrayStats($point->results);
            $data->points[$key]->stats = (object)[
                "_min" => $data->min,
                "_max" => $data->max,
                "mean" => $arrayStats->mean(),
                "median" => $arrayStats->median(),
                "mode" => $arrayStats->mode(),
                "range" => $arrayStats->range(),
                "standard_deviation" => $arrayStats->standard_deviation(),
                "frequency" => $arrayStats->frequency(),
                "min" => $arrayStats->min(),
                "max" => $arrayStats->max(),
                "maxGraphDataValue" => 0,
                "graphData" => $arrayStats->graphData($data->min, $data->max),
            ];
            $maxGraphDataValue = max(array_map(function(GraphItem $item) {
                return $item->getValue();
            }, $data->points[$key]->stats->graphData));
            $data->points[$key]->stats->maxGraphDataValue = $maxGraphDataValue;

            $x = null;
            if ($maxGraphDataValue <= 0.0001) $x = 10000;
            elseif ($maxGraphDataValue <= 0.001) $x = 1000;
            elseif ($maxGraphDataValue <= 0.01) $x = 100;
            elseif ($maxGraphDataValue <= 0.1) $x = 10;

            if ($x) {
                foreach ($data->points[$key]->stats->graphData as $item) {
                    $item->multiplyValue($x);
                }
            }
            unset($data->points[$key]->results);
            unset($stats);
        }
        $response = $data->points;
        unset($data);
    } catch (Exception $e) {
    }
    return $response;
}

function info($eventData): array
{
    $response = [
        'PHP_VERSION' => PHP_VERSION,
        '__DIR__' => __DIR__,
    ];
    return $response;
}
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
            $maxGraphDataValue = max(array_map(function (GraphItem $item) {
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

function item_analysis($eventData)
{
    $data = json_decode($eventData);

    /* Null Matrix STD_COUNT X QUESTION_COUNT */
    $matrix = array_fill_keys($data->esssr, array_fill_keys($data->question_ids, 0));
    foreach ($data->esssrq as $esssrq) {
        $matrix[$esssrq->esss_id_row_index][$esssrq->question_id] = 1;
    }

    /* Sort descending as sum of student point */
    usort($matrix, 'usortSumDesc');

    /* Chunk the sorted matrix 27% top and 27% bottom  */
    $top27 = array_slice($matrix, 0, (int)((27 / 100) * count($data->esssr)), true);
    $bottom27 = array_slice($matrix, (count($data->esssr) - (int)((27 / 100) * count($data->esssr))), (int)((27 / 100) * count($data->esssr)), true);
    $questions = [];
    foreach ($data->questions as $question) {
        $questions[$question->id] = $question;
    }

    $items = array_map(function ($question_id) use ($matrix, $top27, $bottom27, $questions) {
        $columnTotal = array_column($matrix, $question_id);
        $columnTop27 = array_column($top27, $question_id);
        $columnBottom27 = array_column($bottom27, $question_id);
        $totalSum = array_sum($columnTotal);
        $totalSumPersentage = $totalSum / count($matrix) * 100;
        $totalSumTop27 = array_sum($columnTop27);
        $totalSumBottom27 = array_sum($columnBottom27);
        $countTop27 = count($top27);
        $countBottom27 = count($bottom27);
        $Pj = ($totalSumTop27 + $totalSumBottom27) / ($countTop27 + $countBottom27);
        $Sj2 = $Pj * (1 - $Pj);
        $SS = sqrt($Sj2);
        $rjx = ($totalSumTop27 - $totalSumBottom27) / $countTop27;
        $arrayStats = new ArrayStats($columnTotal);

        return [
            'question_id' => $question_id,
            'question' => $questions[$question_id],
            'data' => [
                'totalSum' => [
                    'id' => 'totalSum',
                    'title' => 'Maddeyi toplam doğru cevaplayan öğrenci sayısı',
                    'value' => $totalSum
                ],
                'totalSumPersentage' => [
                    'id' => 'totalSumPersentage',
                    'title' => 'Madde başarı yüzdesi',
                    'value' => $totalSumPersentage
                ],
                'totalSumTop27' => [
                    'id' => 'totalSumTop27',
                    'title' => 'Maddeyi üst grupta doğru cevaplayan öğrenci sayısı',
                    'value' => $totalSumTop27
                ],
                'totalSumBottom27' => [
                    'id' => 'totalSumBottom27',
                    'title' => 'Maddeyi alt grupta doğru cevaplayan öğrenci sayısı',
                    'value' => $totalSumBottom27
                ],
                'Pj' => [
                    'id' => 'Pj',
                    'title' => 'Madde güçlük indeksi',
                    'value' => $Pj
                ],
                'Sj2' => [
                    'id' => 'Sj2',
                    'title' => 'Madde varyansı',
                    'value' => $Sj2
                ],
                'rjx' => [
                    'id' => 'rjx',
                    'title' => 'Madde ayırıcılık gücü',
                    'value' => $rjx
                ],
                'SS' => [
                    'id' => 'SS',
                    'title' => 'Standart sapma',
                    'value' => $SS
                ],
                'ri' => [
                    'id' => 'ri',
                    'title' => 'Madde güvenirlik indeksi',
                    'value' => $rjx * $SS
                ],
                'stats' => [
                    "_min" => 0,
                    "_max" => 1,
                    "mean" => $arrayStats->mean(),
                    "median" => $arrayStats->median(),
                    "mode" => $arrayStats->mode(),
                    "range" => $arrayStats->range(),
                    "variance" => $arrayStats->variance(),
                    "standard_deviation" => $arrayStats->standard_deviation(),
                    "frequency" => $arrayStats->frequency(),
                    "min" => $arrayStats->min(),
                    "max" => $arrayStats->max(),
                    "maxGraphDataValue" => 1,
                    "graphData" => $arrayStats->graphData(0, 1),
                ]
            ]
        ];

    }, $data->question_ids);

    $graphData = [
        [
            'id' => 1,
            'name' => '[Pj>0.90]',
            'value' => 0,
            'desc' => 'Eğer etkili bir öğretim varsa tercih edilir'
        ],
        [
            'id' => 2,
            'name' => '[Pj>=0.60][rjx>=0.20]',
            'value' => 0,
            'desc' => 'Tipik iyi bir madde'
        ],
        [
            'id' => 3,
            'name' => '[Pj>=0.60][rjx<0.20]',
            'value' => 0,
            'desc' => 'Üzerinde çalışılması gereken madde'
        ],
        [
            'id' => 4,
            'name' => '[Pj<0.60][rjx>=0.20]',
            'value' => 0,
            'desc' => 'Zor fakat ayırt edici bir madde (Eğer yüksek standartlara sahipseniz bu soru iyidir)'
        ],
        [
            'id' => 5,
            'name' => '[Pj<0.60][rjx<0.20]',
            'value' => 0,
            'desc' => 'Zor ve ayırt edici olmayan madde (Bu madde kullanılamaz)'
        ],
    ];

    $Pj = [];
    foreach ($items as $item) {
        $Pj[] = $item['data']['Pj']['value'];
        if ($item['data']['Pj']['value'] > 0.90) {
            $graphData[0]['value']++;
        } else if ($item['data']['Pj']['value'] >= 0.60 && $item['data']['rjx']['value'] >= 0.20) {
            $graphData[1]['value']++;
        } else if ($item['data']['Pj']['value'] >= 0.60 && $item['data']['rjx']['value'] < 0.20) {
            $graphData[2]['value']++;
        } else if ($item['data']['Pj']['value'] < 0.60 && $item['data']['rjx']['value'] >= 0.20) {
            $graphData[3]['value']++;
        } else if ($item['data']['Pj']['value'] < 0.60 && $item['data']['rjx']['value'] < 0.20) {
            $graphData[4]['value']++;
        }
    }

    return [
        'PjAvg' => array_sum($Pj) / count($Pj),
        'studentCount' => count($data->esssr),
        'questionCount' => count($data->question_ids),
        'graphData' => $graphData,
        'items' => $items,
    ];
}

function usortSumDesc(Array $a, Array $b)
{
    return array_sum($b) - array_sum($a);
}
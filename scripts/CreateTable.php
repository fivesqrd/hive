<?php

if (count($argv) != 2) {
    echo "Uusage: {$argv[0]} <config_file>\n";
    exit;
}

require_once 'vendor/autoload.php';
$config = require_once $argv[1];

$spec = [
    'types' => [
        'partition' => 'S',
        'sort'      => null
    ],
    'capacity'  => ['read' => 5, 'write' => 5],
    'indexes' => [
        'Queue-Timeslot-Index' => [
            'type' => 'global',
            'keys' => [
                ['name' => 'Queue', 'types' => ['key' => 'HASH', 'attribute' => 'S']],
                ['name' => 'Timeslot', 'types' => ['key' => 'RANGE', 'attribute' => 'S']],
            ],
            'capacity' => ['read' => 5, 'write' => 5]
        ]
    ]
];

$db = new Bego\Database(
    new Aws\DynamoDb\DynamoDbClient($config['aws']), new Aws\DynamoDb\Marshaler()
);

$db->table(new Hive\Model($config['table']))->create($spec);
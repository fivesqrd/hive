# Hive PHP Client

## Configuration
```
$config = [
    'table' => 'My-Table-Name',
    'aws' => [
        'version' => 'latest',
        'region'  => 'eu-west-1',
        'credentials' => [
            'key'    => 'my-key',
            'secret' => 'my-secret',
        ],
    ],
];
```

## Preparing a DynamoDb table
Create a local config file say config.php

```
<?php

return [
    'table' => 'My-Table-Name',
    'aws' => [
        'version' => 'latest',
        'region'  => 'eu-west-1',
        'credentials' => [
            'key'    => 'my-key',
            'secret' => 'my-secret',
        ],
    ],
];
```

Run the table create script
```
php vendor/fivesqrd/hive/scripts/CreateTable.php config.php
```

## Instantiate the queue
```
$queue = Hive\Queue::instance(
    $config, 'Welcome-Email-Notifications'
);
```

## Add a job to the queue
```
$payload = ['to' => 'you@domain.com', 'subject' => 'hello'];

/* Run as soon as possible */
$job = $queue->add($payload);

Hive\Queue::instance('Welcome-Email-Notifications')->add($payload);
```

```

/* Schedule for later */
$job = $queue->add(
    $payload, date('c', utctime() + 3600)
);
```

## Get all pending jobs
```
$jobs = $queue->receive(5, 300);

foreach ($jobs as $job) {

    $payload = $job->attribute('Payload');

    /* Do the work */
    $jobId = $job->attribute('Id');

    /* Mark job as done */
    $queue->done($job);
}
```

# Hive PHP Client
Hive is a simple job queue library for PHP that uses DynamoDB for a backend.

## Configuration
```
$config = [
    'table' => 'My-Table-Name',
    'aws' => [
        'version' => '2012-08-10',
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
$queue = Hive\Factory::queue(
    $config, 'Welcome-Email-Notifications'
);

```

## Add a job to the queue
```

$job = Hive\Job::create(
    ['to' => 'you@domain.com', 'subject' => 'hello']
);

/* Run as soon as possible */
$result = $queue->add($job);
```

```

/* Schedule for later */
$job = Hive\Job::create(
    ['to' => 'you@domain.com', 'subject' => 'hello'], 
    gmdate('U') + 300
);

$result = $queue->add($job);
```

```
/* Add multiple jobs as part of a batch */

$jobs = [
    Hive\Job::create(['to' => 'you@domain.com', 'subject' => 'hello']),
    Hive\Job::create(['to' => 'you@domain.com', 'subject' => 'hello 2']),
];

$batchId = $queue->batch($job);
```

## Get all pending jobs from a queue
```
/* Receive 5 jobs and lock them for 300 seconds (FIFO) */
$jobs = $queue->receive(5, 300);

foreach ($jobs as $job) {

    $payload = $job->payload();

    /* Do the work */
    $jobId = $job->id();

    /* Mark job as done */
    $queue->done($job);
}
```

```
/* Receive 5 jobs and lock them for 300 seconds (FILO) */
$jobs = $queue->receive(5, 300, false);

foreach ($jobs as $job) {

    $subject = $job->payload('subject');

    /* Do the work */
    $jobId = $job->id();

    /* Mark job as done */
    $queue->done($job);
}
```

## Queurying the contents of the queue
View the contents of current and past queue items without affecting the queue
```
$factory = Hive\Factory::query(
    $config, 'Welcome-Email-Notifications'
);

/* Show the next 10 items waiting in the queue (FIFO) */
$items = $query->queued(10, true);

/* Show the next 10 items waiting in the queue (FILO) */
$items = $query->queued(10, true);

/* Show the 10 most recently added items (queued or not) */
$items = $query->recent(10);

/* Iterate over all the most recently added items (queued or not) */
$items = $query->recentByPage(1);
$items = $query->recentByPage(2);
$items = $query->recentByPage(3);
```

```
/* Access individual job attributes */
foreach ($items as $job) {
    echo "Job id: {$job->attribute('Id')}\n";
    echo "Status: {$job->attribute('Status')}\n";
    echo "Created on: {$job->attribute('Timestamp')}\n";
    echo "Scheduled for: {$job->attribute('Timeslot')}\n";
}
```

```
/* Dump all attrinutes */
foreach ($items as $job) {
    print_r($job->attributes());
}
```

## Cancelling a queued job
```
$result = $queue->cancel($job->attribute('Id'));
```
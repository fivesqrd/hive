<?php

namespace Hive;

class Queue
{
    protected $_name;

    protected $_table;

    const INDEX_NAME = 'Queue-Timeslot-Index';
    const ATTEMPT_LIMIT = 3;

    public static function instance($config, $name)
    {
        $db = new \Bego\Database(
            new \Aws\DynamoDb\DynamoDbClient($config['aws']), new \Aws\DynamoDb\Marshaler()
        );

        $table = $db->table(
            new Model($config['table'])
        );

        return new static($table, $name);
    }

    public function __construct($table, $name)
    {
        $this->_table = $table;
        $this->_name = $name;
    }

    public function add(Job $job)
    {
        $item = $job->item();

        $item['Queue'] = $this->_name;

        $this->_table->put($item);

        return $item;
    }

    /**
     * Add jobs to queue with the same key
     */
    public function batch(array $jobs)
    {
        $batchId = uniqid();

        foreach ($jobs as $job) {
            $item = $job->item();

            $item['Queue'] = $this->_name;
            $item['Batch'] = $batchId;

            $this->_table->put($item);
        }

        return $batchId;
    }

    public function receive($limit, $timeout = 300, $fifo = true)
    {
        $results = $this->_table->query(static::INDEX_NAME)
            ->key($this->_name)
            ->condition('Timeslot', '<=', gmdate('c'))
            //->consistent()
            ->reverse($fifo)
            ->limit($limit)
            ->fetch(); 


        foreach ($results as $item) {
            $attempts = $item->attribute('Attempts');

            $item->set('Timeslot', gmdate('c', time() + $timeout));
            $item->set('Worker', gethostname());
            $item->set('Started', gmdate('c'));
            $item->set('Attempts', $attempts + 1);

            if ($attempts >= static::ATTEMPT_LIMIT) {
                /* Last attempt for this job */
                $job->remove('Timeslot');
                $job->set('Status', 'failed');
            } 

            $this->_table->update($item);
        }

        return $results;
    }

    public function done($job)
    {
        $job->set('Status', 'completed');
        $job->set('Completed', gmdate('c'));
        if (!$job->attribute('Worker')) {
            $job->set('Worker', gethostname());
        }
        $job->remove('Timeslot');

        $this->_table->update($job);
    }
}
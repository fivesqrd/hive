<?php

namespace Hive;

class Queue
{
    protected $_name;

    protected $_table;

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

    public function add($payload, $timeslot = 0)
    {
        return $this->_table->put([
            'Id'        => bin2hex(random_bytes(16)), 
            'Queue'     => $this->_name,
            'Timestamp' => gmdate('c'),
            'Timeslot'  => $timeslot ?: gmdate('c'), //or 0 to run as soon as possible
            'Payload'   => $payload,
            'Status'    => 'queued'
        ]);
    }

    public function receive($limit, $ttl = 300)
    {
        $results = $this->_table->query('Queue-Locked-Index')
            ->key($this->_name)
            ->condition('Timeslot', '<=', gmdate('c'))
            ->consistent()
            ->limit($limit)
            ->fetch(); 

        foreach ($results as $item) {
            $item->set('Timeslot', date('c', utctime() + $ttl));
            $item->set('Worker', gethostname());
            $item->set('Started', gmdate('c'));
            $table->update($item);
        }

        return $results;
    }

    public function done($job)
    {
        $job->set('Status', 'completed');
        $job->set('Worker', gethostname());
        $job->set('Completed', gmdate('c'));
        $job->remove('Timeslot');

        $this->_table->update($job);
    }
}
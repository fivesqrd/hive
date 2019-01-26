<?php

namespace Hive;

class Queue
{
    protected $_name;

    protected $_table;

    const INDEX_NAME = 'Queue-Timeslot-Index';

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
        /* Keep for 30 days */
        $ttl = 86400 * 30;

        return $this->_table->put([
            'Id'        => bin2hex(random_bytes(16)), 
            'Queue'     => $this->_name,
            'Timestamp' => gmdate('c'),
            'Timeslot'  => $timeslot ? gmdate('c', $timestlot) : '0', //or 0 to run as soon as possible
            'Destroy'   => $timeslot ? $timeslot + $ttl : time() + $ttl,
            'Payload'   => $payload,
            'Status'    => 'queued'
        ]);
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
            $item->set('Timeslot', gmdate('c', time() + $timeout));
            $item->set('Worker', gethostname());
            $item->set('Started', gmdate('c'));
            $this->_table->update($item);
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
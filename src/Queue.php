<?php

namespace Hive;

use Bego;

class Queue
{
    protected $_name;

    protected $_table;

    const INDEX_NAME = 'Queue-Timeslot-Index';

    public function __construct($table, $name)
    {
        $this->_table = $table;
        $this->_name = $name;
    }

    public function add(Job $job)
    {
        $item = $this->_table->put(
            $job->queue($this->_name)->item()->attributes()
        );

        return $job->id();
    }

    public function cancel($id)
    {
        $item = $this->_table->fetch($id);

        if (!$item->attribute('Id')) {
            throw new Exception(
                "Requested job is not in queue anymore"
            );
        }

        if ($item->attribute('Queue') != $this->_name) {
            throw new Exception(
                "Requested action is not authorised"
            );
        }

        if ($item->attribute('Status') != 'queued') {
            throw new Exception(
                "Job {$id} is {$item->attribute('Status')} and cannot be cancelled"
            );
        }

        return $this->_table->delete($item);
    }

    /**
     * Add jobs to queue with the same key
     */
    public function batch(array $jobs)
    {
        $batchId = uniqid();

        foreach ($jobs as $job) {

            /* Todo: use batch write instead */

            $this->_table->put(
                $job->batch($batchId)->queue($this->_name)->item()->attributes()
            );
        }

        return $batchId;
    }

    public function receive($limit, $timeout = 300, $fifo = true)
    {
        $results = $this->_table->query(static::INDEX_NAME)
            ->key($this->_name)
            ->condition(Bego\Condition::comperator('Timeslot', '<=', gmdate('c')))
            ->reverse($fifo)
            ->limit($limit)
            ->fetch(); 

        $received = [];

        foreach ($results as $item) {

            $attempts = $item->attribute('Attempts');

            $job = new Job($item);

            $attempts = $job->prepare($timeout);

            /* 
             * Using attempts as a version number for optimistic locking.
             * If the number of attempts is not consistent without our value,
             * another worker has beaten us to taking this job
             */

            $conditions = [
                 Bego\Condition::comperator('Attempts', '=', $attempts),
            ];

            /* 
             * Only if the update succeeded will we return the item 
             */

            if ($this->_table->update($job->item(), $conditions)) {
                $received[] = $job;
            }
        }

        return $received;
    }

    public function done($job)
    {
        $this->_table->update($job->completed()->item());
    }
}

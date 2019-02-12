<?php

namespace Hive;

use Bego;

class Job
{
    protected $_item;

    protected $_attemptLimit = 3;

    /* 30 days */
    CONST TTL = 2592000;

    public static function create($payload, $timeslot = 0)
    {
        $destroy = $timeslot ?: gmdate('U');

        return new static(new Bego\Item([
            'Id'        => bin2hex(random_bytes(16)), 
            'Timestamp' => gmdate('c'),
            'Timeslot'  => $timeslot ? gmdate('c', $timeslot) : '0', //or 0 to run as soon as possible
            'Destroy'   => $destroy + static::TTL,
            'Payload'   => $payload,
            'Attempts'  => 0,
            'Status'    => 'queued'
        ]));
    }

    public function __construct($item)
    {
        $this->_item = $item;
    }

    public function id()
    {
        return $this->get('Id');
    }

    public function payload($key = null)
    {
        if ($key === null) {
            return $this->get('Payload');
        }

        $data = $this->get('Payload');

        $keys = explode('.', $key);

        foreach ($keys as $innerKey) {
            if (!array_key_exists($innerKey, $data)) {
                return null;
            }

            $data = $data[$innerKey];
        }

        return $data;
    }

    /**
     * Set this job's queue
     */
    public function queue($name)
    {
        $this->_item->set('Queue', $name);

        return $this;
    }

    /**
     * Mark job as one of several in a batch
     */
    public function batch($id)
    {
        $this->_item->set('Batch', $id);

        return $this;
    }

    public function item()
    {
        return $this->_item;
    }

    public function get($key)
    {
        return $this->_item->attribute($key);
    }

    /**
     * Prepare for flight
     */
    public function prepare($timeout)
    {
        $attempts = $this->_item->attribute('Attempts');

        if ($attempts >= $this->_attemptLimit) {
            /* Last attempt for this job */
            $this->_item->remove('Timeslot');
            $this->_item->set('Status', 'failed');
        } else {
            /* Using the timeslot as a TTL for this attempt */
            $this->_item->set('Timeslot', gmdate('c', time() + $timeout));
            $this->_item->set('Worker', gethostname());
            $this->_item->set('Started', gmdate('c'));
        }

        /*
         * Keep tabs on how many times we've returned this job
         */

        $this->_item->set('Attempts', $attempts + 1);

        return $attempts;
    }

    /**
     * Mark as done
     */
    public function completed()
    {
        $this->_item->set('Status', 'completed');
        $this->_item->set('Completed', gmdate('c'));
        
        if (!$this->_item->attribute('Worker')) {
            $this->_item->set('Worker', gethostname());
        }
        
        $this->_item->remove('Timeslot');

        return $this;
    }
}

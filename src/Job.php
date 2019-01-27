<?php

namespace Hive;

class Job
{
    protected $_payload;

    protected $_timeslot = 0;

    /* 30 days */
    protected $_ttl = 2592000;

    protected $_id;

    public function __construct($payload, $timeslot = 0)
    {
        $this->_payload = $payload;
        $this->_timeslot = $timeslot;
        $this->_id = bin2hex(random_bytes(16));
    }

    public function item()
    {
        return [
            'Id'        => $this->_id, 
            'Timestamp' => gmdate('c'),
            'Timeslot'  => $this->_getTimeslot(), //or 0 to run as soon as possible
            'Destroy'   => $this->_getDestroyTime(),
            'Payload'   => $this->_payload,
            'Status'    => 'queued'
        ];
    }

    protected function _getDestroyTime()
    {
        if (!$this->_timeslot) {
            return gmdate('U') + $this->_ttl;
        }

        return $this->_timeslot + $this->_ttl;
    }

    protected function _getTimeslot()
    {
        if (!$this->_timeslot) {
            return '0';
        }

        return gmdate('c', $this->_timeslot);
    }
}
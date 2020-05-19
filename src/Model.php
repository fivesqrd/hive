<?php

namespace Hive;

class Model extends \Bego\Model
{
    /**
     * Table name
     */
    protected $_name;

    /**
     * Table's partition key attribute
     */
    protected $_partition = 'Id';

    /**
     * Table's sort key attribute
     */
    protected $_sort = null;

    /**
     * List of indexes available for this table
     */
    protected $_indexes = [];

    public function __construct($name)
    {
        $this->_name = $name;
        $this->_indexes = [
            'Queue-Timeslot-Index' => [
                'key' => 'Queue', 'sort' => 'Timeslot'
            ],
            'Queue-Timestamp-Index' => [
                'key' => 'Queue', 'sort' => 'Timestamp'
            ],
        ];
    }
}
<?php

namespace Hive;

class Query
{
    protected $_name;

    protected $_table;

    const INDEX_TIMESTAMP = 'Queue-Timestamp-Index';

    public function __construct($table, $name)
    {
        $this->_table = $table;
        $this->_name = $name;
    }

    public function queued($limit = 30, $fifo = true)
    {
        return $this->_table->query(QUEUE::INDEX_NAME)
            ->key($this->_name)
            ->reverse($fifo)
            ->limit($limit)
            ->fetch(); 
    }

    public function recent($limit = 30)
    {
        return $this->_table->query(static::INDEX_TIMESTAMP)
            ->key($this->_name)
            ->reverse(true)
            ->limit($limit)
            ->fetch(); 
    }

    public function recentByPage($page = 1)
    {
        return $this->_table->query(static::INDEX_TIMESTAMP)
            ->key($this->_name)
            ->reverse(true)
            ->fetch($page); 
    }
}

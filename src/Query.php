<?php

namespace Hive;

use Bego;
use Aws\DynamoDb;

class Query
{
    protected $_name;

    protected $_table;

    const INDEX_TIMESTAMP = 'Queue-Timestamp-Index';

    public static function create($config, $name)
    {
        if (!isset($config['aws']['version'])) {
            $config['aws']['version'] = '2012-08-10';
        }
        
        $db = new Bego\Database(
            new DynamoDb\DynamoDbClient($config['aws']), new \Aws\DynamoDb\Marshaler()
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

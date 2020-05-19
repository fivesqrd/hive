<?php
namespace Hive;

use Bego;
use Aws\DynamoDb;

class Factory
{

    protected $_table;

    protected $_name;

    public static function instance($config, $name)
    {
        if (!isset($config['aws']['version'])) {
            $config['aws']['version'] = '2012-08-10';
        }
        
        $db = new Bego\Database(
            new DynamoDb\DynamoDbClient($config['aws']), new DynamoDb\Marshaler()
        );

        $table = $db->table(
            new Model($config['table'])
        );

        return new static($table, $name);
    }

    public static function queue($config, $name)
    {
        return static::instance($config, $name)->createQueueInstance();
    }

    public static function query($config, $name)
    {
        return static::instance($config, $name)->createQueryInstance();
    }

    public function __construct($table, $name)
    {
        $this->_table = $table;
        $this->_name = $name;
    }

    public function createQueryInstance()
    {
        return new Query($this->_table, $this->_name);
    }

    public function createQueueInstance()
    {
        return new Queue($this->_table, $this->_name);
    }
}
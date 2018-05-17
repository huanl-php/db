<?php

namespace HuanL\Db\Driver\MySQL;

use HuanL\Db\Db;
use PDO;
use HuanL\Db\DbConnect;
use HuanL\Db\PrefixInterface;

class MySQLDBConnect extends DbConnect implements PrefixInterface {

    /**
     * 表前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * mysql连接初始化
     * MySQLDBConnect constructor.
     * @param string $user
     * @param string $passwd
     * @param string $db
     * @param string $host
     * @param int $port
     */
    public function __construct(string $user, string $passwd = '', string $db = '',
                                string $prefix = '', string $host = 'localhost',
                                int $port = 3306, array $param = []) {
        $param = array_merge($param, [
            'dbname' => $db, 'host' => $host, 'port' => $port
        ]);
        parent::__construct('mysql', $param, $user, $passwd, [PDO::ATTR_PERSISTENT => true]);
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getPrefix(): string {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return Db
     */
    public function setPrefix(string $prefix): Db {
        $this->prefix = $prefix;
        return $this;
    }

}

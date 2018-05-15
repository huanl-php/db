<?php


namespace HuanL\Db\Driver;


use HuanL\Db\DbConnect;

class MySQLDBConnect extends DbConnect {

    /**
     * mysql连接初始化
     * MySQLDBConnect constructor.
     * @param string $user
     * @param string $passwd
     * @param string $db
     * @param string $localhost
     * @param int $port
     */
    public function __construct(string $user, string $passwd, string $db,
                                string $localhost = 'localhost', int $port = 3306
    ) {
        parent::__construct('mysql', [
            'dbname' => $db, 'host' => $localhost, 'port' => $port
        ], $user, $passwd);
    }


}

<?php


namespace HuanL\Db;

/**
 * Class Db
 * @package HuanL\Db
 */
class Db {

    /**
     * 连接对象
     * @var DbConnect
     */
    private $dbConnect = null;

    public function __construct(DbConnect $dbConnect = null) {
        $this->dbConnect = $dbConnect;
    }
    
}

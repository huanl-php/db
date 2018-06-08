<?php


namespace HuanL\Db;

/**
 * Class Db
 * @package HuanL\Db
 */
abstract class Db implements DbOperInterface {

    /**
     * 连接对象
     * @var DbConnect
     */
    protected $dbConnect = null;

    public function __construct(DbConnect $dbConnect = null) {
        $this->dbConnect = $dbConnect;
    }

    /**
     * 上一次插入的id
     * @return int
     */
    public function lastId(){
        return $this->dbConnect->lastId();
    }
}

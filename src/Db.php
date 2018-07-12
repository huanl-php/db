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
    public function lastId() {
        return $this->dbConnect->lastId();
    }

    /**
     * 开始事务
     * @return bool
     */
    public function begin(): bool {
        return $this->dbConnect->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(): bool {
        return $this->dbConnect->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollback(): bool {
        return $this->dbConnect->rollBack();
    }

    /**
     * 是否在事务内
     * @return bool
     */
    public function inTransaction(): bool {
        return $this->dbConnect->inTransaction();
    }

    /**
     * 错误代码
     * @return int
     */
    public function errorCode(): int {
        return $this->dbConnect->errorCode();
    }

    /**
     * 错误详细信息
     * @return array
     */
    public function errorInfo(): array {
        return $this->dbConnect->errorInfo();
    }
}

<?php


namespace HuanL\Db;

use PDO;

/**
 * Class DbConnect
 * @method int errorCode()
 * @method array errorInfo()
 * @method PDOStatement|bool query ($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = array())
 * @package HuanL\Db
 */
class DbConnect {

    /**
     * pdo链接
     * @var PDO;
     */
    private $pdo = null;


    /**
     * 链接字符串
     * @var string
     */
    private $dns = '';

    /**
     * 数据库用户
     * @var string
     */
    private $user = '';

    /**
     * 数据库密码
     * @var string
     */
    private $passwd = '';

    /**
     * 数据库连接选项
     * @var array
     */
    private $options = [];

    /**
     * 连接数据库,初始化pdo对象
     * DbConnect constructor.
     * @param string $dbtype
     * @param array $param
     * @param string $user
     * @param string $passwd
     * @param array $options
     */
    public function __construct(string $dbtype, array $param = [], string $user = '',
                                string $passwd = '', array $options = []) {
        $this->dns = "$dbtype:";
        foreach ($param as $key => $value) {
            $this->dns .= $key . '=' . $value . ';';
        }
        $this->user = $user;
        $this->passwd = $passwd;
        $this->options = $options;
        $this->reconnect();
    }

    /**
     * 数据库重连
     * @return void
     */
    public function reconnect() {
        $this->pdo = null;
        $this->pdo = new PDO($this->dns, $this->user, $this->passwd, $this->options);
    }

    /**
     * 魔术方法call,更好的调用pdo对象中的方法
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws MethodExistException
     */
    public function __call($name, $arguments) {
        // TODO: Implement __call() method.
        if (method_exists($this->pdo, $name)) {
            return call_user_func_array([$this->pdo, $name], $arguments);
        }
        throw new MethodExistException('Did not find the corresponding method!');
    }

    /**
     * 开始事务
     * @return bool
     */
    public function begin(): bool {
        return $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }

    /**
     * 回滚事务
     * @return bool
     */
    public function rollback(): bool {
        return $this->pdo->rollBack();
    }

    /**
     * 执行一条sql语句,返回影响行数
     * @param $statement
     * @return int
     */
    public function exec($statement): int {
        return $this->pdo->exec($statement);
    }

    /**
     * 预处理SQL语句,防注入必备
     * @param $statement
     * @param array $driver_options
     * @return bool|\PDOStatement
     */
    public function prepare($statement, array $driver_options = []) {
        return $this->pdo->prepare($statement, $driver_options);
    }

    /**
     * 是否在事务内
     * @return bool
     */
    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }

    /**
     * 获取pdo连接对象
     * @return PDO
     */
    public function getPdo(): PDO {
        return $this->pdo;
    }

    /**
     * 上一个插入的id
     * @return string
     */
    public function lastId() {
        return $this->pdo->lastInsertId();
    }

}

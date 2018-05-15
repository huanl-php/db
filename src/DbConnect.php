<?php


namespace HuanL\Db;

use PDO;

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
        $this->pdo = new PDO($this->dns, $user, $passwd, $options);
    }


}
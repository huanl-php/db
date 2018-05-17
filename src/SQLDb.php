<?php


namespace HuanL\Db;

use HuanL\Db\Driver\MySQL\MySQLDBConnect;

/**
 * 使用SQL语句的数据库
 * @package HuanL\Db
 */
class SQLDb extends Db implements DbOperInterface {

    /**
     * sql语句
     * @var string
     */
    protected $sql = '';

    /**
     * 表前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 操作的表
     * @var string
     */
    protected $table = '';

    /**
     * 输出字段
     * @var string
     */
    protected $field = '*';

    /**
     * 条件语句
     * @var string
     */
    protected $where = '';

    /**
     * 记录最后一个PDOStatement对象
     * @var \PDOStatement
     */
    protected $pdoStatement = null;

    /**
     * 逻辑运算符
     * @var string
     */
    protected $logicOperator = '';

    /**
     * 绑定的值
     * @var array
     */
    protected $bindValue = [];

    /**
     * 映射到绑定的值
     * @var array
     */
    protected $bindMap = [];

    /**
     * 括号栈
     * @var array
     */
    protected $brackets_stack = [];

    public function __construct(DbConnect $dbConnect = null) {
        parent::__construct($dbConnect);
        if ($dbConnect instanceof PrefixInterface) {
            $this->prefix = $dbConnect->getPrefix();
        }
    }

    /**
     * 操作的表
     * @param $tables
     * @return $this
     */
    public function table($tables, string $alias = ''): Db {
        //设置要操作的表,使用试初始化其他参数的值
        $this->initParamValue();
        //对传入的参数做处理,分为数组和字符串
        if (is_array($tables)) {
            //数组则遍历数组,如果key不为数字,则为有别名的表
            foreach ($tables as $key => $value) {
                $this->table .= '`' . $this->prefix . $value . '`';
                if (is_string($key)) {
                    //无别名的表,加上前缀
                    $this->table .= ' as ' . $key;
                }
                $this->table .= ',';
            }
            //对table字符串处理一下,去掉最后面的逗号,
            $this->table = substr($this->table, 0, strlen($this->table) - 1);
        } else {
            //字符串直接设置,对tables处理一下,将:替换成表前缀
            $this->table = str_replace(':', $this->prefix, $tables) .
                (empty($alias) ? '' : " as $alias");
        }
        return $this;
    }

    /**
     * 字段
     * @return $this
     */
    public function field($fields): Db {
        //逻辑和表差不多
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                $this->field .= '`' . $value . '`';
                if (is_string($key)) {
                    $this->field .= ' as ' . $key;
                }
                $this->field .= ',';
            }
            $this->field = substr($this->field, 0, strlen($this->field) - 1);
        } else {
            $this->field = $fields;
        }
        return $this;
    }

    /**
     * 初始化值
     */
    protected function initParamValue() {
        $this->where = '';
        $this->logicOperator = '';
        $this->sql = '';
        $this->field = '*';
        $this->pdoStatement = null;
        $this->bindValue = [];
        $this->bindMap = [];
        $this->brackets_stack = [];
    }

    /**
     * 条件处理
     * @param $field
     * @param string $operator
     * @param string $value
     * @param string $logicOperator
     * @return $this
     * @throws WhereParameterException
     */
    public function where($field, $operator = '=', $value = '', $logicOperator = 'and'): DbOperInterface {
        // TODO: Implement where() method.
        //括号入栈,用来判断一个where的开始结束,好在语句的左边和右边加上括号
        //只对数组产生效果
        $isArray = is_array($field);
        if (sizeof($this->brackets_stack) == 0 && $isArray) {
            $this->internalWhere('(');
            $this->logicOperator = '';
        }
        array_push($this->brackets_stack, 1);
        //处理第一个字段,如果为数组,则就是$key=>$value的形式
        //字符串则直接相加,带上操作符
        if ($isArray) {
            foreach ($field as $key => $item) {
                //如果key是数字,则只传递item过去
                if (is_numeric($key)) {
                    //然后再对值进行判断,如果是数组,则用方法的形式调用while
                    //不是数组,将其封装成数组,调用
                    if (!is_array($item)) {
                        $item = [$item];
                    }
                    call_user_func_array([$this, 'where'], $item);
                } else {
                    $this->where($key, $operator, $item);
                }
            }
        } else if (is_string($field)) {
            //如果是字符串,判断一下有多少个参数
            //如果一个参数,就是一段完整的字符串
            //两个参数,就是字段=值的形式
            switch (func_num_args()) {
                case 1:
                    $this->internalWhere($field);
                    break;
                case 2:
                    $this->where($field, '=', $operator, $this->logicOperator);
                    break;
                case 3:
                    //三个参数,以记录中的逻辑运算符为准
                    $this->where($field, $operator, $value, $this->logicOperator);
                    break;
                case 4:
                    //防止SQL注入
                    $key = $this->internalBind($field, $value);
                    $this->logicOperator = $logicOperator;
                    $this->internalWhere("`$field` $operator :" . $key);
                    $this->bindValue($key, $value);
                    break;
                default:
                    throw new WhereParameterException('Number of parameters not allowed');
            }
        }
        array_pop($this->brackets_stack);
        if (sizeof($this->brackets_stack) == 0 && $isArray) {
            $this->logicOperator = '';
            $this->internalWhere(')');
        }
        return $this;
    }

    /**
     * 内置的where,连接where的字符串
     * @param $where
     */
    protected function internalWhere($where) {
        //连接新的where,重置运算符
        $this->where .= $this->logicOperator . ' ' . $where . ' ';
        $this->logicOperator = 'and';
    }

    /**
     * 内部用的只绑定,映射值
     * @param $key
     * @param $str
     */
    protected function internalBind(string $key, string $value): string {
        $this->bindMap[$key] = md5($key . $value);
        $this->bindValue(':' . $this->bindMap[$key], $value);
        return $this->bindMap[$key];
    }

    /**
     *
     * @return $this
     */
    public function _or(): DbOperInterface {
        // TODO: Implement _or() method.
        $this->logicOperator = 'or';
        return $this;
    }

    public function _and(): DbOperInterface {
        // TODO: Implement _and() method.
        $this->logicOperator = 'and';
        return $this;
    }

    /**
     * 搜索符合条件的记录,返回记录集
     * @return bool|RecordCollection
     */
    public function select() {
        // TODO: Implement select() method.
        //查询,先拼接语句
        $this->sql = 'select ' . $this->field . ' from ' . $this->table . ' where ' . $this->where;
        //预处理语句
        if ($pdoStatement = $this->prepare($this->sql)) {
            return new RecordCollection($pdoStatement);
        }
        return false;
    }

    /**
     * 增加一条记录
     * @param $data
     * @return int
     */
    public function insert($data): int {
        // TODO: Implement insert() method.
        //20180117好热,写得好混乱.._:(´_`」 ∠):_ ...

        //对sql语句拼接操作
        $this->sql = 'insert into ' . $this->table . ' (';
        $end = 'values(';
        foreach ($data as $key => $value) {
            $this->sql .= '`' . $key . '`,';
            $key = $this->internalBind($key, $value);
            $end .= ':' . $key . ',';
        }
        //处理最后一个逗号
        $this->sql = substr($this->sql, 0, strlen($this->sql) - 1);
        $end = substr($end, 0, strlen($end) - 1);
        $end .= ')';
        $this->sql .= ') ' . $end;
        if ($pdoStatement = $this->prepare($this->sql)) {
            return $pdoStatement->rowCount();
        }
        return false;
    }

    /**
     * 执行预处理语句,返回PDOStatement
     * @param $sql
     * @return bool|\PDOStatement
     */
    protected function prepare($sql) {
        $pdoStatement = null;
        if (!($pdoStatement = $this->dbConnect->prepare($sql))) {
            //失败直接返回false
            return false;
        }
        //执行sql语句
        if ($pdoStatement->execute($this->bindValue)) {
            return $this->pdoStatement = $pdoStatement;
        }
        return false;
    }


    /**
     * 再执行一次上一次的操作
     * @param array $values
     * @return RecordCollection
     */
    public function again(array $values): RecordCollection {
        //遍历映射绑定的值
        foreach ($this->bindMap as $key => $value) {
            //如果这个值存在于映射中,那么判断是否在values中存在
            if (isset($this->bindMap[$key])) {
                if (isset($values[$key])) {
                    //如果values中存在,使用unset删除掉这个值,值设置为新的
                    $values[$this->bindMap[$key]] = $values[$key];
                    unset($values[$key]);
                } else {
                    //不存在则设置为原来的值
                    $values[$this->bindMap[$key]] = $this->bindValue[':' . $this->bindMap[$key]];
                }
            }
        }
        //再执行一次
        if ($this->pdoStatement->execute($values)) {
            return new RecordCollection($this->pdoStatement);
        }
        return false;
    }

    public function find() {
        // TODO: Implement find() method.
    }

    public function delete(): int {
        // TODO: Implement delete() method.
    }

    public function update($data): int {
        // TODO: Implement update() method.
    }

    /**
     * 绑定一个值
     * @param $key
     * @param $value
     * @return $this
     */
    public function bindValue($key, $value): Db {
        $this->bindValue[$key] = $value;
        return $this;
    }

}
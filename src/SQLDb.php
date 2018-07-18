<?php


namespace HuanL\Db;

/**
 * Class SQLDb
 * @package HuanL\Db
 */
class SQLDb extends Db {

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
    protected $field = '';

    /**
     * 联合查询
     * @var string
     */
    protected $join = '';

    /**
     * 条件语句
     * @var string
     */
    protected $where = '';

    /**
     * 分页
     * @var string
     */
    protected $limit = '';

    /**
     * 排序
     * @var string
     */
    protected $order = '';

    /**
     * 分组
     * @var string
     */
    protected $group = '';

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
     * 绑定的参数
     * @var array
     */
    protected $bindParam = [];

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
    public function table($tables, string $alias = ''): DbOperInterface {
        //设置要操作的表,使用试初始化其他参数的值
        $this->initParamValue();
        //对传入的参数做处理,分为数组和字符串
        if (is_array($tables)) {
            //数组则遍历数组,如果key不为数字,则为有别名的表
            foreach ($tables as $key => $value) {
                if (is_numeric($key)) {
                    //数字,没有别名
                    $this->table .= '`' . $this->dealTable($value) . '`';
                } else {
                    //不是数字,有别名的表,自动加上前缀
                    $this->table .= '`' . $this->prefix . $key . '` as ' . $value;
                }
                $this->table .= ',';
            }
            //对table字符串处理一下,去掉最后面的逗号,
            $this->dealGarbage($this->table, 1);
        } else {
            //字符串直接设置,对tables处理一下,将:替换成表前缀
            $this->table = '`' . $this->dealTable($tables) . '`' .
                (empty($alias) ? '' : " as $alias");
        }
        return $this;
    }

    /**
     * 处理表
     * @param string $table
     * @return string
     */
    protected function dealTable(string &$table): string {
        //一个冒号替换为表前缀,两个冒号则当做没有表前缀处理
        if ($table[0] == ':') {
            if ($table[1] == ':') {
                //两个冒号
                $table = substr($table, 2);
            } else {
                $table = $this->prefix . substr($table, 1);
            }
        } else {
            //没有冒号
            $table = $this->prefix . $table;
        }
        return $table;
    }

    /**
     * 处理字段
     * @param string $field
     * @return string
     */
    protected function dealField(string &$field): string {
        return $field = str_replace('.', '`.`', $field);
    }

    /**
     * 处理垃圾
     * @param string $string
     * @param int $len
     * @return string
     */
    protected function dealGarbage(string &$string, int $len): string {
        return $string = substr($string, 0, strlen($string) - $len);
    }

    /**
     * 设置返回的字段
     * @param $fields
     * @param string $alias
     * @return $this
     */
    public function field($fields, string $alias = ''): DbOperInterface {
        //逻辑和表差不多
        if (is_array($fields)) {
            foreach ($fields as $key => $value) {
                if (is_numeric($key)) {
                    //如果是数字,就是没有别名的字段
                    $this->field .= '`' . $this->dealField($value) . '`';
                } else {
                    //key对value的形式
                    $this->field .= '`' . $this->dealField($key) . '` as ' . $value;
                }
                $this->field .= ',';
            }
            $this->dealGarbage($this->field, 1);
        } else {
            $this->field .= (empty($this->field) ? '' : ',') . $fields;
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
        $this->join = '';
        $this->field = '';
        $this->order = '';
        $this->group = '';
        $this->limit = '';
        $this->pdoStatement = null;
        $this->bindValue = [];
        $this->bindParam = [];
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
                    //两个参数,判断$operator是否为数组,绑定到参数
                    if (is_array($operator)) {
                        $this->dealSqlBindValue($field, $operator);
                        $this->where($field);
                    } else {
                        $this->where($field, '=', $operator, $this->logicOperator);
                    }
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
     * 处理sql,绑定值
     * @param $bindValue
     */
    protected function dealSqlBindValue($sql, $bindValue) {
        $this->bindValue = array_merge($this->bindValue, $bindValue);
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
        $this->bindMap[$key] = 'internalBindValue_' . sizeof($this->bindValue);
        $this->bindValue($this->bindMap[$key], $value);
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
        $this->sql = 'select ' . (empty($this->field) ? '*' : $this->field) . ' from ' . $this->table .
            ' ' . $this->join . (empty($this->where) ? ' ' : 'where' . $this->where) . $this->group .
            $this->order . $this->limit;
        //预处理语句
        if ($pdoStatement = $this->prepare($this->sql)) {
            return new RecordCollection($pdoStatement);
        }
        return false;
    }

    /**
     * 链接表
     * @param $tables
     * @param string $alias
     * @param string $on
     * @param string $type
     * @return $this
     */
    public function join($tables, $alias = '', $on = '', $type = 'inner'): DbOperInterface {
        //不能重载,只能用这些麻烦的方法来实现类似重载的效果了
        if (is_array($tables)) {
            //数组,$key=>$value
            foreach ($tables as $key => $value) {
                if (is_numeric($key)) {
                    //key为数字,判断$value是不是数组,不是数组封装成数组的形式
                    //递归调用本方法
                    if (is_array($value)) {
                        $value = [$value];
                    }
                } else {
                    //如果key是字符串,再判断值是否为数组
                    //如果value是数组,则将key设置为第一个参数,往后推
                    if (is_array($value)) {
                        array_unshift($value, $key);
                    } else {
                        $value = [$key, $value];
                    }
                }
                call_user_func_array([$this, 'join'], $value);
            }
        } else {
            if (func_num_args() == 2) {
                //两个参数的情况下,$alias参数为on
                $on = $alias;
                $alias = '';
            }
            $this->join .= $type . ' join `' . $this->dealTable($tables) . '`' .
                (empty($alias) ? '' : ' as ' . $alias) . ' on ';
            //处理on
            if (is_array($on)) {
                foreach ($on as $key => $value) {
                    if (is_numeric($key)) {
                        $value = $this->internalBind($key, $value);
                        $this->join .= '`' . $this->dealField($key) . '` = :' . $value;
                    } else {
                        $this->join .= $value;
                    }
                    $this->join .= ' and ';
                }
                $this->dealGarbage($this->join, 5);
            } else {
                //字符串直接连接
                $this->join .= $on;
            }
            $this->join .= ' ';
        }
        return $this;
    }

    /**
     * 分组
     * @param $fields
     * @return $this
     */
    public function group(string $fields): DbOperInterface {
        //用到的地方很少,直接的只处理列
        $this->group = 'group by ' . $this->dealField($fields) . ' ';
        return $this;
    }

    /**
     * 增加一条记录
     * @param $data
     * @return int
     */
    public function insert($data): int {
        // TODO: Implement insert() method.
        //对sql语句拼接操作
        $this->sql = 'insert into ' . $this->table;
        $end = ' values';
        if (empty($this->field)) {
            //如果列名是空的,那么就认为data中的key为列名
            //判断有没有key(key为字符串),没有的话就不加列名的参数
            $mode = 0;
            if (is_string(key($data))) {
                $mode = 1;
                $this->sql .= '(';
            }
            $end .= '(';
            foreach ($data as $key => $value) {
                if ($mode) {
                    $this->sql .= '`' . $key . '`,';
                }
                $value = $this->internalBind($key, $value);
                $end .= ':' . $value . ',';
            }
            //处理最后一个逗号
            $this->dealGarbage($end, 1);
            if ($mode) {
                $this->dealGarbage($this->sql, 1);
                $this->sql .= ') ';
            }
            $end .= ')';
        } else {
            //列名不为空,那么不对key进行识别,直接按照多条记录处理
            //对于不正确的抛出异常
            $this->sql .= '(' . $this->field . ') ';
            foreach ($data as $key => $item) {
                $end .= '(';
                foreach ($item as $value) {
                    $value = $this->internalBind($key, $value);
                    $end .= ':' . $value . ',';
                }
                $this->dealGarbage($end, 1);
                $end .= '),';
            }
            $this->dealGarbage($end, 1);
        }
        $this->sql .= $end;
        //执行sql语句
        if ($pdoStatement = $this->prepare($this->sql)) {
            return $pdoStatement->rowCount();
        }
        return false;
    }

    /**
     * 符合条件的数量
     * @return int
     */
    public function count(): int {
        $tmp_field = $this->field;
        $this->field('count(*)');
        $count = $this->find();
        $this->field = $tmp_field;
        return $count['count(*)'];
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
        //两者冲突
        if (count($this->bindParam)) {
            foreach ($this->bindParam as $key => $value) {
                $pdoStatement->bindParam($key, $this->bindParam[$key]);
            }
            if ($pdoStatement->execute()) {
                return $this->pdoStatement = $pdoStatement;
            }
        } else {
            if ($pdoStatement->execute($this->bindValue)) {
                return $this->pdoStatement = $pdoStatement;
            }
        }
        return false;
    }

    /**
     * 再执行一次上一次的操作
     * @param array $values
     * @return RecordCollection
     */
    public function again(array $values = []): RecordCollection {
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
                    $values[$this->bindMap[$key]] = $this->bindValue[$this->bindMap[$key]];
                }
            }
        }
        //再执行一次
        if ($this->pdoStatement->execute($values)) {
            return new RecordCollection($this->pdoStatement);
        }
        return null;
    }

    /**
     * 排序
     * @param $fields
     * @param string $mode
     * @return $this
     */
    public function order($fields, string $mode = 'desc'): DbOperInterface {
        $this->order = 'order by ';
        //判断字段是否为数组
        if (is_array($fields)) {
            //遍历数组
            foreach ($fields as $key => $value) {
                //通过判断key来判断这一项是否为key=>value的形式
                if (is_numeric($key)) {
                    //如果key是数字,则直接加入
                    $this->order .= $this->dealField($value);
                } else {
                    $this->order .= $this->dealField($key) . ' ' . $value;
                }
                $this->order .= ',';
            }
            $this->dealGarbage($this->order, 1);
        } else {
            //如果是字符串,直接接入
            $this->order .= $this->dealField($fields) . (empty($mode) ? '' : ' ' . $mode);
        }
        $this->order .= ' ';
        return $this;
    }

    /**
     * 查询一条记录,直接返回数据
     * @return array|bool
     */
    public function find() {
        // TODO: Implement find() method.
        $tmp_limit = $this->limit;
        $this->limit = 'limit 1';
        $tmpPDOStatement = $this->select();
        $this->limit = $tmp_limit;
        return $tmpPDOStatement->fetch();
    }

    /**
     *
     * @param int $start
     * @param int $length
     * @return $this
     */
    public function limit(int $start, int $length = 0): DbOperInterface {
        $this->limit = 'limit ' . $start . (empty($length) ? '' : ',' . $length) . ' ';
        return $this;
    }

    /**
     * 删除符合条件的
     * @return int
     */
    public function delete(): int {
        // TODO: Implement delete() method.
        $this->sql = 'delete from ' . $this->table . ' where ' . $this->where . $this->limit;
        if ($this->pdoStatement = $this->prepare($this->sql)) {
            return $this->pdoStatement->rowCount();
        }
        return false;
    }

    /**
     * 更新符合条件的记录
     * @param $data
     * @return int
     */
    public function update($data): int {
        // TODO: Implement update() method.
        $this->sql = 'update ' . $this->table . ' set ';
        //先遍历更新的值,加入绑定的value中
        foreach ($data as $key => $value) {
            $value = $this->internalBind($key, $value);
            $this->sql .= '`' . $this->dealField($key) . '` = :' . $value . ',';
        }
        $this->dealGarbage($this->sql, 1);
        $this->sql .= ' where ' . $this->where . $this->limit;
        if ($this->pdoStatement = $this->prepare($this->sql)) {
            return $this->pdoStatement->rowCount();
        }
        return false;
    }

    /**
     * 直接执行一条sql语句,返回记录集
     * @param $sql
     * @return RecordCollection
     */
    public function query($sql) {
        $this->sql = $sql;
        if ($pdoStatement = $this->dbConnect->query($sql)) {
            return new RecordCollection($this->pdoStatement = $pdoStatement);
        }
        return false;
    }

    /**
     * 执行一条sql语句,返回变化条数
     * @param $sql
     * @return int
     */
    public function exec($sql): int {
        $this->sql = $sql;
        return $this->dbConnect->exec($sql);
    }

    /**
     * 绑定一个值
     * @param $key
     * @param $value
     * @return $this
     */
    public function bindValue($key, $value): DbOperInterface {
        if (func_num_args() == 1) {
            $this->bindValue[] = $key;
        } else {
            $this->bindValue[$key] = $value;
        }
        return $this;
    }

    /**
     * 绑定变量
     * @param $key
     * @param $value
     * @return $this
     */
    public function bindParam($key, &$value): DbOperInterface {
        $this->bindParam[$key] = $value;
        return $this;
    }

}
<?php


namespace HuanL\Db;

/**
 * 使用SQL语句的数据库
 * @package HuanL\Db
 */
class DbSQL extends Db implements DbOperInterface {

    /**
     * sql语句
     * @var string
     */
    protected $sql = '';

    /**
     * 条件语句
     * @var string
     */
    protected $where = '';

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
     * 括号栈
     * @var array
     */
    protected $brackets_stack = [];

    /**
     * @param $field
     * @param string $operator
     * @param string $value
     * @return Db
     */
    public function where($field, $operator = '=', $value = '', $logicOperator = 'and'): Db {
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
                    //四个参数,绑定值md5($field.$operator.$value.$logicOperator)
                    //防止SQL注入
                    $key = md5($field . $operator . $value . $logicOperator);
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


    public function _or(): Db {
        // TODO: Implement _or() method.
        $this->logicOperator = 'or';
        return $this;
    }

    public function _and(): Db {
        // TODO: Implement _and() method.
        $this->logicOperator = 'and';
        return $this;
    }

    public function select() {
        // TODO: Implement select() method.
    }

    public function insert($data): int {
        // TODO: Implement insert() method.
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
     * @return Db
     */
    public function bindValue($key, $value): Db {
        $this->bindValue[$key] = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getWhere(): string {
        return $this->where;
    }
}
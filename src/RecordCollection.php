<?php


namespace HuanL\Db;

use PDO;
use PDOStatement;
use ArrayAccess;

/**
 * 查询结果记录集类
 * Class RecordCollection
 * @method bool bindColumn(mixed $column, mixed &$param, int $type, int $maxlen, mixed $driverdata)
 * @method bool bindParam(mixed $parameter, mixed &$variable, int $data_type = PDO::PARAM_STR, int $length, mixed $driver_options)
 * @method bool bindValue(mixed $parameter, mixed $value, int $data_type = PDO::PARAM_STR)
 * @method bool closeCursor()
 * @method int columnCount()
 * @method bool debugDumpParams()
 * @method bool errorCode()
 * @method bool errorInfo()
 * @method bool execute(array $input_parameters)
 * @method mixed fetch(int $fetch_style = PDO::FETCH_BOTH, int $cursor_orientation = PDO::FETCH_ORI_NEXT, int $cursor_offset = 0)
 * @method array fetchAll(int $fetch_style = PDO::ATTR_DEFAULT_FETCH_MODE, mixed $fetch_argument = PDO::FETCH_COLUMN, array $ctor_args = array())
 * @method string fetchColumn(int $column_number = 0)
 * @method mixed fetchObject(string $class_name = "stdClass", array $ctor_args)
 * @method mixed getAttribute(int $attribute)
 * @method array getColumnMeta(int $column)
 * @method bool nextRowset()
 * @method int rowCount()
 * @method bool setAttribute(int $attribute, mixed $value)
 * @method bool setFetchMode (int $mode, $arg1, $arg2)
 * @package HuanL\Db
 */
class RecordCollection implements ArrayAccess {

    /**
     * PDOStatement对象
     * @var PDOStatement
     */
    protected $statement = null;

    /**
     * 记录当前的结果
     * @var array
     */
    protected $row = [];

    public function __construct(PDOStatement $statement) {
        $this->statement = $statement;
    }

    /**
     * 读取记录
     * @param int $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     * @return mixed
     */
    public function read($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0) {
        return $this->row = $this->statement->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }

    /**
     * 调用PDOStatement中的方法
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws MethodExistException
     */
    public function __call($name, $arguments) {
        // TODO: Implement __call() method.
        if (method_exists($this->statement, $name)) {
            return call_user_func_array([$this->statement, $name], $arguments);
        }
        throw  new MethodExistException('Did not find the corresponding method!');
    }

    /**
     * @return PDOStatement
     */
    public function getStatement(): PDOStatement {
        return $this->statement;
    }

    public function offsetExists($offset) {
        // TODO: Implement offsetExists() method.
        return isset($this->row[$offset]);
    }

    public function offsetGet($offset) {
        // TODO: Implement offsetGet() method.
        return $this->row[$offset];
    }

    public function offsetSet($offset, $value) {
        // TODO: Implement offsetSet() method.
        $this->row[$offset] = $value;
    }

    public function offsetUnset($offset) {
        // TODO: Implement offsetUnset() method.
        unset($this->row[$offset]);
    }
}
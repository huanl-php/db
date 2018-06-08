<?php


namespace HuanL\Db;

/**
 * 数据库操作接口,基本的数据库操作方式
 * CURD创建,更新,读取,删除
 * @package HuanL\Db
 */
interface DbOperInterface {

    /**
     * 绑定值
     * @param $key
     * @param $value
     * @return DbOperInterface
     */
    public function bindValue($key, $value): DbOperInterface;

    /**
     * 返回字段
     * @param $fields
     * @param string $alias
     * @return Db
     */
    public function field($fields, string $alias = ''): DbOperInterface;

    /**
     * 排序
     * @param $fields
     * @param string $mode
     * @return Db
     */
    public function order($fields, string $mode = 'desc'): DbOperInterface;

    /**
     * 分页
     * @param int $start
     * @param int $length
     * @return Db
     */
    public function limit(int $start, int $length = 0): DbOperInterface;

    /**
     * 在执行一次上次操作
     * @param array $values
     * @return RecordCollection
     */
    public function again(array $values = []): RecordCollection;

    /**
     * 条件
     * @param $field
     * @param $operator
     * @param $value
     * @return Db
     */
    public function where($field, $operator, $value): DbOperInterface;

    /**
     * or运算符
     * @return Db
     */
    public function _or(): DbOperInterface;

    /**
     * and运算符
     * @return Db
     */
    public function _and(): DbOperInterface;

    /**
     * 插入数据
     * @param $data
     * @return int
     */
    public function insert($data): int;

    /**
     * 更新数据
     * @param $data
     * @return int
     */
    public function update($data): int;

    /**
     * 读取出符合条件的所有数据
     * @return mixed
     */
    public function select();

    /**
     * 读取出符合条件的第一条数据
     * @return mixed
     */
    public function find();

    /**
     * 删除符合条件的数据
     * @return int
     */
    public function delete(): int;

    /**
     * 执行返回记录集
     * @return RecordCollection
     */
    public function query($action): RecordCollection;

    /**
     * 执行,返回变化条数
     * @return int
     */
    public function exce($action): int;

}
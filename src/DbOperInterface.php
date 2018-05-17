<?php


namespace HuanL\Db;

/**
 * 数据库操作接口,基本的数据库操作方式
 * CURD创建,更新,读取,删除
 * @package HuanL\Db
 */
interface DbOperInterface {

    /**
     * 条件
     * @param $field
     * @param $operator
     * @param $value
     * @return Db
     */
    public function where($field, $operator = '=', $value = ''): DbOperInterface;

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


}
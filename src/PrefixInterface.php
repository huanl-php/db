<?php


namespace HuanL\Db;


/**
 * Interface PrefixInterface
 * 有表前缀的接口
 * @package HuanL\Db
 */
interface PrefixInterface {

    /**
     * 设置表前缀
     * @param string $Prefix
     * @return mixed
     */
    public function setPrefix(string $Prefix);

    public function getPrefix(): string;
}
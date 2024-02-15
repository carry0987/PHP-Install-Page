<?php
namespace carry0987\Template\Controller;

use carry0987\Redis\RedisTool;

class RedisController
{
    private $redis = null;
    private $hash = 'tpl';

    public function __construct(mixed $redisConfig)
    {
        if ($redisConfig instanceof RedisTool) {
            $this->redis = $redisConfig;
        }
        if ($this->redis === null && is_array($redisConfig)) {
            $this->redis = new RedisTool($redisConfig['host']);
        }

        return $this;
    }

    public function isConnected()
    {
        return $this->redis !== null;
    }

    public function setHashName(string $hash)
    {
        $this->hash = $hash;

        return $this;
    }

    public function getVersion(string $get_tpl_path, string $get_tpl_name, string $get_tpl_type)
    {
        if ($this->redis === null) return false;
        $tpl_key = "template::$get_tpl_path::$get_tpl_name::$get_tpl_type";
        $result = $this->redis->getHashValue($this->hash, $tpl_key);
        if (!empty($result)) {
            return unserialize($result);
        }

        return false;
    }

    public function createVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_md5, int $tpl_expire_time, string $tpl_verhash)
    {
        if ($this->redis === null) return false;
        $tpl_key = "template::$tpl_path::$tpl_name::$tpl_type";
        $tpl_data = [
            "tpl_md5" => $tpl_md5,
            "tpl_expire_time" => $tpl_expire_time,
            "tpl_verhash" => $tpl_verhash,
        ];

        return $this->redis->setHashValue($this->hash, $tpl_key, serialize($tpl_data));
    }

    public function updateVersion(string $tpl_path, string $tpl_name, string $tpl_type, string $tpl_md5, int $tpl_expire_time, string $tpl_verhash)
    {
        return $this->createVersion($tpl_path, $tpl_name, $tpl_type, $tpl_md5, $tpl_expire_time, $tpl_verhash);
    }

    public function getTemplateByMd5(string $tpl_md5)
    {
        $tpl_id = $this->redis->getValue('tpl_md5:'.$tpl_md5);
        if ($tpl_id) {
            $tpl_data = $this->redis->getHashValue('template', 'template:'.$tpl_id);
            return $tpl_data ? unserialize($tpl_data) : null;
        }

        return null;
    }
}

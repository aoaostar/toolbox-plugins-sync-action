<?php

namespace aoaostar;

use app\Cloud;
use Exception;

class App
{
    /**
     * @var array|false
     */
    private $config;

    public function parse_flags()
    {

        $flags = getopt('c:', ['config:']);

        $configFile = __ROOT_PATH__ . '/config.ini';
        if (isset($flags['c'])) {
            $configFile = $flags['c'];
        }
        if (isset($flags['config'])) {
            $configFile = $flags['config'];
        }

        $this->config = parse_ini_file($configFile, true);

    }

    public function run()
    {
        try {

            $this->parse_flags();

            $cloud = new Cloud($this->config);

            $collections = $cloud->collect(__PLUGIN_PATH__);

            $cloud->sync($collections);
            $result = $cloud->getResult();
            $count = count($collections);
            info("共计插件 {$count}个, 新增 {$result->created} 个, 更新 {$result->updated} 个, 失败 {$result->failed} 个");
            info('同步插件完成~');

        } catch (Exception $e) {
            error($e->getMessage());
        }

    }
}
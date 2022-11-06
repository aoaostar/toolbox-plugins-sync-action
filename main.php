<?php


use aoaostar\App;

const __ROOT_PATH__ = __DIR__;
const __PLUGIN_PATH__ = __ROOT_PATH__ . '/src/plugin';

require __DIR__ . "/vendor/autoload.php";

require __DIR__ . "/src/common.php";

(new App())->run();
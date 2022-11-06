<?php


function dump(...$args)
{
    $message = '';
    $message .= date('[Y-m-d h:i:s] ');
    foreach ($args as $arg) {
        if (is_string($arg)) {
            $message .= $arg . ' ';
            continue;
        }
        $message .= json_encode($arg, JSON_UNESCAPED_UNICODE) . ' ';
    }
    $message = trim($message) . "\n";
    echo $message;

}

function dd(...$args)
{
    dump(...$args);
    die();
}

function error(...$args)
{
    dump('[error]', ...$args);
}

function warn(...$args)
{
    dump('[warn]', ...$args);
}

function info(...$args)
{
    dump('[info]', ...$args);
}


//当前命名空间的包名
function base_space_name($space)
{
    $str_replace = str_replace('\\', '/', $space);
    return basename($str_replace);
}

function plugin_current_class_get($namespace)
{
    return str_replace('plugin\\', '', $namespace);
}

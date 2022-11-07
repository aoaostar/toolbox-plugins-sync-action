<?php

namespace app;

use app\model\Plugin;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class Cloud
{
    private $cloudPlugins = [];
    private $client;
    private $config;
    private $result = [
        'created' => 0,
        'updated' => 0,
        'failed' => 0,
    ];
    const MAX_RETRY = 3;
//    const BASE_URL = 'http://tool-cloud.test';
    const BASE_URL = 'https://tool-cloud.aoaostar.com';

    /**
     * @throws Exception
     */
    public function __construct($options)
    {
        $this->result = json_decode(json_encode($this->result));
        $this->config = json_decode(json_encode($options));
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(function ($retries, Request $request, Response $response = null, $exception = null) {
            if ($retries >= self::MAX_RETRY) {
                return false;
            }
            // 请求失败, 继续重试
            if ($exception instanceof ConnectException) {
                warn("请求失败, 第{$retries}次重试 [" . $request->getMethod() . '][' . $request->getUri() . ']');
                return true;
            }
            if ($response) {
                // 如果请求有响应, 但是状态码大于等于500
                if ($response->getStatusCode() >= 500) {
                    warn('状态码[' . $response->getStatusCode() . "]异常, 第{$retries}次重试 [" . $request->getMethod() . '][' . $request->getUri() . ']');
                    return true;
                }
            }

            return false;
        }, function () {
            return 1000;
        }));

        $this->client = new Client([
            'base_uri' => self::BASE_URL,
            'headers' => [
                'authorization' => 'Bearer ' . $this->config->{'tool-cloud'}->token,
                'x-requested-with' => 'XMLHttpRequest',
            ],
            'handler' => $stack
        ]);
        info("从云端获取插件中 [" . self::BASE_URL . "]");
        $this->cloudPlugins = $this->getPlugins();
        info("共计云端插件 " . count($this->cloudPlugins) . " 个");
    }

    public function collect($pluginPath): array
    {

        $glob = glob($pluginPath . '/*');
        $spaces = [];
        foreach ($glob as $v) {
            if (is_dir($v)) {
                $spaces[] = basename($v);
            }
        }
        $plugins = [];
        foreach ($spaces as $space) {
            $arr = glob("{$pluginPath}/$space/*");
            foreach ($arr as $v) {
                $plugins[] = "$space\\" . basename($v);
            }

        }
        // 获取插件信息
        $collections = [];
        foreach ($plugins as $plugin) {
            $class = "\\plugin\\$plugin\\Install";
            $model = new Plugin();
            (new $class())->Install($model);
            $collections[] = $model;
        }

        // 合并更新、新增的插件
        foreach ($collections as &$plugin) {
            foreach ($this->cloudPlugins as $cloudPlugin) {
                if ($plugin->class === $cloudPlugin->class) {
                    $plugin->id = $cloudPlugin->id;
                    break;
                }
            }
        }
        return $collections;

    }

    public function sync($plugins): array
    {
        foreach ($plugins as $plugin) {
            try {
                if (!empty($plugin->id)) {
                    // 更新插件
                    $this->updatePlugin($plugin->id, $plugin);
                    info("[$plugin->title][$plugin->class] 更新插件成功 [{$plugin->id}]");
                    $this->result->updated++;
                    continue;
                }
                // 新增插件
                $data = [
                    "title" => $plugin->title,
                    "category_id" => $this->config->{'tool-cloud'}->category_id,
                    "desc" => $plugin->desc,
                    "class" => $plugin->class,
                    "version" => $plugin->version,
                    "news" => '',
                    "help" => '',
                    "link" => $this->config->{'tool-cloud'}->link,
                    "user" => [
                        "username" => $this->config->{'tool-cloud'}->username,
                    ],
                    "file" => [
                        'owner' => $this->config->github->owner,
                        'repo' => $this->config->github->repo,
                        'branch' => $this->config->github->branch,
                        "path" => $this->config->github->path,
                    ]
                ];
                $flags = [
                    '{space}' => explode('\\', $plugin->class)[0],
                    '{alias}' => $plugin->alias,
                ];
                foreach ($flags as $k => $v) {
                    $data['link'] = str_ireplace($k, $v, $data['link']);
                    $data['file']['path'] = str_ireplace($k, $v, $data['file']['path']);
                }
                
                $this->createPlugin($data);
                info("[$plugin->title][$plugin->class] 新增插件成功");
                $this->result->created++;
            } catch (GuzzleException|Exception $e) {
                warn("[$plugin->title][$plugin->class] " . $e->getMessage());
                $this->result->failed++;

            }
        }
        return $plugins;
    }

    /**
     * @throws Exception
     */
    private function getPlugins(): array
    {
        try {
            $response = $this->client->request('GET', '/api/plugins');
            $stream = $response->getBody()->getContents();
            $resp = json_decode($stream);

            if ($resp->status === 'ok') {
                return $resp->data->items;
            }

        } catch (GuzzleException $e) {
            throw new Exception('获取云端插件列表失败: ' . $e->getMessage());
        }
        return [];

    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function updatePlugin($id, $plugin): void
    {
        $response = $this->client->put('/api/plugin', [
                'json' => array_merge((array)$plugin, [
                    'id' => $id,
                ]),
            ]
        );
        $contents = $response->getBody()->getContents();
        $resp = json_decode($contents);
        if ($resp->status === 'ok') {
            return;
        }
        if ($resp->message) {
            throw new Exception($resp->message);
        }
        throw new Exception('未知异常');

    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function createPlugin($plugin): void
    {
        $response = $this->client->post('/api/plugin', [
                'json' => $plugin,
            ]
        );
        $contents = $response->getBody()->getContents();
        $resp = json_decode($contents);
        if ($resp->status === 'ok') {
            return;
        }
        if ($resp->message) {
            throw new Exception($resp->message);
        }
        throw new Exception('未知异常');
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
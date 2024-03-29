<?php

/*
 * This file is part of the pkg6/easy-im.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Pkg6\easyIm\Huanxin\Request;

use GuzzleHttp\Exception\GuzzleException;
use Pkg6\easyIm\Kernel\BaseClient;
use Pkg6\easyIm\Kernel\Support\Str;

/**
 *  环信即时通讯云
 * https://www.easemob.com/
 * https://docs-im.easemob.com/im/start
 * $config = [
 *  'appKey' => '',
 *  'clientId' => '',
 *  'clientSecret' => '',
 *  'orgName' => '',
 *  'appName' => '',
 * ];
 * Class HuanxinClient.
 */
class HuanxinClient extends BaseClient
{
    /**
     * @var string
     */
    public static $host = 'https://a1.easemob.com';

    /**
     * @param string $method
     * @param string $action
     * @param array  $params
     * @param bool   $headerVerify
     *
     * @throws GuzzleException
     *
     * @return string
     */
    public function send(string $method, string $action, array $params = [], $headerVerify = true)
    {
        $url = $this->buildHost() . Str::removeFristSlash($action);
        $options = [];
        if ($headerVerify) {
            $options['headers'] = $this->buildHeaders();
        }
        $method = strtoupper($method);
        if ($method == 'GET') {
            $options['query'] = $params;
        } elseif ($method == 'POST') {
            $options['json'] = $params;
        } elseif ($method == 'PUT') {
            $options['body'] = json_encode($params);
        }
        $this->config['http']['http_errors'] = false;

        return $this->httpRequest($method, $url, $options);
    }

    /**
     * @throws GuzzleException
     *
     * @return string
     */
    public function getToken()
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['clientId'],
            'client_secret' => $this->config['clientSecret'],
        ];

        return $this->send('post', 'token', $params, false);
    }

    /**
     * @return string
     */
    protected function buildHost()
    {
        return HuanxinClient::$host . '/' . $this->config['orgName'] . '/' . $this->config['appName'] . '/';
    }

    /**
     * @throws GuzzleException
     *
     * @return array
     */
    protected function buildHeaders()
    {
        $cahceKey = md5('client_credentials' .
            $this->config['appKey'] .
            $this->config['clientId'] .
            $this->config['clientSecret']);
        if ( ! $this->app->cache->hasCache($cahceKey)) {
            $tokens = $this->getToken();
            $tokensArr = json_decode($tokens, true);
            $this->app->cache->setCache($cahceKey, $tokensArr['access_token'], $tokensArr['expires_in']);
        }
        $token = $this->app->cache->getCache($cahceKey);

        return [
            'Authorization' => "Bearer {$token}",
        ];
    }
}

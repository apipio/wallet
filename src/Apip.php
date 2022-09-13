<?php

namespace Apip\Wallet;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Apip

{
    public $client;

    public $app_id = "";
    public $app_key = "";

    public function __construct($uri = '')
    {
        if ($uri == '') {
            $uri = config('services.apip.uri');
        }

        $this->app_id = config('services.apip.app_id');
        $this->app_key = config('services.apip.app_key');

        $this->client = Http::retry(3, 3000)->withOptions([
            'verify' => false,
            'headers' => [
                'User-Agent' => 'APIP',
            ],
            'base_uri' => $uri,
            // 'timeout' => 10,
            // 'connect_timeout' => 10,
            // 'debug' => true,
        ]);
    }


    /**
     * 签名算法
     *
     * @param  [type] $params  [description]
     * @param  [type] $app_key [description]
     * @return [type]          [description]
     */
    public function sign(array $params, string $app_key)
    {
        unset($params['app_key'], $params['sign'], $params['sign_type']);
        ksort($params);
        $params = array_filter($params);
        $md5_sign = md5(urldecode(http_build_query($params)) . $app_key);

        return strtoupper($md5_sign);
    }

    public function validate(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string',
            'from' => 'required|string',
            'amount' => 'required|numeric',
            'to' => 'required|string',
            'chain_name' => 'required|string',
            'token_name' => 'required|string',
            'sign' => 'required|string',
        ]);

        if ($request->app_id == config('services.apip.app_id') and !sign($request->all(), config('services.apip.app_key'))) {
            throw new Exception('签名不正确');
        }
    }

    /**
     * 创建钱包
     */
    public function create(string $label, string $symbol = '')
    {
        $params = [
            'app_id' => $this->app_id,
            'label' => $label,
            'symbol' => $symbol,
        ];
        $params['sign'] = $this->sign($params, $this->app_key);
        $response = $this->client->post('create.wallet', $params);

        return $this->handle($response);
    }

    /**
     * 查询余额
     */
    public function balance(string $label, string $address = '')
    {
        $params = [
            'app_id' => $this->app_id,
            'label' => $label,
            'address' => $address,
        ];
        $params['sign'] = $this->sign($params, $this->app_key);
        $response = $this->client->post('wallet.balance', $params);

        return $this->handle($response);
    }

    /**
     * 导入钱包
     */
    public function import(string $chain, string $label, string $address, string $private_key)
    {
        $params = [
            'app_id' => $this->app_id,
            'label' => $label,
            'chain' => $chain,
            'address' => $address,
            'private_key' => $private_key,
        ];
        $params['sign'] = $this->sign($params, $this->app_key);
        $response = $this->client->post('import.wallet', $params);

        return $this->handle($response);
    }

    /**
     * 提现钱包
     */
    public function withdraw($amount, $to, string $symbol, $from = null)
    {
        $params = [
            'app_id' => $this->app_id,
            'symbol' => $symbol,
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
        ];
        $params['sign'] = $this->sign($params, $this->app_key);
        $response = $this->client->post('send.wallet', $params);

        return $this->handle($response);
    }

    /**
     * 汇总数据
     */
    public function data(string $symbol)
    {
        $params = [
            'app_id' => $this->app_id,
            'symbol' => $symbol,
        ];
        $params['sign'] = $this->sign($params, $this->app_key);
        $response = $this->client->post('wallet.data', $params);

        return $this->handle($response);
    }

    /**
     * 汇总钱包
     */
    public function collect(string $symbol = 'bsc20_usdt,trc20_usdt')
    {
        $params = [
            'app_id' => $this->app_id,
            'symbol' => $symbol,
        ];
        $params['sign'] = $this->sign($params, $this->app_key);
        $response = $this->client->post('collect.wallet', $params);

        // $response->dump();

        return $this->handle($response);
    }

    /**
     * 处理数据
     */
    public function handle(Response $response)
    {
        return $response->json();
    }
}

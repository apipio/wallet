<?php

namespace Apip\Wallet;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class Apip
{
    public $client;

    public $app_id = '';

    public $app_key = '';

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
                'User-Agent' => config('app.name'),
            ],
            'base_uri' => $uri,
            // 'timeout' => 10,
            // 'connect_timeout' => 10,
            // 'debug' => true,
        ]);
    }

    /**
     * 签名算法
     */
    public function sign(array $params, string $app_key): string
    {
        unset($params['app_key'], $params['sign'], $params['sign_type']);
        ksort($params);
        $params = array_filter($params);
        $md5_sign = md5(urldecode(http_build_query($params)).$app_key);

        return strtoupper($md5_sign);
    }

    /**
     * 验证请求
     *
     * @return void
     */
    public function validate(Request $request)
    {
        $request->validate([
            'app_id' => 'required|string',
            'from' => 'required|string',
            'amount' => 'nullable|numeric',
            'to' => 'required|string',
            'chain_name' => 'required|string',
            'token_name' => 'nullable|string',
            'sign' => 'required|string',
        ]);

        throw_if($request->app_id == config('services.apip.app_id') and ! $this->sign($request->all(), config('services.apip.app_key')), '签名不正确');
    }

    /**
     * 创建钱包
     *
     * @param  string  $label  标签名称
     * @param  string  $symbol  币种符号,例如 bsc20_usdt
     * @return mixed
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
     *
     * @param  string  $label  标签名称
     * @param  string  $address  钱包地址
     * @return mixed
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
     *
     * @param  string  $chain  链名称
     * @param  string  $label  标签名称
     * @param  string  $address  钱包地址
     * @param  string|null  $private_key  私钥
     * @return mixed
     */
    public function import(string $chain, string $label, string $address, ?string $private_key)
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
     *
     * @param  float  $amount  提现金额
     * @param  string  $to  提现地址
     * @param  string  $symbol  币种符号,例如 bsc20_usdt
     * @param  string|null  $from  来源标签,例如 withdraw
     * @return mixed
     */
    public function withdraw(float $amount, string $to, string $symbol, ?string $from = null)
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
     *
     * @param  string  $symbol  币种符号,例如 bsc20_usdt
     * @return mixed
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
     *
     * @param  string  $symbol  币种符号,例如 bsc20_usdt,trc20_usdt
     * @return mixed
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
     *
     * @return mixed
     */
    public function handle(Response $response)
    {
        return $response->json();
    }
}

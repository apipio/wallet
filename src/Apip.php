<?php

namespace Apip\Wallet;

use Apip\Wallet\Exceptions\ConfigurationException;
use Apip\Wallet\Exceptions\InvalidSignatureException;
use Apip\Wallet\Exceptions\RequestFailedException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

/**
 * apip.io 钱包支付网关客户端.
 *
 * 负责请求签名与异步回调验签. 网关地址, 参数名与签名算法由 apip.io 约定, 不得修改.
 */
class Apip
{
    /**
     * HTTP 客户端实例.
     */
    public PendingRequest $client;

    /**
     * 应用 ID.
     */
    public string $app_id = '';

    /**
     * 应用密钥, 仅用于本地签名, 禁止写入日志或返回给前端.
     */
    public string $app_key = '';

    /**
     * 初始化客户端.
     *
     * @param  string  $uri  网关地址, 留空时读取 config('services.apip.uri')
     *
     * @throws ConfigurationException 网关地址未配置时抛出
     */
    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            $uri = $this->resolveConfig('uri');
        }

        if (! is_string($uri) || $uri === '') {
            throw new ConfigurationException('未配置 apip.io 网关地址: apip.uri');
        }

        $app_id = $this->resolveConfig('app_id');
        $app_key = $this->resolveConfig('app_key');

        $this->app_id = is_string($app_id) ? $app_id : '';
        $this->app_key = is_string($app_key) ? $app_key : '';

        $this->client = Http::baseUrl($uri)
            ->withUserAgent('APIP')
            ->withOptions([
                'verify' => true,
            ]);
    }

    /**
     * 生成请求签名.
     *
     * 移除 app_key, sign, sign_type 后按键名排序, 过滤空值,
     * 拼接查询串与密钥后取 MD5 并转大写.
     *
     * @param  array<string, mixed>  $params  待签名参数
     * @param  string  $app_key  应用密钥
     * @return string 大写 MD5 签名
     *
     * @throws ConfigurationException 应用密钥为空时抛出
     */
    public function sign(array $params, #[SensitiveParameter] string $app_key): string
    {
        unset($params['app_key'], $params['sign'], $params['sign_type']);

        if ($app_key === '') {
            throw new ConfigurationException('未配置 apip.io 应用密钥: apip.app_key');
        }

        ksort($params);

        $params = array_filter($params);

        return strtoupper(md5(urldecode(http_build_query($params)) . $app_key));
    }

    /**
     * 校验异步回调的请求参数与签名.
     *
     * 应用 ID 或签名不匹配时抛出异常, 调用方应拒绝本次回调.
     *
     * @param  Request  $request  网关回调请求
     *
     * @throws ConfigurationException 应用凭据缺失时抛出
     * @throws InvalidSignatureException 缺少必要信息, 应用 ID 或签名不匹配时抛出
     */
    public function validate(Request $request): void
    {
        try {
            Validator::make($request->all(), [
                'app_id' => 'required|string',
                'from' => 'nullable|string',
                'amount' => 'nullable|numeric',
                'to' => 'required|string',
                'chain_name' => 'required|string',
                'token_name' => 'nullable|string',
                'sign' => 'required|string',
            ])->validate();
        } catch (ValidationException) {
            throw new InvalidSignatureException('缺少必要信息');
        }

        $app_id = $this->resolveConfig('app_id');
        $app_key = $this->resolveConfig('app_key');

        if (! is_string($app_id) || $app_id === '' || ! is_string($app_key) || $app_key === '') {
            throw new ConfigurationException('未配置 apip.io 应用凭据: apip.app_id / apip.app_key');
        }

        $request_app_id = $request->input('app_id');

        if (! is_string($request_app_id) || ! hash_equals($app_id, $request_app_id)) {
            throw new InvalidSignatureException('应用 ID 不匹配');
        }

        $expected = $this->sign($request->all(), $app_key);
        $request_sign = $request->input('sign');
        $provided = is_string($request_sign) ? strtoupper($request_sign) : '';

        if (! hash_equals($expected, $provided)) {
            throw new InvalidSignatureException('签名不正确');
        }
    }

    /**
     * 处理网关响应.
     *
     * 非 2xx 响应直接抛出异常, 避免调用方把失败当成成功.
     *
     * @param  Response  $response  HTTP 响应
     * @return mixed 解码后的响应体
     *
     * @throws RequestFailedException 响应状态码为 4xx/5xx 时抛出
     */
    public function handle(Response $response): mixed
    {
        try {
            return $response->throw()->json();
        } catch (RequestException $e) {
            throw new RequestFailedException('apip.io 请求失败: ' . $e->getMessage(), $e->response, $e);
        }
    }

    /**
     * 读取配置, 兼容旧版 services.apip 配置.
     *
     * @param  string  $key  配置项名称
     * @return mixed 配置值
     */
    private function resolveConfig(string $key): mixed
    {
        return Config::get('apip.'.$key) ?? Config::get('services.apip.'.$key);
    }
}

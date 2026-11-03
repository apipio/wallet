# apip.io Wallet

apip.io 数字货币支付网关的 Laravel 客户端,提供请求签名,异步回调验签与统一响应处理.

> 仅适用于 Laravel / Illuminate 框架.其他框架请参考 `src/Apip.php` 自行实现.

[apip.io 官方文档](https://apip.io/)

## 环境要求

- PHP >= 8.3
- Laravel 11 / 12 / 13

## 安装

```bash
composer require apip/wallet
```

包通过 Laravel 自动发现注册 `ApipServiceProvider`,无需手动添加.

## 配置

发布配置文件:

```bash
php artisan vendor:publish --tag=apip-config
```

在 `.env` 中填写:

```dotenv
APIP_URL=https://apip.io/api/
APIP_APP_ID=your-app-id
APIP_APP_KEY=your-app-key
```

不发布配置文件也可以,包会直接读取上述环境变量.继续使用 `services.apip.*` 时,`apip.*` 未配置会自动回退.

## 快速开始

```php
use Apip\Wallet\Apip;

$apip = app(Apip::class);
```

## 回调验签

```php
use Apip\Wallet\Exceptions\InvalidSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

public function callback(Request $request, Apip $apip)
{
    try {
        $apip->validate($request);
    } catch (ValidationException|InvalidSignatureException $e) {
        Log::warning('apip.io 回调失败', ['message' => $e->getMessage()]);

        return 'fail';
    }

    return 'success';
}
```

> 参照支付宝异步通知约定:处理成功返回 `success`,其余返回 `fail`.

完整的订单幂等与金额校验示例见 `examples/ApipCallbackController.php`.

## 方法

### `sign(array $params, string $app_key): string`

生成请求签名.移除 `app_key`,`sign`,`sign_type`,按键名排序,过滤空值后拼接密钥取 MD5.

```php
$params['sign'] = $apip->sign($params, config('apip.app_key'));
```

### `validate(Request $request): void`

校验回调字段,应用 ID 与签名,不通过时抛出异常.

| 异常 | 说明 |
| --- | --- |
| `ValidationException` | 字段缺失或类型错误 |
| `InvalidSignatureException` | 应用 ID 或签名不匹配 |
| `ConfigurationException` | 应用凭据未配置 |

### `handle(Response $response): mixed`

返回解码后的响应体.4xx / 5xx 抛出 `RequestFailedException`,原始响应通过 `response()` 获取.

```php
$response = $apip->client->post('send.wallet', $params);
$data = $apip->handle($response);
```

## 异常

所有异常继承 `ApipException`(`RuntimeException`),可按层级捕获.

```php
use Apip\Wallet\Exceptions\ApipException;

try {
    $apip->validate($request);
} catch (ApipException $e) {
    report($e);
}
```

## 许可证

MIT

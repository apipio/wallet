# apip.io payment

BTC ETH USDT/USDC apip.io Payment Gateway

Laravel USDT 数字货币支付接口

[apip.io 官方](https://apip.io/)

#### 使用方法

通过 composer 安装包（注意本包仅适用于 Laravel 框架，其他框架需要仿照修改）

```
composer require apip/wallet
```

然后在 _config/services.php_ 增加配置信息，后可以在.env 定义相关信息：APIP_APP_ID，APIP_APP_KEY

```php
    'apip' => [
        'uri' => env('APIP_URL', 'https://apip.io/api/'),
        'app_id' => env('APIP_APP_ID', 'wallet-OjZdTkYWJ1NEsvrFu5uD5iz94hiylIpvf2S8qSBNaGKLdVLUOVeeaDKios'),
        'app_key' => env('APIP_APP_KEY', '4XZym0Redk40fqKkx77qfkqBsCkfR6NTwNsMYSER4ywNhWsYuhNh9KItgKJMJ21a'),
    ],
```

如图：

![](config.png)

然后在 **_.env_** 添加设置 APIP_APP_ID、APIP_APP_KEY

#### 接口方法

```php

# 初始化
$apip = new \Apip\Wallet\Apip();

# 创建所有协议的钱包，可能会消耗一点时间，建议使用下面，创建指定协议
$apip->create('test_label')

# 创建指定协议
$apip->create('test_label', 'erc20')

# 创建指定协议，支持多个协议多个币种
$apip->create('test_label', 'bsc20,erc20')
$apip->create('test_label', 'bnb,bsc20')

# 提现 使用热钱包提现
$apip->create('1.00', '0x0000towallet', 'bsc20_usdt')

# 提现 指定发送钱包来提现
$apip->create('1.00', '0x0000towallet', 'bsc20_usdt', '0x000from_wallet')


// 获得汇总数据
$apip->data('bsc20_usdt');

// 获得汇总数据 支持多个协议多个币种
$apip->data('bnb,bsc20_usdt');

// 汇总钱包
$apip->collect('bsc20_usdt');

// 汇总钱包 支持多个协议多个币种
$apip->collect('bnb,bsc20_usdt');

# 接受支付回调在方法开头调用以下方法验证签名和各个请求参数，不合格抛出异常，可以使用try/catch进行处理
$apip->validate();

```

#### 官方网站

[apip.io 官方](https://apip.io/)

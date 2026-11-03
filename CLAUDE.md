# CLAUDE.md

## 项目

apip.io Wallet - Laravel 支付网关客户端 Composer 包.

## 技术栈

- PHP >= 8.3
- Laravel 11 / 12 / 13
- PHPUnit 12

## 代码规范

- 跟随 Laravel 官方风格, 不使用 declare(strict_types=1)
- 注释使用中文, 标点用半角
- 属性和返回值声明类型
- 新增配置写入 config/apip.php, 通过 env() 注入
- 新增异常继承 ApipException
- 对外方法签名, HTTP 端点, 签名算法不得随意修改

## 安全标准

- 禁止 verify => false 或任何关闭 TLS 校验的写法
- 禁止对提现, 归集等非幂等资金接口启用自动重试
- 回调验签必须使用 hash_equals, 只校验白名单字段 SIGNED_FIELDS
- 密钥只从环境变量读取, 禁止硬编码, 写日志或返回给前端
- 敏感参数使用 #[\SensitiveParameter]
- 回调只返回 success / fail, 不返回具体失败原因
- 失败原因写日志, 日志可带完整请求体, 但不得包含 app_key
- 修改签名或验签逻辑必须同步更新单元测试

## 测试

- 新行为必须补 PHPUnit 测试
- HTTP 使用 Http::fake() 模拟
- 运行: vendor/bin/phpunit
- 提交前: composer validate --strict + vendor/bin/phpunit

## Git

- 不提交 vendor/ 和 composer.lock
- 提交前检查 git status 无敏感文件
- 发布使用 tag, CHANGELOG 不披露安全修复细节

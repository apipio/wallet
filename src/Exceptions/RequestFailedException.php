<?php

namespace Apip\Wallet\Exceptions;

use Illuminate\Http\Client\Response;
use Throwable;

/**
 * 网关请求失败异常.
 *
 * 网关返回 4xx 或 5xx 时抛出, 保留原始响应便于排查.
 */
class RequestFailedException extends ApipException
{
    /**
     * @param  string  $message  异常说明
     * @param  Response|null  $response  网关原始响应
     * @param  Throwable|null  $previous  原始异常
     */
    public function __construct(
        string $message,
        private readonly ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $response?->status() ?? 0, $previous);
    }

    /**
     * 获取网关原始响应.
     */
    public function response(): ?Response
    {
        return $this->response;
    }
}

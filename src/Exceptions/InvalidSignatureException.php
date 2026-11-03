<?php

namespace Apip\Wallet\Exceptions;

/**
 * 回调签名校验失败异常.
 *
 * 应用 ID 不匹配或签名不正确时抛出, 调用方必须拒绝本次回调.
 */
class InvalidSignatureException extends ApipException
{
}

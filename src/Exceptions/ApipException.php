<?php

namespace Apip\Wallet\Exceptions;

use RuntimeException;

/**
 * apip.io 钱包客户端异常基类.
 *
 * 调用方可统一捕获本类处理所有客户端异常.
 */
class ApipException extends RuntimeException
{
}

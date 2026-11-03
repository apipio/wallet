<?php

namespace App\Http\Controllers;

use Apip\Wallet\Apip;
use Apip\Wallet\Exceptions\InvalidSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * apip.io 回调处理示例.
 *
 * 处理成功返回 success, 其余返回 fail.
 * 不向网关返回具体失败原因, 原因只写日志.
 */
class ApipCallbackController extends Controller
{
    public function __invoke(Request $request, Apip $apip): string
    {
        try {
            $apip->validate($request);

            // 业务逻辑: 按订单幂等入账, 校验金额, 币种和收款地址

            return 'success';
        } catch (InvalidSignatureException $e) {
            Log::warning('apip.io 回调校验失败', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return 'fail';
        } catch (Throwable $e) {
            Log::error('apip.io 回调处理异常', [
                'message' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return 'fail';
        }
    }
}

<?php

namespace App\Http\Controllers;

use Apip\Wallet\Apip;
use Apip\Wallet\Exceptions\InvalidSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * apip.io 回调处理示例.
 *
 * 参照支付宝异步通知约定: 处理成功返回 success, 其余情况返回 fail.
 * 不向网关返回具体失败原因, 原因只写日志, 避免信息泄露.
 * Order 模型与字段名请按实际项目调整.
 */
class ApipCallbackController extends Controller
{
    public function __invoke(Request $request, Apip $apip): string
    {
        $payload = $request->all();

        try {
            $apip->validate($request);
        } catch (ValidationException $e) {
            Log::warning('apip.io 回调参数不合法', [
                'errors' => $e->errors(),
                'request' => $payload,
            ]);

            return 'fail';
        } catch (InvalidSignatureException $e) {
            Log::warning('apip.io 回调验签失败', [
                'message' => $e->getMessage(),
                'request' => $payload,
            ]);

            return 'fail';
        }

        $address = $request->input('to');
        $amount = $request->input('amount');
        $symbol = $request->input('token_name');

        if (! is_string($address) || ! is_string($amount) || ! is_string($symbol)) {
            Log::warning('apip.io 回调字段类型错误', ['request' => $payload]);

            return 'fail';
        }

        $handled = Cache::lock('apip:callback:'.$address, 10)->block(
            3,
            fn (): bool => DB::transaction(function () use ($address, $amount, $symbol, $payload): bool {
                $order = Order::query()
                    ->where('address', $address)
                    ->where('symbol', $symbol)
                    ->lockForUpdate()
                    ->first();

                if ($order === null) {
                    Log::warning('apip.io 回调订单不存在', [
                        'request' => $payload,
                        'address' => $address,
                        'symbol' => $symbol,
                    ]);

                    return false;
                }

                // 已入账直接返回成功, 保证重复回调幂等
                if ($order->paid_at !== null) {
                    return true;
                }

                if (bccomp($amount, $order->amount, 8) !== 0) {
                    Log::warning('apip.io 回调金额不匹配', [
                        'request' => $payload,
                        'address' => $address,
                        'expected' => $order->amount,
                        'actual' => $amount,
                    ]);

                    return false;
                }

                $order->update(['paid_at' => now()]);

                return true;
            })
        );

        return $handled ? 'success' : 'fail';
    }
}

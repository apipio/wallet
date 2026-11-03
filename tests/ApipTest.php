<?php

namespace Apip\Wallet\Tests;

use Apip\Wallet\Apip;
use Apip\Wallet\Exceptions\ConfigurationException;
use Apip\Wallet\Exceptions\InvalidSignatureException;
use Apip\Wallet\Exceptions\RequestFailedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

final class ApipTest extends TestCase
{
    private const APP_ID = 'wallet-abc';

    private const APP_KEY = 'secret-key';

    #[Test]
    public function sign_matches_gateway_algorithm(): void
    {
        $signature = $this->apip()->sign([
            'app_id' => self::APP_ID,
            'amount' => '1.00',
            'to' => '0xto',
        ], self::APP_KEY);

        $this->assertSame('F0D81C9A3DF00BF1F99031F78450087A', $signature);
    }

    #[Test]
    public function sign_ignores_sign_fields(): void
    {
        $apip = $this->apip();

        $base = [
            'app_id' => self::APP_ID,
            'amount' => '1.00',
            'to' => '0xto',
        ];

        $withSign = $base + [
            'sign' => 'ignored',
            'app_key' => 'ignored',
            'sign_type' => 'MD5',
        ];

        $this->assertSame(
            $apip->sign($base, self::APP_KEY),
            $apip->sign($withSign, self::APP_KEY)
        );
    }

    #[Test]
    public function sign_requires_app_key(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->apip()->sign(['app_id' => self::APP_ID], '');
    }

    #[Test]
    public function validate_accepts_valid_callback(): void
    {
        $this->apip()->validate($this->makeRequest($this->payload()));

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validate_rejects_tampered_amount(): void
    {
        $payload = $this->payload();
        $payload['amount'] = '100.00';

        $this->expectException(InvalidSignatureException::class);

        $this->apip()->validate($this->makeRequest($payload));
    }

    #[Test]
    public function validate_rejects_wrong_app_id(): void
    {
        $payload = $this->payload();
        $payload['app_id'] = 'other';

        $this->expectException(InvalidSignatureException::class);

        $this->apip()->validate($this->makeRequest($payload));
    }

    #[Test]
    public function validate_rejects_missing_sign(): void
    {
        $payload = $this->payload();
        unset($payload['sign']);

        $this->expectException(ValidationException::class);

        $this->apip()->validate($this->makeRequest($payload));
    }

    #[Test]
    public function validate_rejects_arbitrary_signature(): void
    {
        $payload = $this->payload();
        $payload['sign'] = str_repeat('0', 32);

        $this->expectException(InvalidSignatureException::class);

        $this->apip()->validate($this->makeRequest($payload));
    }

    #[Test]
    public function validate_rejects_reused_signature_after_tamper(): void
    {
        $payload = $this->payload();
        $payload['to'] = '0xattacker';

        $this->expectException(InvalidSignatureException::class);

        $this->apip()->validate($this->makeRequest($payload));
    }

    #[Test]
    public function validate_ignores_fields_outside_whitelist(): void
    {
        $payload = $this->payload();
        $payload['coupon'] = 'free';
        $payload['note'] = 'injected';

        $this->apip()->validate($this->makeRequest($payload));

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function validate_requires_credentials(): void
    {
        Config::set('apip.app_id', null);
        Config::set('apip.app_key', null);

        $this->expectException(ConfigurationException::class);

        $this->apip()->validate($this->makeRequest($this->payload()));
    }

    #[Test]
    public function validate_falls_back_to_legacy_config(): void
    {
        Config::set('apip', null);
        Config::set('services.apip.uri', 'https://apip.io/api/');
        Config::set('services.apip.app_id', self::APP_ID);
        Config::set('services.apip.app_key', self::APP_KEY);

        $this->apip()->validate($this->makeRequest($this->payload()));

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function handle_returns_decoded_body(): void
    {
        Http::fake([
            'https://apip.io/api/*' => Http::response(['code' => 0, 'data' => 'ok'], 200),
        ]);

        $response = Http::baseUrl('https://apip.io/api/')->post('wallet.data');

        $result = $this->apip()->handle($response);

        $this->assertSame(['code' => 0, 'data' => 'ok'], $result);
    }

    #[Test]
    public function handle_throws_request_failed_exception(): void
    {
        Http::fake([
            'https://apip.io/api/*' => Http::response('gateway error', 500),
        ]);

        $response = Http::baseUrl('https://apip.io/api/')->post('wallet.data');

        try {
            $this->apip()->handle($response);
            $this->fail('RequestFailedException was not thrown.');
        } catch (RequestFailedException $e) {
            $this->assertSame(500, $e->getCode());
            $this->assertNotNull($e->response());
        }
    }

    private function apip(): Apip
    {
        return new Apip();
    }

    private function payload(): array
    {
        $payload = [
            'app_id' => self::APP_ID,
            'from' => '0xfrom',
            'amount' => '1.00',
            'to' => '0xto',
            'chain_name' => 'bsc',
            'token_name' => 'usdt',
        ];

        $payload['sign'] = $this->apip()->sign($payload, self::APP_KEY);

        return $payload;
    }

    private function makeRequest(array $payload): Request
    {
        return Request::create('/callback', 'POST', $payload);
    }
}

<?php
/**
 * Safaricom Daraja (M-Pesa) client.
 * All credentials live in Admin → Settings → M-Pesa, so going live is just:
 *   1. paste the live consumer key/secret, shortcode and passkey,
 *   2. switch environment to "live",
 *   3. click "Register C2B URLs".
 */
class Mpesa
{
    public static function baseUrl(): string
    {
        return setting('mpesa_env', 'sandbox') === 'live' ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';
    }

    public static function isConfigured(): bool
    {
        return setting('mpesa_consumer_key') !== '' && setting('mpesa_consumer_secret') !== ''
            && setting('mpesa_shortcode') !== '' && setting('mpesa_passkey') !== '';
    }

    /** Public URL Safaricom calls back. Must be HTTPS and reachable from the internet. */
    public static function callbackUrl(string $endpoint): string
    {
        $base = rtrim(setting('mpesa_callback_base') ?: BASE_URL, '/');
        $secret = setting('mpesa_callback_secret');
        return $base . '/api/' . $endpoint . ($secret ? '?key=' . urlencode($secret) : '');
    }

    public static function log(string $endpoint, $payload): void
    {
        try {
            insert('mpesa_logs', [
                'endpoint' => $endpoint,
                'payload'  => is_string($payload) ? $payload : json_encode($payload),
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            error_log('mpesa log failed: ' . $e->getMessage());
        }
    }

    /** OAuth access token, cached until shortly before expiry. */
    public static function token(bool $force = false): string
    {
        $cache = json_decode(setting('mpesa_token_cache'), true);
        if (!$force && is_array($cache) && ($cache['env'] ?? '') === setting('mpesa_env') && ($cache['exp'] ?? 0) > time() + 60) {
            return $cache['token'];
        }
        $res = self::http('GET', '/oauth/v1/generate?grant_type=client_credentials', null,
            'Basic ' . base64_encode(setting('mpesa_consumer_key') . ':' . setting('mpesa_consumer_secret')));
        if (empty($res['body']['access_token'])) {
            throw new RuntimeException('M-Pesa authentication failed. Check the consumer key/secret and environment. (' . ($res['raw'] ?: 'HTTP ' . $res['status']) . ')');
        }
        set_setting('mpesa_token_cache', json_encode([
            'token' => $res['body']['access_token'],
            'exp'   => time() + (int) ($res['body']['expires_in'] ?? 3599),
            'env'   => setting('mpesa_env'),
        ]));
        return $res['body']['access_token'];
    }

    public static function http(string $method, string $path, ?array $body = null, ?string $auth = null): array
    {
        $ch = curl_init(self::baseUrl() . $path);
        $headers = ['Content-Type: application/json', 'Authorization: ' . ($auth ?: 'Bearer ' . self::token())];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('Could not reach M-Pesa: ' . $err);
        return ['status' => $status, 'body' => json_decode($raw, true) ?: [], 'raw' => $raw];
    }

    private static function password(string $timestamp): string
    {
        return base64_encode(setting('mpesa_shortcode') . setting('mpesa_passkey') . $timestamp);
    }

    /**
     * Lipa na M-Pesa Online (STK push).
     * Returns Daraja's response: MerchantRequestID, CheckoutRequestID, ResponseCode, CustomerMessage.
     */
    public static function stkPush(string $phone, float $amount, string $reference, string $description): array
    {
        $ts = date('YmdHis');
        $isTill = setting('mpesa_type', 'paybill') === 'till';
        $payload = [
            'BusinessShortCode' => setting('mpesa_shortcode'),
            'Password'          => self::password($ts),
            'Timestamp'         => $ts,
            'TransactionType'   => $isTill ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
            'Amount'            => (int) ceil($amount),
            'PartyA'            => $phone,
            // For till numbers, PartyB is the till; the shortcode above is the store number
            'PartyB'            => $isTill && setting('mpesa_till_number') ? setting('mpesa_till_number') : setting('mpesa_shortcode'),
            'PhoneNumber'       => $phone,
            'CallBackURL'       => self::callbackUrl('mpesa-callback.php'),
            'AccountReference'  => substr($reference, 0, 12),
            'TransactionDesc'   => substr($description, 0, 13),
        ];
        $res = self::http('POST', '/mpesa/stkpush/v1/processrequest', $payload);
        self::log('stk_push_response', $res['body'] ?: $res['raw']);
        if (($res['body']['ResponseCode'] ?? '') !== '0') {
            throw new RuntimeException($res['body']['errorMessage'] ?? $res['body']['ResponseDescription'] ?? 'STK push was not accepted.');
        }
        return $res['body'] + ['_request' => array_diff_key($payload, ['Password' => 1])];
    }

    /** Asks Daraja for the final state of an STK push (used if the callback is late). */
    public static function stkQuery(string $checkoutRequestId): array
    {
        $ts = date('YmdHis');
        $res = self::http('POST', '/mpesa/stkpushquery/v1/query', [
            'BusinessShortCode' => setting('mpesa_shortcode'),
            'Password'          => self::password($ts),
            'Timestamp'         => $ts,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);
        self::log('stk_query_response', $res['body'] ?: $res['raw']);
        return $res['body'];
    }

    /** Registers C2B confirmation/validation URLs so direct Paybill payments are captured. */
    public static function registerC2B(): array
    {
        $res = self::http('POST', '/mpesa/c2b/v1/registerurl', [
            'ShortCode'       => setting('mpesa_c2b_shortcode') ?: setting('mpesa_shortcode'),
            'ResponseType'    => 'Completed',
            'ConfirmationURL' => self::callbackUrl('c2b-confirmation.php'),
            'ValidationURL'   => self::callbackUrl('c2b-validation.php'),
        ]);
        self::log('c2b_register_response', $res['body'] ?: $res['raw']);
        return $res;
    }

    /** Validates the ?key= secret on callback URLs. */
    public static function verifyCallbackKey(): bool
    {
        $secret = setting('mpesa_callback_secret');
        return $secret === '' || hash_equals($secret, (string) ($_GET['key'] ?? ''));
    }

    /** Maps Daraja result codes to our transaction status. */
    public static function statusFromCode($code): string
    {
        $code = (string) $code;
        if ($code === '0') return 'success';
        if ($code === '1032') return 'cancelled';
        if ($code === '1037') return 'timeout';
        return 'failed';
    }

    public static function friendlyMessage($code, string $fallback = ''): string
    {
        $map = [
            '0'    => 'Payment received. Thank you!',
            '1'    => 'Your M-Pesa balance is insufficient for this payment.',
            '1032' => 'You cancelled the M-Pesa prompt.',
            '1037' => 'We could not reach your phone. Make sure it is on and unlocked, then try again.',
            '2001' => 'Wrong M-Pesa PIN entered.',
            '1025' => 'An error occurred sending the prompt. Please try again.',
            '1019' => 'The transaction expired. Please try again.',
        ];
        return $map[(string) $code] ?? ($fallback ?: 'The payment was not completed.');
    }

    /** Applies a successful/failed STK result to the transaction and its order. */
    public static function applyStkResult(array $tx, $resultCode, string $resultDesc, array $meta = [], ?string $raw = null): void
    {
        $status = self::statusFromCode($resultCode);
        update('mpesa_transactions', array_filter([
            'result_code'   => (int) $resultCode,
            'result_desc'   => substr($resultDesc, 0, 250),
            'status'        => $status,
            'mpesa_receipt' => $meta['MpesaReceiptNumber'] ?? null,
            'trans_time'    => isset($meta['TransactionDate']) ? (string) $meta['TransactionDate'] : null,
            'raw_callback'  => $raw,
        ], fn($v) => $v !== null), 'id = ?', [$tx['id']]);

        if ($status === 'success' && $tx['order_id']) {
            $order = row('SELECT * FROM orders WHERE id = ?', [$tx['order_id']]);
            $paid = (float) ($meta['Amount'] ?? $tx['amount']);
            if ($order && $paid + 0.01 >= (float) $order['amount']) {
                mark_order_paid((int) $order['id'], $meta['MpesaReceiptNumber'] ?? null, isset($meta['PhoneNumber']) ? (string) $meta['PhoneNumber'] : $tx['phone']);
            } elseif ($order) {
                update('orders', ['notes' => 'Underpaid: received ' . $paid], 'id = ?', [$order['id']]);
            }
        }
    }
}

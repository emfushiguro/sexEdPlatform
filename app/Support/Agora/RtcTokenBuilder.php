<?php

namespace App\Support\Agora;

class RtcTokenBuilder
{
    private const VERSION = '007';

    private const SERVICE_RTC = 1;

    private const PRIVILEGE_JOIN = 1;

    private const PRIVILEGE_PUBLISH_AUDIO = 2;

    private const PRIVILEGE_PUBLISH_VIDEO = 3;

    private const PRIVILEGE_PUBLISH_DATA = 4;

    public static function build(string $appId, string $appCertificate, string $channel, int $uid, bool $canPublish, int $expiresAt): string
    {
        if (! ctype_xdigit($appId) || strlen($appId) !== 32 || ! ctype_xdigit($appCertificate) || strlen($appCertificate) !== 32) {
            return '';
        }

        $issuedAt = time();
        $ttl = max(1, $expiresAt - $issuedAt);
        $salt = random_int(1, 99999999);
        $privileges = [
            self::PRIVILEGE_JOIN => $ttl,
        ];

        if ($canPublish) {
            $privileges[self::PRIVILEGE_PUBLISH_AUDIO] = $ttl;
            $privileges[self::PRIVILEGE_PUBLISH_VIDEO] = $ttl;
            $privileges[self::PRIVILEGE_PUBLISH_DATA] = $ttl;
        }

        $service = self::packUint16(self::SERVICE_RTC)
            .self::packMapUint32($privileges)
            .self::packString($channel)
            .self::packString((string) $uid);

        $data = self::packString($appId)
            .self::packUint32($issuedAt)
            .self::packUint32($ttl)
            .self::packUint32($salt)
            .self::packUint16(1)
            .$service;

        $signing = hash_hmac('sha256', hash_hmac('sha256', $appCertificate, self::packUint32($issuedAt), true), self::packUint32($salt), true);
        $signature = hash_hmac('sha256', $data, $signing, true);

        return self::VERSION.base64_encode(zlib_encode(self::packString($signature).$data, ZLIB_ENCODING_DEFLATE));
    }

    private static function packString(string $value): string
    {
        return self::packUint16(strlen($value)).$value;
    }

    private static function packUint16(int $value): string
    {
        return pack('v', $value);
    }

    private static function packUint32(int $value): string
    {
        return pack('V', $value);
    }

    private static function packMapUint32(array $values): string
    {
        $packed = '';
        ksort($values);

        foreach ($values as $key => $value) {
            $packed .= self::packUint16((int) $key).self::packUint32((int) $value);
        }

        return self::packUint16(count($values)).$packed;
    }
}

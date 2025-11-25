<?php


require __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;


require_once __DIR__ . '/config.php';

class JWT
{
    private static $secretKey;
    private static $refreshSecret;
    private static $accessTokenTTL;  // in seconds
    private static $refreshTokenTTL; // in seconds
    private static $db;

    public static function init()
    {
        $config = new Config();
        self::$secretKey       = getenv('JWT_SECRET') ?: 'default_access_secret';
        self::$refreshSecret   = getenv('JWT_REFRESH_SECRET') ?: 'default_refresh_secret';
        self::$accessTokenTTL  = getenv('JWT_ACCESS_EXPIRY') ?: 60 * 60 *1212;    // 12 hours
        self::$refreshTokenTTL = getenv('JWT_REFRESH_EXPIRY') ?: 60 * 60 * 24 * 7; // 7 days

        // Use reflection to access private $db from Config
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        self::$db = $property->getValue($config);
    }

    public static function createAccessToken(array $payload): string
    {
        $issuedAt = time();
        $expireAt = $issuedAt + self::$accessTokenTTL;

        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expireAt;

        return FirebaseJWT::encode($payload, self::$secretKey, 'HS256');
    }

    public static function createRefreshToken(array $payload): string
    {
        $issuedAt = time();
        $expireAt = $issuedAt + self::$refreshTokenTTL;

        $payload['iat'] = $issuedAt;
        $payload['exp'] = $expireAt;

        $refreshToken = FirebaseJWT::encode($payload, self::$refreshSecret, 'HS256');

        // Save session in DB
        self::saveSession($payload['sub'], $refreshToken, $expireAt);

        return $refreshToken;
    }

    private static function saveSession($userId, $refreshToken, $expireTimestamp): void
    {
        $expiresAt  = date('Y-m-d H:i:s', $expireTimestamp);
        $ipAddress  = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $deviceInfo = $_SERVER['HTTP_SEC_CH_UA'] ?? null;

        $stmt = self::$db->prepare("INSERT INTO sessions (user_id, refresh_token, expires_at, ip_address, user_agent, device_info)
                                    VALUES (:user_id, :refresh_token, :expires_at, :ip_address, :user_agent, :device_info)");
        $stmt->execute([
            'user_id'       => $userId,
            'refresh_token' => $refreshToken,
            'expires_at'    => $expiresAt,
            'ip_address'    => $ipAddress,
            'user_agent'    => $userAgent,
            'device_info'   => $deviceInfo,
        ]);
    }

    public static function verifyAccessToken($token)
    {
        try {
            return FirebaseJWT::decode($token, new Key(self::$secretKey, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }

    public static function verifyRefreshToken($token)
    {
        try {
            return FirebaseJWT::decode($token, new Key(self::$refreshSecret, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }
}

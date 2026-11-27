<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LicenseService
{
    public const TOKEN_PATH = 'license.token';

    /**
     * Hardcoded license settings (safe to ionCube-encode this file; cannot be overridden via .env or config).
     */
    private const LICENSE_SERVER = 'http://codemarketz.test';

    private const PRODUCT_SLUG = 'droidxpert-advanced-android-app-solution-for-businesses';

    private const PUBLIC_KEY = 'a7JWXsZCQh/YRUrHOaIsfdiias2v6ws4+rWcGvAUnK8=';

    private const CLIENT_VERSION = '1.0.0';

    private const API_TIMEOUT = 15;

    private static ?string $testServerOverride = null;

    private static ?string $testProductSlugOverride = null;

    private static ?string $testPublicKeyOverride = null;

    public static function storagePath(): string
    {
        return storage_path('app/license.json');
    }

    public static function isTestContext(): bool
    {
        return app()->runningUnitTests() && app()->environment('testing');
    }

    public static function setTestingOverrides(?string $publicKey = null, ?string $server = null, ?string $productSlug = null): void
    {
        if (! self::isTestContext()) {
            return;
        }

        self::$testPublicKeyOverride = $publicKey;
        self::$testServerOverride = $server !== null ? rtrim($server, '/') : null;
        self::$testProductSlugOverride = $productSlug;
    }

    public static function clearTestingOverrides(): void
    {
        self::$testPublicKeyOverride = null;
        self::$testServerOverride = null;
        self::$testProductSlugOverride = null;
    }

    public static function serverUrl(): string
    {
        if (self::isTestContext() && self::$testServerOverride !== null) {
            return self::$testServerOverride;
        }

        return rtrim(self::LICENSE_SERVER, '/');
    }

    public static function activateEndpoint(): string
    {
        return self::serverUrl().'/api/v1/public/licenses/activate';
    }

    public static function verifyEndpoint(): string
    {
        return self::serverUrl().'/api/v1/public/licenses/verify';
    }

    public static function productSlug(): string
    {
        if (self::isTestContext() && self::$testProductSlugOverride !== null) {
            return self::$testProductSlugOverride;
        }

        return self::PRODUCT_SLUG;
    }

    private static function publicKey(): string
    {
        if (self::isTestContext() && self::$testPublicKeyOverride !== null) {
            return self::$testPublicKeyOverride;
        }

        return self::PUBLIC_KEY;
    }

    /**
     * Check whether a cryptographically valid license token is stored locally.
     */
    public static function isActivated(): bool
    {
        try {
            $token = Storage::disk('local')->get(self::TOKEN_PATH);
            if (! is_string($token) || trim($token) === '') {
                return false;
            }

            self::verifyToken($token);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Read persisted license metadata from storage/app/license.json.
     *
     * @return array<string, mixed>|null
     */
    public static function getLicenseData(): ?array
    {
        $path = self::storagePath();
        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Activate purchase code against remote license server, verify Ed25519 certificate, and store token.
     *
     * @return array<string, mixed>
     */
    public static function activate(string $purchaseCode, ?string $domain = null): array
    {
        $purchaseCode = trim($purchaseCode);
        if ($purchaseCode === '') {
            throw new RuntimeException('Purchase code / license key wajib diisi.');
        }

        $productSlug = self::productSlug();
        $clientVersion = self::CLIENT_VERSION;
        $resolvedDomain = $domain ?: request()->getHost();

        try {
            $res = Http::timeout(self::API_TIMEOUT)
                ->acceptJson()
                ->post(self::activateEndpoint(), [
                    'purchase_code' => $purchaseCode,
                    'product_slug' => $productSlug,
                    'domain' => $resolvedDomain,
                    'client_version' => $clientVersion,
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak dapat terhubung ke server lisensi: '.$e->getMessage(), 0, $e);
        }

        if (! $res->successful() || ! $res->json('valid')) {
            throw new RuntimeException((string) $res->json('message', 'Invalid license key'));
        }

        $certificate = $res->json('data.certificate');
        $token = is_array($certificate) ? (string) ($certificate['token'] ?? '') : (string) $certificate;
        if ($token === '') {
            throw new RuntimeException('Token sertifikat lisensi tidak ditemukan dalam respons server.');
        }

        $payload = self::verifyToken($token);
        $entitlements = $res->json('data.entitlements', $payload['entitlements'] ?? []);

        return self::persistLicense($token, $payload, $purchaseCode, $productSlug, $resolvedDomain, $clientVersion, $certificate, $entitlements);
    }

    /**
     * Verify offline Ed25519 license token (and check expiry / optional module entitlement).
     *
     * @return array<string, mixed>
     */
    public static function verifyToken(string $token, ?string $requiredModule = null): array
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            throw new RuntimeException('PHP sodium extension is required for Ed25519 license verification.');
        }

        [$encoded, $sigB64] = explode('.', trim($token), 2) + ['', ''];
        $pub = base64_decode(self::publicKey(), true);
        $sig = base64_decode($sigB64, true);

        if (! $pub || ! $sig || ! sodium_crypto_sign_verify_detached($sig, $encoded, $pub)) {
            throw new RuntimeException('Invalid license signature.');
        }

        $decodedJson = base64_decode($encoded, true);
        $payload = is_string($decodedJson) ? json_decode($decodedJson, true) : null;
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid license payload.');
        }

        if (! empty($payload['expires_at']) && now()->isAfter($payload['expires_at'])) {
            throw new RuntimeException('Trial / time-limited license expired.');
        }

        if ($requiredModule !== null) {
            $modules = $payload['entitlements']['modules'] ?? [];
            if (! is_array($modules) || ! in_array($requiredModule, $modules, true)) {
                throw new RuntimeException('Module not included in your license tier.');
            }
        }

        return $payload;
    }

    /**
     * Verify locally stored license.token and trigger online heartbeat when past grace period.
     *
     * @return array<string, mixed>
     */
    public static function verifyStored(?string $requiredModule = null): array
    {
        $token = Storage::disk('local')->get(self::TOKEN_PATH);
        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('License not activated.');
        }

        $payload = self::verifyToken($token, $requiredModule);

        if (now()->isAfter($payload['grace_expires_at'] ?? now()->subSecond())) {
            $payload = self::refreshOnline($payload, $requiredModule);
        }

        return $payload;
    }

    /**
     * Trigger online heartbeat POST /api/v1/public/licenses/verify to refresh certificate after grace period.
     *
     * @param  array<string, mixed>  $currentPayload
     * @return array<string, mixed>
     */
    public static function refreshOnline(array $currentPayload, ?string $requiredModule = null): array
    {
        $purchaseCode = (string) ($currentPayload['purchase_code'] ?? '');
        if ($purchaseCode === '') {
            return $currentPayload;
        }

        try {
            $res = Http::timeout(self::API_TIMEOUT)
                ->acceptJson()
                ->post(self::verifyEndpoint(), [
                    'purchase_code' => $purchaseCode,
                    'product_slug' => self::productSlug(),
                    'domain' => (string) ($currentPayload['domain'] ?? request()->getHost()),
                    'client_version' => self::CLIENT_VERSION,
                ]);

            if (! $res->successful() || $res->json('valid') === false) {
                throw new RuntimeException((string) $res->json('message', 'License verification failed.'));
            }

            $certificate = $res->json('data.certificate');
            $newToken = is_array($certificate) ? (string) ($certificate['token'] ?? '') : (string) $certificate;
            if ($newToken !== '') {
                $newPayload = self::verifyToken($newToken, $requiredModule);
                $entitlements = $res->json('data.entitlements', $newPayload['entitlements'] ?? []);
                self::persistLicense(
                    $newToken,
                    $newPayload,
                    $purchaseCode,
                    (string) ($newPayload['product_slug'] ?? self::productSlug()),
                    (string) ($newPayload['domain'] ?? request()->getHost()),
                    self::CLIENT_VERSION,
                    $certificate,
                    $entitlements
                );

                return $newPayload;
            }
        } catch (ConnectionException $e) {
            Log::warning('License heartbeat unreachable: '.$e->getMessage());
        }

        return $currentPayload;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function persistLicense(
        string $token,
        array $payload,
        string $purchaseCode,
        string $productSlug,
        string $domain,
        string $clientVersion,
        mixed $certificate,
        mixed $entitlements
    ): array {
        Storage::disk('local')->put(self::TOKEN_PATH, $token);

        $record = [
            'purchase_code' => $purchaseCode,
            'product_slug' => $productSlug,
            'domain' => $domain,
            'client_version' => $clientVersion,
            'license_type' => $payload['license_type'] ?? 'regular',
            'is_trial' => (bool) ($payload['is_trial'] ?? false),
            'expires_at' => $payload['expires_at'] ?? null,
            'grace_expires_at' => $payload['grace_expires_at'] ?? null,
            'support_expires_at' => $payload['support_expires_at'] ?? null,
            'entitlements' => is_array($entitlements) ? $entitlements : [],
            'certificate' => $certificate,
            'activated_at' => now()->toIso8601String(),
        ];

        File::ensureDirectoryExists(dirname(self::storagePath()));
        File::put(self::storagePath(), json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $record;
    }

    /**
     * Remove stored local license token and metadata.
     */
    public static function clear(): void
    {
        Storage::disk('local')->delete(self::TOKEN_PATH);

        $path = self::storagePath();
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}

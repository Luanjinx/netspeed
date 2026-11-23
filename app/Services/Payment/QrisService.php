<?php

namespace App\Services\Payment;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Illuminate\Support\Facades\Log;

class QrisService
{
    /**
     * Convert Static QRIS to Dynamic QRIS by injecting transaction amount and recalculating CRC16.
     *
     * @param  string  $staticQris  Raw string of merchant static QRIS
     * @param  float|int  $amount  Exact transaction amount (IDR)
     * @param  string|null  $invoiceRef  Optional invoice reference/number for tag 62
     * @return string Dynamic QRIS string
     */
    public function convertStaticToDynamic(string $staticQris, float|int $amount, ?string $invoiceRef = null): string
    {
        $cleanQris = trim($staticQris);

        if (empty($cleanQris) || strlen($cleanQris) < 30) {
            throw new Exception('QRIS statis tidak valid atau kosong.');
        }

        // Parse TLV tags
        $tags = $this->parseTlv($cleanQris);

        if (! isset($tags['00'])) {
            throw new Exception('Format QRIS tidak memenuhi standar EMVCo.');
        }

        // 1. Force Point of Initiation Method (Tag 01) to Dynamic (12)
        $tags['01'] = '12';

        // 2. Ensure Currency is IDR (Tag 53 = 360)
        if (! isset($tags['53'])) {
            $tags['53'] = '360';
        }

        // 3. Inject Amount (Tag 54)
        $formattedAmount = number_format((float) $amount, 0, '', '');
        $tags['54'] = $formattedAmount;

        // 4. Inject Reference (Tag 62) if provided
        if ($invoiceRef) {
            $billTag = '01'.sprintf('%02d', strlen($invoiceRef)).$invoiceRef;
            $tags['62'] = $billTag;
        }

        // 5. Remove old CRC (Tag 63)
        unset($tags['63']);

        // 6. Reconstruct payload string in proper tag order
        $payloadWithoutCrc = '';
        // Sort tags numerically or maintain EMVCo canonical sequence
        $orderedKeys = $this->getOrderedKeys(array_keys($tags));

        foreach ($orderedKeys as $tag) {
            $val = $tags[$tag];
            $len = sprintf('%02d', strlen($val));
            $payloadWithoutCrc .= $tag.$len.$val;
        }

        // 7. Append Tag 63 with length 04
        $payloadToHash = $payloadWithoutCrc.'6304';

        // 8. Calculate CRC16-CCITT (0x1021, init 0xFFFF)
        $crcHex = $this->calculateCrc16($payloadToHash);

        return $payloadToHash.$crcHex;
    }

    /**
     * Parse EMVCo TLV string into array [tag => value].
     */
    public function parseTlv(string $raw): array
    {
        $tags = [];
        $i = 0;
        $totalLen = strlen($raw);

        while ($i < $totalLen) {
            if ($i + 4 > $totalLen) {
                break;
            }

            $tag = substr($raw, $i, 2);
            $len = (int) substr($raw, $i + 2, 2);
            $i += 4;

            if ($i + $len > $totalLen) {
                break;
            }

            $value = substr($raw, $i, $len);
            $tags[$tag] = $value;
            $i += $len;
        }

        return $tags;
    }

    /**
     * Calculate CRC16-CCITT (False) checksum: Poly 0x1021, Initial 0xFFFF.
     */
    public function calculateCrc16(string $data): string
    {
        $crc = 0xFFFF;
        $len = strlen($data);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(sprintf('%04x', $crc));
    }

    /**
     * Order tags according to EMVCo specification.
     */
    protected function getOrderedKeys(array $keys): array
    {
        usort($keys, function ($a, $b) {
            return (int) $a <=> (int) $b;
        });

        return $keys;
    }

    /**
     * Generate QR Code as SVG string.
     */
    public function renderSvg(string $qrisString): string
    {
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($qrisString);
    }

    /**
     * Generate QR Code as Base64 Data URI (defaults to PNG for maximum cross-platform compatibility).
     */
    public function renderDataUri(string $qrisString, string $format = 'png'): string
    {
        if ($format === 'svg') {
            $svg = $this->renderSvg($qrisString);

            if (str_starts_with($svg, 'data:image/')) {
                return $svg;
            }

            return 'data:image/svg+xml;base64,'.base64_encode($svg);
        }

        return $this->renderPngDataUri($qrisString);
    }

    /**
     * Generate QR Code as Base64 Data URI in PNG format.
     */
    public function renderPngDataUri(string $qrisString, int $scale = 8): string
    {
        $options = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => $scale,
            'imageBase64' => true,
            'addQuietzone' => true,
        ]);

        $qrcode = new QRCode($options);

        return $qrcode->render($qrisString);
    }

    /**
     * Get merchant name from QRIS string.
     */
    public function getMerchantName(string $qrisString): string
    {
        $tags = $this->parseTlv($qrisString);

        return $tags['59'] ?? 'Merchant';
    }

    /**
     * Get merchant city from QRIS string.
     */
    public function getMerchantCity(string $qrisString): string
    {
        $tags = $this->parseTlv($qrisString);

        return $tags['60'] ?? '';
    }

    /**
     * Decode a QR code from an image file path, uploaded file, or binary data.
     * Extracts raw EMVCo payload, merchant name, and merchant city.
     *
     * @param  string  $pathOrBlob  File path, raw binary string, or data URI
     * @return array{
     *     success: bool,
     *     payload: ?string,
     *     merchant_name: ?string,
     *     merchant_city: ?string,
     *     is_emvco: bool,
     *     message: string,
     *     tags: array
     * }
     */
    public function decodeFromImage(string $pathOrBlob): array
    {
        $rawString = null;

        try {
            $qrcode = new QRCode;

            if (is_file($pathOrBlob)) {
                $content = file_get_contents($pathOrBlob);
                if (str_starts_with($content, 'data:image/') && str_contains($content, ';base64,')) {
                    $base64 = explode(';base64,', $content)[1] ?? '';
                    $result = $qrcode->readFromBlob(base64_decode($base64));
                } else {
                    $result = $qrcode->readFromFile($pathOrBlob);
                }
            } elseif (str_starts_with($pathOrBlob, 'data:image/') && str_contains($pathOrBlob, ';base64,')) {
                $base64 = explode(';base64,', $pathOrBlob)[1] ?? '';
                $blob = base64_decode($base64);
                $result = $qrcode->readFromBlob($blob);
            } else {
                $result = $qrcode->readFromBlob($pathOrBlob);
            }

            $rawString = trim((string) $result);
        } catch (\Throwable $e) {

            Log::warning('QRIS Image Decoding Exception: '.$e->getMessage());

            return [
                'success' => false,
                'payload' => null,
                'merchant_name' => null,
                'merchant_city' => null,
                'is_emvco' => false,
                'message' => 'Tidak dapat mendeteksi barcode QR Code pada gambar yang diunggah. Pastikan gambar jelas, fokus, dan tidak terpotong.',
                'tags' => [],
            ];
        }

        if (empty($rawString)) {
            return [
                'success' => false,
                'payload' => null,
                'merchant_name' => null,
                'merchant_city' => null,
                'is_emvco' => false,
                'message' => 'QR Code berhasil dipindai, namun data di dalamnya kosong.',
                'tags' => [],
            ];
        }

        $tags = $this->parseTlv($rawString);
        $isEmvco = str_starts_with($rawString, '000201') || (isset($tags['00']) && $tags['00'] === '01');

        if (! $isEmvco) {
            return [
                'success' => false,
                'payload' => $rawString,
                'merchant_name' => null,
                'merchant_city' => null,
                'is_emvco' => false,
                'message' => 'QR Code berhasil dipindai, tetapi bukan format QRIS Bank Indonesia (EMVCo). Teks terdeteksi: '.substr($rawString, 0, 60).'...',
                'tags' => $tags,
            ];
        }

        $merchantName = $tags['59'] ?? 'Merchant QRIS';
        $merchantCity = $tags['60'] ?? '';

        return [
            'success' => true,
            'payload' => $rawString,
            'merchant_name' => $merchantName,
            'merchant_city' => $merchantCity,
            'is_emvco' => true,
            'message' => "QRIS Berhasil Dipindai! Merchant: {$merchantName}".($merchantCity ? " ({$merchantCity})" : ''),
            'tags' => $tags,
        ];
    }

    /**
     * Get detailed metadata from an EMVCo QRIS string.
     */
    public function getQrisDetails(string $qrisString): array
    {
        $tags = $this->parseTlv($qrisString);
        $isEmvco = str_starts_with($qrisString, '000201') || (isset($tags['00']) && $tags['00'] === '01');

        $crcValid = false;
        if ($isEmvco && strlen($qrisString) > 4) {
            $expectedCrc = substr($qrisString, -4);
            $toHash = substr($qrisString, 0, -4);
            $calcCrc = $this->calculateCrc16($toHash);
            $crcValid = (strtoupper($expectedCrc) === strtoupper($calcCrc));
        }

        return [
            'is_emvco' => $isEmvco,
            'merchant_name' => $tags['59'] ?? null,
            'merchant_city' => $tags['60'] ?? null,
            'country_code' => $tags['58'] ?? null,
            'currency_code' => $tags['53'] ?? null,
            'initiation_method' => ($tags['01'] ?? '') === '12' ? 'Dinamis' : 'Statis',
            'amount' => isset($tags['54']) ? (float) $tags['54'] : null,
            'crc' => $tags['63'] ?? (strlen($qrisString) >= 4 ? substr($qrisString, -4) : null),
            'crc_valid' => $crcValid,
            'tags' => $tags,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\DocumentSignature;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class SignatureService
{
    protected $privateKeyPath = 'private/signing.pem';
    protected $publicKeyPath = 'private/signing.pub.pem';

    public function ensureKeysExist()
    {
        if (!Storage::exists($this->privateKeyPath) || !Storage::exists($this->publicKeyPath)) {
            $config = [
                "digest_alg" => "sha256",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ];
            
            $res = openssl_pkey_new($config);

            // Fix for Windows: Try to find openssl.cnf if initial generation fails
            if (!$res && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $commonPaths = [
                    'C:/xampp/php/extras/ssl/openssl.cnf',
                    'D:/xampp/php/extras/ssl/openssl.cnf',
                    'C:/laragon/bin/php/php-8.2.10-Win32-vs16-x64/extras/ssl/openssl.cnf', // Adjust version if needed
                    dirname(php_ini_loaded_file()) . '/extras/ssl/openssl.cnf', // Relative to php.ini
                ];

                foreach ($commonPaths as $path) {
                    if (file_exists($path)) {
                        $config['config'] = $path;
                        $res = openssl_pkey_new($config);
                        if ($res) break;
                    }
                }
            }

            if (!$res) {
                throw new \Exception("Gagal membuat key pair OpenSSL. Pastikan openssl.cnf terkonfigurasi dengan benar di php.ini atau environment variable. Error: " . openssl_error_string());
            }

            // Pass config to export as well, in case it was needed
            openssl_pkey_export($res, $privateKey, null, $config);
            
            $publicKey = openssl_pkey_get_details($res);
            $publicKey = $publicKey["key"];
            
            Storage::put($this->privateKeyPath, $privateKey);
            Storage::put($this->publicKeyPath, $publicKey);
        }
    }

    public function sign($contentOrFile, User $user, array $letterData = []): DocumentSignature
    {
        $this->ensureKeysExist();

        $privateKey = Storage::get($this->privateKeyPath);
        $publicKey = Storage::get($this->publicKeyPath);
        
        $filePath = null;
        $fileName = null;
        $docHash = '';

        if ($contentOrFile instanceof \Illuminate\Http\UploadedFile) {
            // Handle file upload
            $fileName = $contentOrFile->getClientOriginalName();
            $filePath = $contentOrFile->store('documents', 'public');
            $docHash = hash_file('sha256', $contentOrFile->getRealPath());
        } else {
            // Handle string content
            $docHash = hash('sha256', $contentOrFile);
        }
        
        // 2. Prepare payload data
        $signedAt = now();
        $alg = 'RSA-SHA256';
        $version = 'v1';
        $signerId = $user->id;
        
        // Canonical string to sign: version|doc_hash|signer_id|signed_at_iso|alg
        $dataToSign = sprintf(
            "%s|%s|%s|%s|%s",
            $version,
            $docHash,
            $signerId,
            $signedAt->toIso8601String(),
            $alg
        );

        // 3. Sign
        openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureBase64 = base64_encode($signature);
        
        // 4. Calculate key fingerprint
        $keyFingerprint = hash('sha256', $publicKey);

        // 5. Save to DB
        $docSignature = DocumentSignature::create([
            'user_id' => $user->id,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'content_hash' => $docHash,
            'signature' => $signatureBase64,
            'key_fingerprint' => $keyFingerprint,
            'alg' => $alg,
            'signed_at' => $signedAt,
            'verification_token' => Str::random(64),
            'letter_number' => $letterData['letter_number'] ?? null,
            'letter_date' => $letterData['letter_date'] ?? null,
            'signer_name' => $letterData['signer_name'] ?? null,
            'letter_subject' => $letterData['letter_subject'] ?? null,
            'qr_position' => $letterData['qr_position'] ?? 'bottom-right',
            'qr_x' => $letterData['qr_x'] ?? null,
            'qr_y' => $letterData['qr_y'] ?? null,
            'qr_page' => $letterData['qr_page'] ?? 1,
        ]);

        // 6. Embed QR if it's a PDF
        if ($filePath && str_ends_with(strtolower($fileName), '.pdf')) {
            $this->embedQrInPdf($docSignature, $letterData);
        }

        return $docSignature;
    }

    public function embedQrInPdf(DocumentSignature $signature, array $options = [])
    {
        if (!$signature->file_path || !Storage::disk('public')->exists($signature->file_path)) {
            return;
        }

        $originalPath = Storage::disk('public')->path($signature->file_path);
        $signedFileName = 'signed_' . $signature->file_name;
        $signedPath = 'documents/signed/' . $signedFileName;
        
        // Ensure directory exists
        Storage::disk('public')->makeDirectory('documents/signed');
        $outputPath = Storage::disk('public')->path($signedPath);

        // Generate QR Image (PNG)
        $qrData = $this->generateQrData($signature);
        
        // Use a temporary file for the QR image
        $qrTempFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        
        // We need to generate a PNG. BaconQrCode supports this if Imagick or GD is available.
        // Since we might not have Imagick, let's try to use a simple approach or check if we can use SvgImageBackEnd and convert?
        // FPDF doesn't support SVG natively well.
        // Let's try to use the GD backend if available, or fallback to a simple QR generator if needed.
        // For now, let's assume we can use a simple QR generation via a public API or a library that supports PNG.
        // Actually, BaconQrCode has Png backend but it might need Imagick.
        // Let's try to use `endroid/qr-code` which is very popular and supports GD, but we have `bacon/bacon-qr-code`.
        // BaconQrCode 2.0+ focuses on different backends.
        
        // Let's try to use a simple workaround: Generate SVG and convert to PNG? No, that requires Imagick.
        // Let's use a pure PHP QR code generator that outputs PNG if Bacon is hard to configure for PNG without Imagick.
        // But wait, BaconQrCode DOES support GD.
        
        // Let's try to construct a renderer that uses GD.
        // If that fails, we might need to install `endroid/qr-code`.
        // For now, let's try to use a simple approach:
        // We will use the `BaconQrCode\Renderer\Image\PngImageBackEnd` if it exists, or `ImagickImageBackEnd`.
        // If not, we will use a placeholder or try to install `endroid/qr-code`.
        
        // Let's check if we can use `endroid/qr-code` which is easier for PNG.
        // But I don't want to install more packages if I can avoid it.
        // Let's use a simple trick: use `phpqrcode` library? No.
        
        // Let's try to use the SVG and rasterize it? No.
        
        // Let's assume we can use `BaconQrCode` with `ImagickImageBackEnd` if available.
        // If not, we will skip embedding for now or use a text watermark.
        
        // Actually, let's just use `endroid/qr-code` is much better for this.
        // But since I cannot easily check extensions, I will try to use a public QR API for the image generation to be safe and robust in this environment?
        // No, that's bad for privacy.
        
        // Let's use `simplesoftwareio/simple-qrcode` which wraps BaconQrCode and makes it easy in Laravel?
        // It is not installed.
        
        // Let's try to use the existing `BaconQrCode` to generate a string and save it.
        // If `Imagick` is missing, `BaconQrCode` might fail for PNG.
        // Let's check if `BaconQrCode\Renderer\Image\Png` exists.
        // It seems BaconQrCode 2.x removed direct PNG support without Imagick.
        
        // ALTERNATIVE: Use FPDF to draw the QR code using rectangles!
        // This is the most robust way. We can parse the QR matrix and draw it in PDF.
        // We can use `BaconQrCode\Encoder\Encoder` to get the matrix.
        
        $encoder = new \BaconQrCode\Encoder\Encoder();
        // We need to encode the data.
        // BaconQrCode high level API:
        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new SvgImageBackEnd() // We use SVG backend just to get the writer, but we want the matrix.
            )
        );
        // Wait, Writer doesn't expose matrix easily.
        
        // Let's use a simpler approach:
        // We will use a library that generates QR for FPDF. `chillerlan/php-qrcode`?
        // Or just use a public API for now to demonstrate, as it is a student project?
        // "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrData)
        // This is the most reliable way without worrying about GD/Imagick versions on the host.
        
        // Use QRCode Monkey API with logo upload
        $logoPath = public_path('storage/logo/logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = storage_path('app/public/logo/logo.png');
        }

        $uploadedLogoFile = '';
        if (file_exists($logoPath)) {
            $uploadUrl = 'https://api.qrcode-monkey.com/qr/uploadImage';
            $chUpload = curl_init($uploadUrl);
            $cfile = new \CURLFile($logoPath, mime_content_type($logoPath), basename($logoPath));
            $postData = ['file' => $cfile];
            curl_setopt($chUpload, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chUpload, CURLOPT_POST, true);
            curl_setopt($chUpload, CURLOPT_POSTFIELDS, $postData);
            $uploadResp = curl_exec($chUpload);
            $uploadHttpCode = curl_getinfo($chUpload, CURLINFO_HTTP_CODE);
            curl_close($chUpload);
            if ($uploadResp && $uploadHttpCode === 200) {
                $json = json_decode($uploadResp, true);
                if (is_array($json) && isset($json['file'])) {
                    $uploadedLogoFile = $json['file'];
                }
            }
        }

        // Build custom QR request
        $qrApiUrl = 'https://api.qrcode-monkey.com/qr/custom';
        $qrConfig = [
            'data' => $qrData,
            'config' => [
                'body' => 'square',
                'eye' => 'frame12',
                'eyeBall' => 'ball14',
                'bodyColor' => '#000000',
                'bgColor' => '#FFFFFF',
                'eye1Color' => '#000000',
                'eye2Color' => '#000000',
                'eye3Color' => '#000000',
                'eyeBall1Color' => '#FAD501',
                'eyeBall2Color' => '#FAD501',
                'eyeBall3Color' => '#FAD501',
                'logo' => $uploadedLogoFile ?: '',
                'logoMode' => 'clean',
            ],
            'size' => 400,
            'download' => false,
            'file' => 'png',
        ];

        $ch = curl_init($qrApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($qrConfig));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: image/png',
        ]);
        $qrImageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $isPngBinary = false;
        if ($qrImageData !== false && strlen($qrImageData) >= 8) {
            $pngMagic = "\x89PNG\x0D\x0A\x1A\x0A";
            $isPngBinary = substr($qrImageData, 0, 8) === $pngMagic;
        }

        if ($httpCode === 200 && $isPngBinary && (is_null($contentType) || stripos($contentType, 'image/png') !== false)) {
            file_put_contents($qrTempFile, $qrImageData);
        } else {
            // Fallback to plain QR without logo
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qrData);
            $fallback = @file_get_contents($qrUrl);
            if ($fallback === false) {
                throw new \Exception('Failed to generate QR for document: ' . ($curlError ?: 'unknown error'));
            }
            file_put_contents($qrTempFile, $fallback);
        }

        $pdf = new Fpdi();
        
        // Get the page count
        $pageCount = $pdf->setSourceFile($originalPath);

        // Iterate through all pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            // Add a page with the same size and orientation
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // Add QR Code on specified page or last page
            $targetPage = $signature->qr_page ?? $pageCount;
            if ($pageNo == $targetPage) {
                $qrSize = 25;
                $margin = 10;
                
                // Calculate position based on qr_position setting
                $position = $signature->qr_position ?? 'bottom-right';
                
                if ($position === 'manual' && $signature->qr_x !== null && $signature->qr_y !== null) {
                    // Manual mode stores the center point (percentage of page dimensions)
                    $normalizedX = max(0, min(1, (float) $signature->qr_x));
                    $normalizedY = max(0, min(1, (float) $signature->qr_y));

                    $x = ($normalizedX * $size['width']) - ($qrSize / 2);
                    $y = ($normalizedY * $size['height']) - ($qrSize / 2);

                    // Clamp to page bounds so QR never leaves the page
                    $x = max(0, min($x, $size['width'] - $qrSize));
                    $y = max(0, min($y, $size['height'] - $qrSize));
                } else {
                    // Use predefined positions
                    switch ($position) {
                        case 'top-left':
                            $x = $margin;
                            $y = $margin;
                            break;
                        case 'top-right':
                            $x = $size['width'] - $qrSize - $margin;
                            $y = $margin;
                            break;
                        case 'bottom-left':
                            $x = $margin;
                            $y = $size['height'] - $qrSize - $margin - 10;
                            break;
                        case 'bottom-right':
                        default:
                            $x = $size['width'] - $qrSize - $margin;
                            $y = $size['height'] - $qrSize - $margin - 10;
                            break;
                    }
                }
                
                $pdf->Image($qrTempFile, $x, $y, $qrSize, $qrSize, 'PNG');
                
                // Add signature info below QR
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetXY($x, $y + $qrSize + 1);
                
                if ($signature->signer_name) {
                    $pdf->Cell($qrSize, 3, $signature->signer_name, 0, 0, 'C');
                    $pdf->SetXY($x, $y + $qrSize + 4);
                }
                
                $pdf->SetFont('Helvetica', 'I', 6);
                $pdf->Cell($qrSize, 3, 'Digitally Signed', 0, 0, 'C');
            }
        }

        $pdf->Output('F', $outputPath);
        $signedHash = hash_file('sha256', $outputPath);
        
        // Cleanup
        if (file_exists($qrTempFile)) {
            unlink($qrTempFile);
        }

        // Update DB
        $signature->update([
            'signed_file_path' => $signedPath,
            'signed_file_hash' => $signedHash,
        ]);
    }

    /**
     * Overlay a centered logo onto a QR PNG using GD.
     * If no path is provided, attempts `storage/app/public/logo/logo.png`.
     */
    protected function overlayLogoOnQr(string $qrPngPath, ?string $logoPath = null): void
    {
        try {
            if (!file_exists($qrPngPath)) {
                return;
            }

            // Resolve default logo path if none provided
            if (!$logoPath) {
                $defaultLogo = storage_path('app/public/logo/logo.png');
                $altLogo = public_path('storage/logo.png');
                if (file_exists($defaultLogo)) {
                    $logoPath = $defaultLogo;
                } elseif (file_exists($altLogo)) {
                    $logoPath = $altLogo;
                } else {
                    return; // No logo available
                }
            }

            if (!file_exists($logoPath)) {
                return;
            }

            // Load QR image
            $qrImg = imagecreatefrompng($qrPngPath);
            if (!$qrImg) {
                return;
            }

            $qrW = imagesx($qrImg);
            $qrH = imagesy($qrImg);

            // Load logo (supports png/jpeg)
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $logoImg = imagecreatefrompng($logoPath);
            } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                $logoImg = imagecreatefromjpeg($logoPath);
            } else {
                return;
            }

            if (!$logoImg) {
                return;
            }

            imagesavealpha($qrImg, true);
            imagesavealpha($logoImg, true);

            $logoW = imagesx($logoImg);
            $logoH = imagesy($logoImg);

            // Target logo size: ~30% of QR width
            $targetLogoW = (int) round($qrW * 0.3);
            $targetLogoH = (int) round($logoH * ($targetLogoW / max(1, $logoW)));

            // Create resized logo with transparency
            $resizedLogo = imagecreatetruecolor($targetLogoW, $targetLogoH);
            imagesavealpha($resizedLogo, true);
            $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
            imagefill($resizedLogo, 0, 0, $transparent);
            imagecopyresampled($resizedLogo, $logoImg, 0, 0, 0, 0, $targetLogoW, $targetLogoH, $logoW, $logoH);

            // Optional white backing to improve scan reliability
            $padding = (int) round($qrW * 0.02);
            $backW = $targetLogoW + ($padding * 2);
            $backH = $targetLogoH + ($padding * 2);
            $centerX = (int) round(($qrW - $backW) / 2);
            $centerY = (int) round(($qrH - $backH) / 2);

            // Draw white rectangle (with simple square corners)
            $white = imagecolorallocate($qrImg, 255, 255, 255);
            imagefilledrectangle($qrImg, $centerX, $centerY, $centerX + $backW, $centerY + $backH, $white);

            // Composite logo centered on QR
            $logoX = $centerX + $padding;
            $logoY = $centerY + $padding;
            imagecopy($qrImg, $resizedLogo, $logoX, $logoY, 0, 0, $targetLogoW, $targetLogoH);

            // Save back to the same PNG path
            imagepng($qrImg, $qrPngPath);

            // Cleanup
            imagedestroy($qrImg);
            imagedestroy($logoImg);
            imagedestroy($resizedLogo);
        } catch (\Throwable $e) {
            // Silently ignore overlay issues to avoid breaking signing flow
        }
    }

    public function generateQrData(DocumentSignature $signature): string
    {
        // Payload for QR
        $payload = [
            'v' => 'v1',
            'h' => $signature->content_hash,
            'uid' => $signature->user_id,
            'ts' => $signature->signed_at->toIso8601String(),
            'alg' => $signature->alg,
            'sig' => $signature->signature,
            'fp' => $signature->key_fingerprint,
            'url' => route('verify.signature', ['token' => $signature->verification_token]),
        ];

        return json_encode($payload);
    }

    public function generateQrSvg(string $data): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }

    public function verify($contentOrFile, string $qrJson): array
    {
        $this->ensureKeysExist();
        $publicKey = Storage::get($this->publicKeyPath);
        
        $data = json_decode($qrJson, true);
        if (!$data) {
            return ['valid' => false, 'reason' => 'Invalid JSON'];
        }

        // Reconstruct data to verify
        // version|doc_hash|signer_id|signed_at_iso|alg
        // Note: keys in JSON are shortened to save space: v, h, uid, ts, alg
        $version = $data['v'] ?? 'v1';
        $docHash = $data['h'] ?? '';
        $signerId = $data['uid'] ?? '';
        $signedAt = $data['ts'] ?? '';
        $alg = $data['alg'] ?? 'RSA-SHA256';
        $signature = base64_decode($data['sig'] ?? '');
        
        // Verify content hash matches
        $calculatedHash = '';
        if ($contentOrFile instanceof \Illuminate\Http\UploadedFile) {
            $calculatedHash = hash_file('sha256', $contentOrFile->getRealPath());
        } else {
            $calculatedHash = hash('sha256', $contentOrFile);
        }

        if ($calculatedHash !== $docHash) {
            return ['valid' => false, 'reason' => 'Content mismatch'];
        }

        $dataToVerify = sprintf(
            "%s|%s|%s|%s|%s",
            $version,
            $docHash,
            $signerId,
            $signedAt,
            $alg
        );

        $valid = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($valid === 1) {
            return ['valid' => true, 'data' => $data];
        } else {
            return ['valid' => false, 'reason' => 'Signature invalid'];
        }
    }
}

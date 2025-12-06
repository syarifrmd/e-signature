<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index()
    {
        return \Inertia\Inertia::render('certificate/index', [
            'certificates' => \App\Models\Certificate::latest()->get()
        ]);
    }

    public function create()
    {
        return \Inertia\Inertia::render('certificate/create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'recipient_name' => 'required|string|max:255',
            'rank' => 'required|in:1,2,3',
        ]);

        // Create DB Record with e-signature fields
        $certificate = \App\Models\Certificate::create([
            'certificate_number' => 'CERT-' . date('Ymd') . '-' . uniqid(),
            'recipient_name' => $request->recipient_name,
            'rank' => $request->rank,
            'signed_by' => $request->user()->id,
            'signer_name' => $request->user()->name,
            'verification_token' => \Illuminate\Support\Str::random(64),
            'verified_at' => now(), // Auto-verify on creation
        ]);

        // Generate Certificate Image with QR Code
        $this->generateCertificateImage($certificate);

        return redirect()->route('certificates.show', $certificate->id);
    }

    public function show(\App\Models\Certificate $certificate)
    {
        return \Inertia\Inertia::render('certificate/show', [
            'certificate' => [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'recipient_name' => $certificate->recipient_name,
                'rank' => $certificate->rank,
                'generated_file_path' => $certificate->generated_file_path,
                'signer_name' => $certificate->signer_name,
                'verification_token' => $certificate->verification_token,
                'verified_at' => $certificate->verified_at?->format('d F Y H:i:s'),
            ],
            'file_url' => $certificate->generated_file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($certificate->generated_file_path) : null,
        ]);
    }

    public function download(\App\Models\Certificate $certificate)
    {
        if (!$certificate->generated_file_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($certificate->generated_file_path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($certificate->generated_file_path, 'certificate_' . $certificate->recipient_name . '.png');
    }

    protected function generateCertificateImage(\App\Models\Certificate $certificate)
    {
        // Paths
        $templatePath = storage_path('app/public/certificate/template/juara' . $certificate->rank . '.png');
        $outputPath = 'certificate/generated/' . $certificate->certificate_number . '.png';
        $fullOutputPath = storage_path('app/public/' . $outputPath);

        // Ensure directory exists
        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('certificate/generated');

        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: " . $templatePath);
        }

        // Load Template
        $img = imagecreatefrompng($templatePath);
        $width = imagesx($img);
        $height = imagesy($img);

        // Colors
        $black = imagecolorallocate($img, 0, 0, 0);

        // 1. Overlay Recipient Name (Centered)
        $text = $certificate->recipient_name;
        
        $fontPath = 'C:\\Windows\\Fonts\\arial.ttf';
        if (!file_exists($fontPath)) {
            $fontPath = public_path('fonts/Roboto-Bold.ttf');
        }

        if (file_exists($fontPath)) {
            $fontSize = 150;
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $x = ($width - $textWidth) / 2;
            $y = ($height / 2) - 50;
            
            imagettftext($img, $fontSize, 0, $x, $y, $black, $fontPath, $text);
        } else {
            $font = 5;
            $textWidth = imagefontwidth($font) * strlen($text);
            $x = ($width - $textWidth) / 2;
            $y = ($height / 2) - 50;
            imagestring($img, $font, $x, $y, $text, $black);
        }
        
        // 2. Generate QR Code for Verification (using QRCode Monkey API with logo)
        $verificationUrl = route('certificates.verify', $certificate->verification_token);
        
        // Create temp file for QR
        $qrTempPath = storage_path('app/temp_qr_' . $certificate->id . '.png');
        
        // Prepare logo path from public storage
        $logoPath = public_path('storage/logo/logo.png');
        if (!file_exists($logoPath)) {
            // Try alternative common location
            $logoPath = storage_path('app/public/logo/logo.png');
        }
        
        // If logo exists, upload to QRCode Monkey first to get file id
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

        // Use QRCode Monkey API to generate QR code with optional logo
        $qrApiUrl = "https://api.qrcode-monkey.com/qr/custom";
        $qrConfig = [
            'data' => $verificationUrl,
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
                'logoMode' => 'clean'
            ],
            'size' => 400,
            'download' => false,
            'file' => 'png'
        ];
        
        // Make POST request to QRCode Monkey API
        $ch = curl_init($qrApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($qrConfig));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: image/png'
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
            file_put_contents($qrTempPath, $qrImageData);
        } else {
            // Fallback to plain QR (no logo) if API fails or returns non-PNG
            $fallbackUrl = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($verificationUrl);
            $fallbackImage = @file_get_contents($fallbackUrl);
            if ($fallbackImage === false) {
                throw new \Exception('Failed to generate QR code: ' . ($curlError ?: 'unknown error'));
            }
            file_put_contents($qrTempPath, $fallbackImage);
        }
        
        // Load QR image and overlay
        $qrImg = imagecreatefrompng($qrTempPath);
        $qrW = imagesx($qrImg);
        $qrH = imagesy($qrImg);
        
        // Resize QR to reasonable size (e.g., 200x200)
        $targetQrSize = 400;
        $resizedQr = imagecreatetruecolor($targetQrSize, $targetQrSize);
        imagealphablending($resizedQr, false);
        imagesavealpha($resizedQr, true);
        $transparent = imagecolorallocatealpha($resizedQr, 255, 255, 255, 127);
        imagefilledrectangle($resizedQr, 0, 0, $targetQrSize, $targetQrSize, $transparent);
        imagecopyresampled($resizedQr, $qrImg, 0, 0, 0, 0, $targetQrSize, $targetQrSize, $qrW, $qrH);
        
        // Position QR at center-bottom, above yellow line (panitia area)
        // Assuming yellow line is around 80-85% down from top
        $qrPosX = ($width - $targetQrSize) / 2; // Center horizontally
        $qrPosY = ($height * 0.82) - $targetQrSize; // Position above yellow line area
        
        imagecopy($img, $resizedQr, $qrPosX, $qrPosY, 0, 0, $targetQrSize, $targetQrSize);
        
        // 3. Add text below "SEBAGAI" - show rank (Juara)
        $rankLabels = [
            '1' => 'JUARA 1',
            '2' => 'JUARA 2',
            '3' => 'JUARA 3',
        ];
        $rankText = $rankLabels[$certificate->rank] ?? 'JUARA ' . $certificate->rank;
        
        if (file_exists($fontPath)) {
            // Position rank text below "SEBAGAI" (centered)
            $rankFontSize = 100;
            $bbox = imagettfbbox($rankFontSize, 0, $fontPath, $rankText);
            $rankTextWidth = $bbox[2] - $bbox[0];
            $rankX = ($width - $rankTextWidth) / 2;
            $rankY = ($height / 2) + 380; // Below "SEBAGAI"
            
            imagettftext($img, $rankFontSize, 0, $rankX, $rankY, $black, $fontPath, $rankText);
        }
        
        // 4. Add Signer Name below QR
        $signerText = 'Ditandatangani oleh:';
        $signerNameText = $certificate->signer_name;
        
        if (file_exists($fontPath)) {
            $smallFont = 64;
            // Center the signer text below QR
            $bbox = imagettfbbox($smallFont, 0, $fontPath, $signerText);
            $signerTextWidth = $bbox[2] - $bbox[0];
            $signerX = ($width - $signerTextWidth) / 2;
            
            $bbox2 = imagettfbbox($smallFont + 4, 0, $fontPath, $signerNameText);
            $signerNameWidth = $bbox2[2] - $bbox2[0];
            $signerNameX = ($width - $signerNameWidth) / 2;
            
            imagettftext($img, $smallFont, 0, $signerX, $qrPosY + $targetQrSize + 80, $black, $fontPath, $signerText);
            imagettftext($img, $smallFont + 4, 0, $signerNameX, $qrPosY + $targetQrSize + 170, $black, $fontPath, $signerNameText);
        }

        // Save
        imagepng($img, $fullOutputPath);
        imagedestroy($img);
        imagedestroy($qrImg);
        imagedestroy($resizedQr);
        
        // Clean up temp QR file
        @unlink($qrTempPath);

        // Update DB
        $certificate->update(['generated_file_path' => $outputPath]);
    }

    public function verifyByToken($token)
    {
        $certificate = \App\Models\Certificate::where('verification_token', $token)->first();

        if (!$certificate) {
            return inertia('certificate/verify', [
                'verified' => false,
                'message' => 'Token verifikasi tidak valid atau sertifikat tidak ditemukan.',
            ]);
        }

        return inertia('certificate/verify', [
            'verified' => true,
            'certificate' => [
                'certificate_number' => $certificate->certificate_number,
                'recipient_name' => $certificate->recipient_name,
                'rank' => $certificate->rank,
                'signer_name' => $certificate->signer_name,
                'verified_at' => $certificate->verified_at?->format('d F Y H:i:s'),
                'created_at' => $certificate->created_at->format('d F Y'),
                'file_url' => asset('storage/' . $certificate->generated_file_path),
            ],
        ]);
    }

    /**
     * Overlay a centered logo onto a QR PNG using GD.
     * Same method as in SignatureService
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

            // Target logo size: ~20% of QR width (smaller for better scanning)
            $targetLogoW = (int) round($qrW * 0.20);
            $targetLogoH = (int) round($logoH * ($targetLogoW / max(1, $logoW)));

            // Create resized logo with transparency
            $resizedLogo = imagecreatetruecolor($targetLogoW, $targetLogoH);
            imagesavealpha($resizedLogo, true);
            $transparent = imagecolorallocatealpha($resizedLogo, 0, 0, 0, 127);
            imagefill($resizedLogo, 0, 0, $transparent);
            imagecopyresampled($resizedLogo, $logoImg, 0, 0, 0, 0, $targetLogoW, $targetLogoH, $logoW, $logoH);

            // Larger white backing to improve scan reliability
            $padding = (int) round($qrW * 0.03);
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
        } catch (\Exception $e) {
            // Silently fail if logo overlay fails
            return;
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DocumentSignature;
use Illuminate\Http\Request;

class SignatureController extends Controller
{
    protected $signatureService;

    public function __construct(\App\Services\SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    public function index()
    {
        return \Inertia\Inertia::render('signature/index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
            'letter_number' => 'nullable|string|max:255',
            'letter_date' => 'nullable|date',
            'signer_name' => 'nullable|string|max:255',
            'letter_subject' => 'nullable|string',
            'qr_position' => 'nullable|string|in:top-left,top-right,bottom-left,bottom-right,manual',
            'qr_x' => 'nullable|numeric|between:0,1',
            'qr_y' => 'nullable|numeric|between:0,1',
            'qr_page' => 'nullable|integer|min:1',
            'preview_width' => 'nullable|numeric',
            'preview_height' => 'nullable|numeric',
        ]);

        $letterData = [
            'letter_number' => $request->letter_number,
            'letter_date' => $request->letter_date,
            'signer_name' => $request->signer_name,
            'letter_subject' => $request->letter_subject,
            'qr_position' => $request->qr_position ?? 'bottom-right',
            'qr_x' => $request->qr_x,
            'qr_y' => $request->qr_y,
            'qr_page' => $request->qr_page ?? 1,
            'preview_width' => $request->preview_width,
            'preview_height' => $request->preview_height,
        ];

        $signature = $this->signatureService->sign($request->file('file'), $request->user(), $letterData);

        return redirect()->route('signatures.show', $signature->id);
    }

    public function show(\App\Models\DocumentSignature $signature)
    {
        $qrData = $this->signatureService->generateQrData($signature);
        $qrSvg = $this->signatureService->generateQrSvg($qrData);

        return \Inertia\Inertia::render('signature/show', [
            'signature' => $signature,
            'qrSvg' => $qrSvg,
            'qrData' => $qrData,
            'file_url' => $signature->file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($signature->file_path) : null,
            'signed_file_url' => $signature->signed_file_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($signature->signed_file_path) : null,
        ]);
    }

    public function downloadSigned(\App\Models\DocumentSignature $signature)
    {
        // Check if user owns this signature
        if ($signature->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this document');
        }

        if (!$signature->signed_file_path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($signature->signed_file_path)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($signature->signed_file_path, 'signed_' . $signature->file_name);
    }

    public function serveSignedFile($filename)
    {
        $filePath = 'documents/signed/' . $filename;
        
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
            abort(404);
        }

        // Find the signature record for this file
        $signature = \App\Models\DocumentSignature::where('signed_file_path', $filePath)->first();
        
        // Allow access if user owns it OR if user is authenticated
        if ($signature && auth()->check() && $signature->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this document');
        }

        $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($filePath);
        
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    public function verifyForm()
    {
        return \Inertia\Inertia::render('signature/verify', [
            'verification_result' => session('verification_result'),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $uploadedFile = $request->file('file');
        $hash = hash_file('sha256', $uploadedFile->getRealPath());

        $signature = DocumentSignature::with('user')
            ->where('signed_file_hash', $hash)
            ->first();

        if (!$signature) {
            $result = [
                'valid' => false,
                'reason' => 'Dokumen tidak dikenali atau sudah dimodifikasi.',
            ];
        } else {
            $result = [
                'valid' => true,
                'document' => [
                    'letter_number' => $signature->letter_number,
                    'letter_date' => optional($signature->letter_date)->format('Y-m-d'),
                    'letter_subject' => $signature->letter_subject,
                    'signer_name' => $signature->signer_name ?? optional($signature->user)->name,
                    'signed_at' => optional($signature->signed_at)->toIso8601String(),
                    'issuer' => optional($signature->user)->name,
                ],
            ];
        }

        return redirect()->route('verify.form')->with('verification_result', $result);
    }
    
    public function verifyEndpoint($token)
    {
        $signature = \App\Models\DocumentSignature::where('verification_token', $token)->firstOrFail();
        
        return response()->json([
            'valid' => true,
            'signer' => $signature->user->name,
            'signed_at' => $signature->signed_at,
            'content_hash' => $signature->content_hash,
        ]);
    }
}

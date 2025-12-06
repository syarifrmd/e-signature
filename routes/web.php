<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\SignatureController;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('signatures/create', [SignatureController::class, 'index'])->name('signatures.create');
    Route::post('signatures', [SignatureController::class, 'store'])->name('signatures.store');
    Route::get('signatures/{signature}', [SignatureController::class, 'show'])->name('signatures.show');
    // Moved below to allow download for authenticated users even if not verified
    // Route::get('signatures/{signature}/download', [SignatureController::class, 'downloadSigned'])->name('signatures.download');
    Route::get('verify', [SignatureController::class, 'verifyForm'])->name('verify.form');
    Route::post('verify', [SignatureController::class, 'verify'])->name('verify.check');

    // Certificate Routes
    Route::get('certificates', [\App\Http\Controllers\CertificateController::class, 'index'])->name('certificates.index');
    Route::get('certificates/create', [\App\Http\Controllers\CertificateController::class, 'create'])->name('certificates.create');
    Route::post('certificates', [\App\Http\Controllers\CertificateController::class, 'store'])->name('certificates.store');
    Route::get('certificates/{certificate}', [\App\Http\Controllers\CertificateController::class, 'show'])->name('certificates.show');
    Route::get('certificates/{certificate}/download', [\App\Http\Controllers\CertificateController::class, 'download'])->name('certificates.download');
    
    // Certificate Verification Routes (public access)
    Route::get('certificates/verify/{token}', [\App\Http\Controllers\CertificateController::class, 'verifyByToken'])->name('certificates.verify');
});

// Allow downloading signed documents for authenticated users without email verification
Route::middleware(['auth'])->group(function () {
    Route::get('signatures/{signature}/download', [SignatureController::class, 'downloadSigned'])->name('signatures.download');
});

// Public route to serve signed files directly
Route::get('storage/documents/signed/{filename}', [SignatureController::class, 'serveSignedFile'])->name('storage.signed');

Route::get('verify-signature/{token}', [SignatureController::class, 'verifyEndpoint'])->name('verify.signature');

require __DIR__.'/settings.php';

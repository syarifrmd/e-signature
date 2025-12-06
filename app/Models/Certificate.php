<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'certificate_number',
        'recipient_name',
        'rank',
        'signed_by',
        'signer_name',
        'verification_token',
        'verified_at',
        'generated_file_path',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}

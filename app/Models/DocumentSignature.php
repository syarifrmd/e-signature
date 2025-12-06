<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'file_name',
        'signed_file_path',
        'signed_file_hash',
        'content_hash',
        'signature',
        'key_fingerprint',
        'alg',
        'signed_at',
        'verification_token',
        'letter_number',
        'letter_date',
        'signer_name',
        'letter_subject',
        'qr_position',
        'qr_x',
        'qr_y',
        'qr_page',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'letter_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

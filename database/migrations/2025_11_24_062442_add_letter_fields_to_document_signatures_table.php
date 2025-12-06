<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->string('letter_number')->nullable()->after('signed_file_path');
            $table->date('letter_date')->nullable()->after('letter_number');
            $table->string('signer_name')->nullable()->after('letter_date');
            $table->text('letter_subject')->nullable()->after('signer_name');
            $table->string('qr_position', 20)->default('bottom-right')->after('letter_subject');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->dropColumn(['letter_number', 'letter_date', 'signer_name', 'letter_subject', 'qr_position']);
        });
    }
};

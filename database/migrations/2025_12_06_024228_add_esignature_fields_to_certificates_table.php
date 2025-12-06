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
        Schema::table('certificates', function (Blueprint $table) {
            // Remove old signature_path column
            $table->dropColumn('signature_path');
            
            // Add e-signature fields
            $table->unsignedBigInteger('signed_by')->nullable()->after('rank');
            $table->string('signer_name')->after('signed_by');
            $table->string('verification_token', 64)->unique()->after('signer_name');
            $table->timestamp('verified_at')->nullable()->after('verification_token');
            
            // Foreign key to users table
            $table->foreign('signed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropForeign(['signed_by']);
            $table->dropColumn(['signed_by', 'signer_name', 'verification_token', 'verified_at']);
            $table->string('signature_path')->nullable();
        });
    }
};

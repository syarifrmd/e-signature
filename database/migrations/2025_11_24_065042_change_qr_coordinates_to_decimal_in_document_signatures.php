<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, reset any manual coordinates to null since they're in old pixel format
        DB::table('document_signatures')
            ->where('qr_position', 'manual')
            ->update(['qr_x' => null, 'qr_y' => null]);
            
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->decimal('qr_x', 5, 4)->nullable()->change();
            $table->decimal('qr_y', 5, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->integer('qr_x')->nullable()->change();
            $table->integer('qr_y')->nullable()->change();
        });
    }
};

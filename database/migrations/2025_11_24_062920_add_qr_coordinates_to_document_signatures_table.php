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
            $table->integer('qr_x')->nullable()->after('qr_position');
            $table->integer('qr_y')->nullable()->after('qr_x');
            $table->integer('qr_page')->default(1)->after('qr_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signatures', function (Blueprint $table) {
            $table->dropColumn(['qr_x', 'qr_y', 'qr_page']);
        });
    }
};

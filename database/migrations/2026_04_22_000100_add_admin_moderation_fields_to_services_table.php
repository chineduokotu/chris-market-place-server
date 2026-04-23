<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('price');
            $table->text('moderation_note')->nullable()->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'moderation_note']);
        });
    }
};

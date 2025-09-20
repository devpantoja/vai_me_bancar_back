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
        Schema::table('donates', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('donates', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'cancelled'])->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('donates', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('donates', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->after('amount');
        });
    }
};

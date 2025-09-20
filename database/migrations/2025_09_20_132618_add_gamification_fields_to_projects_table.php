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
        Schema::table('projects', function (Blueprint $table) {
            $table->decimal('goal_amount', 10, 2)->default(0)->after('budget');
            $table->decimal('current_amount', 10, 2)->default(0)->after('goal_amount');
            $table->string('status')->default('active')->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['goal_amount', 'current_amount', 'status']);
        });
    }
};

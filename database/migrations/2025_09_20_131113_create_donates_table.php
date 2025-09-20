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
        Schema::create('donates', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->foreignId('project_id')->constrained('projects');
            $table->string('donor_name');
            $table->string('cellphone');
            $table->string('asaas_cliente_id')->nullable()->comment('ID do cliente no Asaas');
            $table->string('asaas_cobranca_id')->nullable()->comment('ID da cobrança no Asaas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donates');
    }
};

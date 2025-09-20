<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donate extends Model
{
    protected $fillable = [
        'amount',
        'status',
        'project_id',
        'donor_name',
        'cellphone',
        'asaas_cliente_id',
        'asaas_cobranca_id'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

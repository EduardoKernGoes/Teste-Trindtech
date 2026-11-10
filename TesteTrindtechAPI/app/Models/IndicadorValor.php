<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicadorValor extends Model
{
    use HasFactory;

    protected $table = 'indicador_valores';

    protected $fillable = [
        'indicador_id',
        'data_referencia',
        'valor',
    ];

    protected $casts = [
        'data_referencia' => 'date:Y-m-d',
    ];

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class, 'indicador_id');
    }
}

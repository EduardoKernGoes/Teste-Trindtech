<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicador extends Model
{
    use HasFactory;

    protected $table = 'indicadores';

    protected $fillable = [
        'nome',
        'codigo_sgs',
    ];

    public function valores(): HasMany
    {
        return $this->hasMany(IndicadorValor::class, 'indicador_id');
    }
}

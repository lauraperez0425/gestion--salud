<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovimientoFarmacia extends Model
{
    use SoftDeletes;

    protected $table = 'movimientos_farmacia';

    protected $fillable = [
        'medicamento_id',
        'tipo',
        'cantidad',
        'detalle',
        'fecha'
    ];

    // Un movimiento pertenece a un medicamento
    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }
}
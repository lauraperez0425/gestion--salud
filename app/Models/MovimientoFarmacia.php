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
        'receta_medica_id',
        'farmaceutico_id',
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

    public function recetaMedica()
    {
        return $this->belongsTo(RecetaMedica::class);
    }

    public function farmaceutico()
    {
        return $this->belongsTo(User::class, 'farmaceutico_id');
    }
}
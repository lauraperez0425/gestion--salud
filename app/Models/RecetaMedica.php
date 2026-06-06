<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecetaMedica extends Model
{
    use SoftDeletes;

    protected $table = 'recetas_medicas';

    protected $fillable = [
        'cita_id',
        'medicamento_id',
        'dosis',
        'frecuencia',
        'duracion',
        'indicaciones',
        'estado_despacho',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class);
    }

    public function medicamento()
    {
        return $this->belongsTo(Medicamento::class);
    }

    public function movimientosFarmacia()
    {
        return $this->hasMany(MovimientoFarmacia::class);
    }
}
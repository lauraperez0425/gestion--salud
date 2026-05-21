<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoriaClinica extends Model
{
    use SoftDeletes;

    protected $table = 'historias_clinicas';

    protected $fillable = [
        'paciente_id',
        'motivo_consulta',
        'enfermedad_actual',
        'peso',
        'talla',
        'presion_arterial',
        'saturacion',
        'temperatura',
        'diagnostico',
        'tratamiento',
        'observaciones',
        'fecha'
    ];

    // Una historia clínica pertenece a un paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
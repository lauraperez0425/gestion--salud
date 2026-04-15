<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VigenciaDerecho extends Model
{
    use SoftDeletes;

    protected $table = 'vigencia_derechos';

    protected $fillable = [
        'paciente_id',
        'fecha_inicio',
        'fecha_fin',
        'estado'
    ];

    // Una vigencia pertenece a un paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }
}
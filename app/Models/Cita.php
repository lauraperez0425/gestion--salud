<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cita extends Model
{
    use SoftDeletes;

    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'medico_id',
        'fecha',
        'hora',
        'estado'
    ];

    // Una cita pertenece a un paciente
    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    // Una cita pertenece a un médico (usuario)
    public function medico()
    {
        return $this->belongsTo(User::class, 'medico_id');
    }
}
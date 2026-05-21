<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSangre extends Model
{
    protected $table = 'tipos_sangre';

    protected $fillable = [
        'nombre',
    ];

    public function pacientes()
    {
        return $this->hasMany(Paciente::class, 'tipo_sangre_id');
    }
}

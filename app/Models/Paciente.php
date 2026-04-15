<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'ci',
        'fecha_nacimiento',
        'telefono',
        'direccion',
        'seguro',
        'estado'
    ];

    // Relación: un paciente tiene muchas citas
    public function citas()
    {
        return $this->hasMany(Cita::class);
    }

    // Relación: un paciente tiene muchas historias clínicas
    public function historiasClinicas()
    {
        return $this->hasMany(HistoriaClinica::class);
    }

    // Relación: un paciente tiene vigencia de derechos
    public function vigencias()
    {
        return $this->hasMany(VigenciaDerecho::class);
    }
}
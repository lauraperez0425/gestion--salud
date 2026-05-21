<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paciente extends Model
{
    use SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'user_id',
        'nombre',
        'apellido',
        'segundo_apellido',
        'ci',
        'fecha_nacimiento',
        'telefono',
        'direccion',
        'estatura',
        'peso',
        'tipo_sangre_id',
        'presion_arterial',
        'temperatura',
        'oxigeno_sangre',
        'seguro',
        'estado'
    ];

    protected static function booted(): void
    {
        static::deleting(function (Paciente $paciente) {
            if ($paciente->user_id && $paciente->user) {
                $paciente->user->tokens()->delete();
                $paciente->user->delete();
            }
        });
    }

    // Relación: un paciente pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    // Relación: un paciente pertenece a un tipo de sangre
    public function tipoSangre()
    {
        return $this->belongsTo(TipoSangre::class, 'tipo_sangre_id');
    }
}
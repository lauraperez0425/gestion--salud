<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    protected $fillable = [
        'user_id',
        'tipo_evento',
        'ip',
        'user_agent',
        'endpoint',
        'metodo',
        'descripcion',
    ];

    // Relación: un log pertenece a un usuario (puede ser null si no estaba logueado)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
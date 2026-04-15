<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    // Relación: un rol tiene muchos usuarios
    public function users()
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}
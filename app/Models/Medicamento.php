<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicamento extends Model
{
    use SoftDeletes;

    protected $table = 'medicamentos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'stock',
        'precio',
        'estado'
    ];

    // Un medicamento tiene muchos movimientos
    public function movimientos()
    {
        return $this->hasMany(MovimientoFarmacia::class);
    }

    // Un medicamento puede estar en varias recetas médicas
    public function recetasMedicas()
    {
        return $this->hasMany(RecetaMedica::class);
    }
}
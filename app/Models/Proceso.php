<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proceso extends Model
{
    use HasFactory;

    protected $fillable = ['cliente_id', 'marca_id','modelo_id',
        'pulgada_id', 'falla','descripcion', 'estado','fecha_ingreso', 'fecha_salida' ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    public function pulgada()
    {
        return $this->belongsTo(Pulgada::class);
    }

    public function evidencias()
    {
        return $this->hasMany(Evidencia::class);
    }
}

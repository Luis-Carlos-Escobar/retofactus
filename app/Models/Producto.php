<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_id',
        'modelo_id',
        'pulgada_id',
        'marca_id',
        'precio',
        'stock',
        'numero_pieza',
        'descripcion',
    ];

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function tipo()
    {
        return $this->belongsTo(Tipo::class);
    }

    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    public function pulgada()
    {
        return $this->belongsTo(Pulgada::class);
    }


}

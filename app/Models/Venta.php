<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'fecha_venta',
        'total',
        'pagado',
        'restante',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(){
        return $this->hasMany(DetalleVenta::class);
    }

    public function productos(){
        return $this->belongsToMany(Producto::class, 'detalle_ventas')->withPivot('cantidad', 'precio_unitario', 'subtotal');
    }

    public function FactusPagos()
    {
        return [
            "customer" => [
                "name" => $this->cliente->nombre,
                
            ],
            "items" => $this->detalles->map(function($detalle) {
                return [
                    "description" => $detalle->producto->nombre,
                    "quantity" => $detalle->cantidad,
                    "unit_price" => $detalle->precio_unitario,
                ];
            })->toArray(),
            "total" => $this->total,
        ];
    }
}

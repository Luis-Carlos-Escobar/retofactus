<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pulgada extends Model
{
    use HasFactory;

    protected $fillable = ['medida'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidencia extends Model
{
    use HasFactory;

    protected $fillable = ['proceso_id', 'imagen', 'comentario'];

    public function proceso()
    {
        return $this->belongsTo(Proceso::class);
    }
}

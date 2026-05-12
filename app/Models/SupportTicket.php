<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory, EmpresaScope;

    protected $table = 'support_tickets';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'asunto',
        'prioridad',
        'descripcion',
        'estado',
        'respuesta',
        'atendido_por',
        'atendido_en',
    ];

    protected $casts = [
        'atendido_en' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function atendidoPor()
    {
        return $this->belongsTo(User::class, 'atendido_por');
    }
}
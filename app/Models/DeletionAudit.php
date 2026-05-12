<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletionAudit extends Model
{
    use HasFactory;

    protected $table = 'deletion_audits';

    protected $fillable = [
        'empresa_id',
        'cultivo_id',
        'user_id',
        'user_name',
        'reason',
        'route_name',
        'path',
        'target_key',
        'target_id',
        'target_type',
        'target_label',
        'target_display',
        'request_payload',
    ];

    protected $casts = [
        'request_payload' => 'array',
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'target_type',
        'target_label',
        'external_ids',
        'image_url',
        'action_url',
        'data_payload',
        'status',
        'recipients_count',
        'onesignal_id',
        'response_summary',
    ];

    protected $casts = [
        'external_ids' => 'array',
        'data_payload' => 'array',
        'recipients_count' => 'integer',
    ];
}

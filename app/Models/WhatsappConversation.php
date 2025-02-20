<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappConversation extends Model
{
    use HasFactory;
    protected $fillable = ['wa_id', 'messages'];

    protected $casts = [
        'messages' => 'array',
        'conversation' => 'array',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'active', 'notification_type_id'];

    public function notificationType()
    {
        return $this->belongsTo(NotificationType::class);
    }
}

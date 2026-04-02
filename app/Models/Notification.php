<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'utilisateur_id',
        'message',
        'type',
        'lu',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'cree_le' => 'datetime',
    ];

    /**
     * Get the utilisateur for this notification
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class);
    }
}

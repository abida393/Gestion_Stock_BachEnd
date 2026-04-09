<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapport extends Model
{
    use HasFactory;

    protected $table = 'rapports';

    const CREATED_AT = 'genere_le';
    const UPDATED_AT = 'updated_at'; // Assuming updated_at exists from timestamps() in migration

    protected $fillable = [
        'utilisateur_id',
        'type',
        'chemin_fichier',
        'genere_le',
    ];

    protected $casts = [
        'genere_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the utilisateur for this rapport
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class);
    }
}

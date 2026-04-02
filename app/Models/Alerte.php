<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    use HasFactory;

    protected $table = 'alertes';

    protected $fillable = [
        'produit_id',
        'seuil',
        'est_active',
        'declenche_le',
        'resolu_le',
    ];

    protected $casts = [
        'est_active' => 'boolean',
        'declenche_le' => 'datetime',
        'resolu_le' => 'datetime',
        'cree_le' => 'datetime',
    ];

    /**
     * Get the produit for this alerte
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}

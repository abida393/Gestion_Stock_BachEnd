<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueVentes extends Model
{
    use HasFactory;

    protected $table = 'historique_ventes';

    protected $fillable = [
        'produit_id',
        'date',
        'quantite',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the produit for this historique_ventes
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}

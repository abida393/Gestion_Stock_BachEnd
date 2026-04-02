<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneCommande extends Model
{
    use HasFactory;

    protected $table = 'ligne_commande';

    protected $fillable = [
        'commande_id',
        'produit_id',
        'quantite',
        'prix',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the commande for this ligne_commande
     */
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    /**
     * Get the produit for this ligne_commande
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }
}

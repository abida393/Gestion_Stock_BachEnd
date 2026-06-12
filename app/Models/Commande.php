<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Commande extends Model
{
    use HasFactory, Auditable;

    protected $table = 'commandes';

    protected $fillable = [
        'fournisseur_id',
        'date_commande',
        'statut',
        'total',
        'date_prevue_livraison',
        'date_reception_reelle',
    ];

    protected $casts = [
        'date_commande' => 'datetime',
        'date_prevue_livraison' => 'date',
        'date_reception_reelle' => 'date',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the fournisseur for this commande
     */
    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Get all lignes_commande for this commande
     */
    public function lignesCommande()
    {
        return $this->hasMany(LigneCommande::class);
    }
}

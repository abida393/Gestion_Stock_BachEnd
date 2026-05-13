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
    ];

    protected $casts = [
        'date_commande' => 'datetime',
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

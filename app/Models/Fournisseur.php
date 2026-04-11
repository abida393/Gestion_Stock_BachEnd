<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Commande;
use App\Models\Produit;

class Fournisseur extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'cree_le';
    const UPDATED_AT = 'mis_a_jour_le';
    const DELETED_AT = 'supprime_le';

    protected $table = 'fournisseurs';

    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'numero_fix',
        'adresse',
    ];

    protected $casts = [
        'cree_le' => 'datetime',
        'mis_a_jour_le' => 'datetime',
        'supprime_le' => 'datetime',
    ];

    /**
     * Get all produits for this fournisseur
     */
    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'produit_fournisseur')
            ->withPivot('prix_unitaire', 'delai_livraison_jours')
            ->withTimestamps('cree_le', 'mis_a_jour_le');
    }

    /**
     * Get all commandes for this fournisseur
     */
    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}


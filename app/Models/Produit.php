<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Categorie;
use App\Models\Fournisseur;
use App\Models\Alerte;
use App\Models\MouvementStock;
use App\Models\Prevision;
use App\Models\HistoriqueVentes;
use App\Models\LigneCommande;

class Produit extends Model
{
    use HasFactory, SoftDeletes;

    const CREATED_AT = 'cree_le';
    const UPDATED_AT = 'mis_a_jour_le';
    const DELETED_AT = 'supprime_le';

    protected $table = 'produits';

    protected $fillable = [
        'nom',
        'description',
        'quantite',
        'seuil_min',
        'categorie_id',
        'image',
        'prix',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'cree_le' => 'datetime',
        'mis_a_jour_le' => 'datetime',
        'supprime_le' => 'datetime',
    ];

    /**
     * Get the categorie for this produit
     */
    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * Get all fournisseurs for this produit
     */
    public function fournisseurs()
    {
        return $this->belongsToMany(Fournisseur::class, 'produit_fournisseur')
            ->withPivot('prix_unitaire', 'delai_livraison_jours')
            ->withTimestamps();
    }

    /**
     * Get all alertes for this produit
     */
    public function alertes()
    {
        return $this->hasMany(Alerte::class);
    }

    /**
     * Get all mouvement_stock for this produit
     */
    public function mouvementStock()
    {
        return $this->hasMany(MouvementStock::class);
    }

    /**
     * Get all previsions for this produit
     */
    public function previsions()
    {
        return $this->hasMany(Prevision::class);
    }

    /**
     * Get all historique_ventes for this produit
     */
    public function historiqueVentes()
    {
        return $this->hasMany(HistoriqueVentes::class);
    }

    /**
     * Get all ligne_commande for this produit
     */
    public function lignesCommande()
    {
        return $this->hasMany(LigneCommande::class);
    }
}

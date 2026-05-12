<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouvementStock extends Model
{
    use HasFactory;

    protected $table = 'mouvement_stock';

    const CREATED_AT = 'cree_le';
    const UPDATED_AT = null;

    protected $fillable = [
        'produit_id',
        'utilisateur_id',
        'quantite',
        'stock_apres',
        'note',
        'date_mouvement',
        'type',
    ];

    protected $casts = [
        'date_mouvement' => 'datetime',
        'cree_le' => 'datetime',
    ];

    /**
     * Get the produit for this mouvement
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Get the utilisateur for this mouvement
     */
    public function utilisateur()
    {
        return $this->belongsTo(User::class);
    }
}

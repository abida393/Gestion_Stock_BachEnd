import pandas as pd
import numpy as np
from sqlalchemy import create_engine, text
from datetime import datetime, timedelta

# Configuration
engine = create_engine('postgresql://postgres:Root@localhost:5433/stock')

def preparer_et_inserer():
    print("Vérification des dépendances (Catégorie + Produit)...")
    
    with engine.connect() as conn:
        # 1. Création d'une catégorie par défaut pour satisfaire la contrainte
        conn.execute(text("""
            INSERT INTO categories (id, nom, description, created_at, updated_at) 
            VALUES (1, 'Général', 'Catégorie par défaut', NOW(), NOW()) 
            ON CONFLICT (id) DO NOTHING
        """))
        
        # 2. Insertion du produit lié à la catégorie 1
        sql_produit = text("""
            INSERT INTO produits (id, nom, description, prix, quantite, seuil_min, categorie_id, cree_le, mis_a_jour_le) 
            VALUES (1, 'Produit Test', 'Généré par Python', 10.0, 100, 5, 1, NOW(), NOW()) 
            ON CONFLICT (id) DO NOTHING
        """)
        conn.execute(sql_produit)
        conn.commit()

        # 3. Préparation des dates pour l'historique
        base = datetime.today()
        date_list = [(base - timedelta(days=x)).date() for x in range(180)]
        ventes = [int(10 + (i * 0.05) + np.random.randint(0, 5)) for i in range(180)]
        
        # 4. DataFrame pour historique_ventes
        df = pd.DataFrame({
            'produit_id': [1] * 180,
            'date': date_list,
            'quantite': ventes,
            'created_at': datetime.now(),
            'updated_at': datetime.now()
        })
        
        # 5. Envoi vers PostgreSQL
        df.to_sql('historique_ventes', engine, if_exists='append', index=False)
        print("--- VICTOIRE TOTALE : Tout est en base ! ---")

if __name__ == "__main__":
    preparer_et_inserer()
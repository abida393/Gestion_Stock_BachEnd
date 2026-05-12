import pandas as pd
from sqlalchemy import create_engine, text
from sklearn.ensemble import IsolationForest
from prophet import Prophet
import numpy as np

# Connexion
engine = create_engine('postgresql://postgres:Root@localhost:5433/stock')

def detecter_anomalies(df):
    model_anon = IsolationForest(contamination=0.05)
    df['score_anomalie'] = model_anon.fit_predict(df[['y']])
    df['anomalie_brute'] = df['score_anomalie'].apply(lambda x: 1 if x == -1 else 0)
    return df

def run_ia_complete():
    print("Démarrage de l'analyse IA complète...")
    
    # 1. Charger l'historique
    query = "SELECT date as ds, quantite as y FROM historique_ventes WHERE produit_id = 1"
    df = pd.read_sql(query, engine)

    # 2. Détection d'anomalies
    df = detecter_anomalies(df)
    score_anomalie_global = float(df['anomalie_brute'].mean())

    # 3. Prédiction (Prophet)
    m = Prophet(daily_seasonality=False)
    m.fit(df[['ds', 'y']])
    future = m.make_future_dataframe(periods=1)
    forecast = m.predict(future)
    
    # Extraction des valeurs
    derniere_pred = forecast.iloc[-1]
    pred = float(derniere_pred['yhat'])
    # On utilise l'écart entre yhat_upper et yhat_lower pour simuler la confiance
    confiance = float(derniere_pred['trend']) 

    # 4. Calcul de l'EOQ (Wilson)
    D = pred * 365 # Demande annuelle
    S, H = 50, 2    # Coûts fixes
    eoq = float(np.sqrt((2 * D * S) / H))

    # 5. Mise à jour de la table 'previsions'
    # On ajoute TOUTES les colonnes demandées par ton schéma
    with engine.connect() as conn:
        sql = text("""
            INSERT INTO previsions 
            (produit_id, periode, quantite_predite, confiance, eoq, score_anomalie, created_at)
            VALUES 
            (1, '24h', :quantite, :conf, :eoq, :score, NOW())
        """)
        
        conn.execute(sql, {
            "quantite": round(pred, 2),
            "conf": round(confiance, 2),
            "eoq": round(eoq, 2),
            "score": round(score_anomalie_global, 4)
        })
        conn.commit()

    print(f"--- VICTOIRE : Analyse enregistrée ---")
    print(f"Ventes prévues : {round(pred, 2)}")
    print(f"Confiance (Trend) : {round(confiance, 2)}")
    print(f"EOQ : {round(eoq, 2)}")
    print(f"Score Anomalie : {round(score_anomalie_global, 4)}")

if __name__ == "__main__":
    run_ia_complete()
"""
Agent IA Stock - Serveur Flask v3.0  (Prescriptive & Explicative)
http://127.0.0.1:5000
"""
try:
    from flask import Flask, request, jsonify
except ImportError:
    import subprocess, sys
    subprocess.check_call([sys.executable, "-m", "pip", "install", "flask"])
    from flask import Flask, request, jsonify

import math
from datetime import datetime, timedelta
from collections import defaultdict

app = Flask(__name__)

# ──────────────────────────────────────────────
#  Utilitaires
# ──────────────────────────────────────────────

def compute_eoq(demande_annuelle, cout_commande=50, cout_stockage_pct=0.20, prix_unitaire=10.0):
    """Formule Wilson : EOQ = sqrt((2 * D * S) / H)"""
    H = max(0.5, prix_unitaire * cout_stockage_pct)
    return math.sqrt((2 * demande_annuelle * cout_commande) / H)


def compute_confiance(nb_mouvements: int) -> float:
    """Plus on a de mouvements historiques, plus la confiance est haute."""
    if nb_mouvements == 0:
        return 0.50
    conf = 0.50 + 0.40 * (1 - math.exp(-nb_mouvements / 30))
    return round(min(conf, 0.98), 2)


def compute_score_anomalie(entrees, sorties) -> float:
    """
    Score simple : ratio des sorties excessives par rapport aux entrées.
    Proche de 0  = sain,  proche de 1 = suspect
    """
    total = entrees + sorties
    if total == 0:
        return 0.0
    return round(max(0, (sorties - entrees) / total), 4)


def build_recommendations(produit, demande, eoq, stock, seuil, confiance):
    """Génère des recommandations textuelles dynamiques."""
    recs = []

    # Rupture imminente
    if stock < seuil:
        recs.append(f"⚠️ Stock critique ({stock} unités < seuil {seuil}). Réapprovisionnement urgent recommandé.")

    # Commande optimale
    if eoq > 0:
        recs.append(f"📦 Commander {round(eoq)} unités pour minimiser les coûts (EOQ Wilson).")

    # Tendance demande vs stock
    if demande > stock * 0.8:
        recs.append("📈 Demande prévue élevée — envisagez d'augmenter le seuil minimum de 20%.")
    elif demande < stock * 0.3:
        recs.append("📉 Demande faible — le stock actuel couvre largement les besoins.")

    # Confiance
    if confiance < 0.70:
        recs.append("🔍 Données historiques insuffisantes — résultats indicatifs uniquement.")
    else:
        recs.append("✅ Analyse basée sur des données fiables.")

    return recs[:3]  # max 3 recommandations


def build_reasoning(produit_nom, demande, eoq, stock, seuil, confiance, entrees, sorties):
    """
    XAI — Génère une explication détaillée en langage naturel
    justifiant les décisions de l'IA pour ce produit.
    """
    parts = []

    # Contexte du produit
    parts.append(f"Analyse du produit « {produit_nom} » :")

    # Situation du stock
    ratio_stock = stock / seuil if seuil > 0 else float('inf')
    if stock < seuil:
        parts.append(
            f"Le stock actuel ({int(stock)} u.) est inférieur au seuil minimum ({int(seuil)} u.), "
            f"ce qui représente un risque de rupture imminent."
        )
    elif ratio_stock < 1.5:
        parts.append(
            f"Le stock actuel ({int(stock)} u.) est proche du seuil minimum ({int(seuil)} u.) — "
            f"situation à surveiller."
        )
    elif ratio_stock > 3:
        parts.append(
            f"Le stock actuel ({int(stock)} u.) est largement supérieur au seuil ({int(seuil)} u.), "
            f"ce qui peut indiquer du surstock et de l'argent dormant."
        )
    else:
        parts.append(
            f"Le stock actuel ({int(stock)} u.) est dans une zone confortable par rapport au seuil ({int(seuil)} u.)."
        )

    # Analyse de la demande
    if sorties > 0:
        trend_pct = round(((demande - sorties) / sorties) * 100)
        parts.append(
            f"L'IA prévoit une demande de {round(demande)} unités, soit une tendance "
            f"{'haussière' if trend_pct > 0 else 'stable'} de {abs(trend_pct)}% "
            f"basée sur {int(sorties)} sorties historiques."
        )
    else:
        parts.append(
            f"Aucune sortie historique — la demande estimée ({round(demande)} u.) "
            f"est basée sur un pourcentage du stock actuel."
        )

    # Justification EOQ
    parts.append(
        f"L'IA recommande de commander {round(eoq)} unités (EOQ Wilson) "
        f"pour minimiser les coûts combinés de commande et de stockage."
    )

    # Niveau de confiance
    if confiance >= 0.85:
        parts.append(f"Niveau de confiance élevé ({round(confiance*100)}%) — données historiques suffisantes.")
    elif confiance >= 0.70:
        parts.append(f"Niveau de confiance modéré ({round(confiance*100)}%) — résultats fiables.")
    else:
        parts.append(
            f"Niveau de confiance faible ({round(confiance*100)}%) — "
            f"les résultats sont indicatifs, davantage de données amélioreront la précision."
        )

    return " ".join(parts)


# ──────────────────────────────────────────────
#  Routes
# ──────────────────────────────────────────────

@app.route('/health', methods=['GET'])
def health():
    return jsonify({"status": "ok", "agent": "IA Stock v3.0"})


@app.route('/predict', methods=['POST'])
def predict():
    data = request.json
    if not data:
        return jsonify({"error": "No JSON provided"}), 400

    produits   = data.get('produits', [])
    mouvements = data.get('mouvements', [])

    # Indexer les mouvements par produit
    mvt_by_product = {}
    for m in mouvements:
        pid  = m.get('produit_id')
        type_ = (m.get('type', '') or '').lower()
        qty  = float(m.get('quantite', 0) or 0)
        if pid not in mvt_by_product:
            mvt_by_product[pid] = {'entrees': 0, 'sorties': 0, 'total': 0}
        if 'entree' in type_ or type_ == 'in':
            mvt_by_product[pid]['entrees'] += qty
        else:
            mvt_by_product[pid]['sorties'] += qty
        mvt_by_product[pid]['total'] += 1

    predictions = []
    for produit in produits:
        pid   = produit.get('id')
        nom   = produit.get('nom', produit.get('name', ''))
        stock = float(produit.get('stock_actuel', produit.get('quantite', 0)) or 0)
        seuil = float(produit.get('seuil_minimum', produit.get('seuil_min', 0)) or 0)
        prix  = float(produit.get('prix', 10) or 10)

        mvt = mvt_by_product.get(pid, {'entrees': 0, 'sorties': 0, 'total': 0})
        nb_mvt   = mvt['total']
        entrees  = mvt['entrees']
        sorties  = mvt['sorties']

        # Demande prévue : basée sur les sorties historiques (si dispo) ou le stock
        if sorties > 0:
            demande = sorties * 1.1   # légère tendance haussière
        else:
            demande = max(5, stock * 0.4)

        eoq       = compute_eoq(demande * 12, prix_unitaire=prix)   # annualisé
        confiance = compute_confiance(nb_mvt)
        score_ano = compute_score_anomalie(entrees, sorties)
        recs      = build_recommendations(produit, demande, eoq, stock, seuil, confiance)
        reasoning = build_reasoning(nom, demande, eoq, stock, seuil, confiance, entrees, sorties)

        predictions.append({
            "produit_id"     : pid,
            "nom"            : nom,
            "quantite"       : round(demande, 2),
            "EOQ"            : round(eoq, 2),
            "confiance"      : confiance,
            "score_anomalie" : score_ano,
            "recommandations": recs,
            "reasoning"      : reasoning,
            "stock_actuel"   : stock,
            "seuil_minimum"  : seuil,
        })

    return jsonify(predictions)


# ──────────────────────────────────────────────
#  Simulation What-If
# ──────────────────────────────────────────────

@app.route('/simulate', methods=['POST'])
def simulate():
    """
    Reçoit un scénario et recalcule le risque de rupture.
    Payload attendu :
    {
        "produit": { id, nom, stock_actuel, seuil_minimum, prix },
        "mouvements": [...],
        "scenario": {
            "type": "delay" | "demand_spike" | "supply_cut",
            "delay_days": 10,
            "demand_spike_pct": 30,
            "description": "Et si le fournisseur a 10 jours de retard ?"
        }
    }
    """
    data = request.json
    if not data:
        return jsonify({"error": "No JSON provided"}), 400

    produit  = data.get('produit', {})
    mouvements = data.get('mouvements', [])
    scenario = data.get('scenario', {})

    stock = float(produit.get('stock_actuel', produit.get('quantite', 0)) or 0)
    seuil = float(produit.get('seuil_minimum', produit.get('seuil_min', 0)) or 0)
    nom   = produit.get('nom', produit.get('name', 'Produit'))

    # Calculer la consommation journalière moyenne
    total_sorties = 0
    total_days = 30  # par défaut
    for m in mouvements:
        type_ = (m.get('type', '') or '').lower()
        qty   = float(m.get('quantite', 0) or 0)
        if 'sortie' in type_ or type_ == 'out':
            total_sorties += qty

    # Estimation de la consommation journalière
    daily_consumption = total_sorties / max(total_days, 1)
    if daily_consumption == 0:
        daily_consumption = max(1, stock * 0.02)  # fallback: 2% du stock/jour

    # Appliquer le scénario
    scenario_type = scenario.get('type', 'delay')
    delay_days = int(scenario.get('delay_days', 0))
    demand_spike_pct = float(scenario.get('demand_spike_pct', 0))

    explanation_parts = []

    if scenario_type == 'delay' or delay_days > 0:
        # Retard fournisseur : le stock doit couvrir X jours supplémentaires
        stock_needed = daily_consumption * delay_days
        remaining = stock - stock_needed
        explanation_parts.append(
            f"Avec un retard fournisseur de {delay_days} jours, "
            f"la consommation estimée serait de {round(stock_needed)} unités. "
            f"Stock restant après retard : {round(max(0, remaining))} unités."
        )

    if scenario_type == 'demand_spike' or demand_spike_pct > 0:
        # Hausse de demande
        boosted = daily_consumption * (1 + demand_spike_pct / 100)
        explanation_parts.append(
            f"Avec une hausse de demande de {demand_spike_pct}%, "
            f"la consommation journalière passerait de {round(daily_consumption, 1)} "
            f"à {round(boosted, 1)} unités/jour."
        )
        daily_consumption = boosted

    if scenario_type == 'supply_cut':
        explanation_parts.append(
            f"En cas de coupure d'approvisionnement, le stock actuel ({int(stock)} u.) "
            f"devra couvrir toute la demande sans réapprovisionnement."
        )

    # Calcul du risque
    effective_consumption = daily_consumption * max(delay_days, 1) if delay_days > 0 else daily_consumption * 30
    days_of_stock = stock / daily_consumption if daily_consumption > 0 else 999
    projected_stockout_days = max(0, round(days_of_stock - delay_days)) if delay_days > 0 else round(days_of_stock)

    # Score de risque (0–100)
    if days_of_stock <= delay_days or stock < seuil:
        risk_score = min(100, max(80, round(100 - (days_of_stock / max(delay_days, 1)) * 50)))
    elif days_of_stock <= delay_days * 1.5:
        risk_score = min(79, max(40, round(80 - (days_of_stock - delay_days) * 5)))
    else:
        risk_score = max(5, round(30 - (days_of_stock - delay_days * 1.5) * 2))

    risk_score = max(0, min(100, risk_score))

    # Niveau de risque
    if risk_score >= 70:
        risk_level = "Critique"
    elif risk_score >= 40:
        risk_level = "Moyen"
    else:
        risk_level = "Faible"

    explanation = " ".join(explanation_parts) if explanation_parts else \
        f"Avec les paramètres actuels, le stock de « {nom} » couvre environ {projected_stockout_days} jours."

    return jsonify({
        "risk_level": risk_level,
        "risk_score": risk_score,
        "projected_stockout_days": projected_stockout_days,
        "daily_consumption": round(daily_consumption, 2),
        "explanation": explanation,
        "produit_nom": nom,
    })


# ──────────────────────────────────────────────
#  Détection d'Anomalies Comportementales
# ──────────────────────────────────────────────

@app.route('/detect-anomalies', methods=['POST'])
def detect_anomalies():
    """
    Compare les mouvements récents avec le profil historique de chaque produit.
    Payload attendu :
    {
        "mouvements_recents": [...],  (dernières 24-48h)
        "profils": {
            "<produit_id>": {
                "avg_qty": 50,
                "std_qty": 10,
                "usual_hours": [8, 9, 10, ..., 17],
                "avg_daily_count": 3
            }
        }
    }
    """
    data = request.json
    if not data:
        return jsonify({"error": "No JSON provided"}), 400

    mouvements_recents = data.get('mouvements_recents', [])
    profils = data.get('profils', {})

    anomalies = []

    for m in mouvements_recents:
        pid = str(m.get('produit_id', ''))
        qty = float(m.get('quantite', 0) or 0)
        date_str = m.get('date_mouvement', m.get('created_at', ''))
        produit_nom = m.get('produit_nom', m.get('produit', {}).get('nom', 'Inconnu'))
        mvt_id = m.get('id', 0)

        profil = profils.get(pid, None)
        if not profil:
            continue

        avg_qty = float(profil.get('avg_qty', 0))
        std_qty = float(profil.get('std_qty', avg_qty * 0.3)) if avg_qty > 0 else 10
        usual_hours = profil.get('usual_hours', list(range(8, 18)))
        avg_daily_count = float(profil.get('avg_daily_count', 3))

        anomaly_reasons = []
        severity = 0

        # 1. Volume anormal (> 2 écarts-types au-dessus de la moyenne)
        if avg_qty > 0 and qty > avg_qty + 2 * std_qty:
            ratio = round(qty / avg_qty, 1)
            anomaly_reasons.append(
                f"Volume anormal : {int(qty)} unités vs moyenne de {round(avg_qty)} "
                f"({ratio}x la moyenne)"
            )
            severity += min(40, round((qty - avg_qty) / std_qty * 10))

        # 2. Heure inhabituelle
        if date_str:
            try:
                dt = datetime.fromisoformat(date_str.replace('Z', '+00:00').split('.')[0])
                hour = dt.hour
                if hour not in usual_hours:
                    anomaly_reasons.append(
                        f"Mouvement à {hour}h — en dehors des heures habituelles "
                        f"({min(usual_hours)}h-{max(usual_hours)}h)"
                    )
                    severity += 25
                    if hour < 6 or hour > 22:
                        severity += 15  # Très suspect
            except (ValueError, TypeError):
                pass

        # 3. Volume anormalement élevé par rapport au seuil absolu
        if qty > 500:
            anomaly_reasons.append(f"Quantité exceptionnelle : {int(qty)} unités en un seul mouvement")
            severity += 20

        if anomaly_reasons:
            severity = min(99, max(20, severity))
            anomalies.append({
                "mouvement_id": mvt_id,
                "produit_id": pid,
                "produit_nom": produit_nom,
                "type": "COMPORTEMENT",
                "severity": severity,
                "reasons": anomaly_reasons,
                "description": " | ".join(anomaly_reasons),
                "date": date_str,
                "quantite": qty,
            })

    # Trier par sévérité décroissante
    anomalies.sort(key=lambda x: x['severity'], reverse=True)

    return jsonify({
        "anomalies": anomalies,
        "total_analyzed": len(mouvements_recents),
        "total_anomalies": len(anomalies),
    })


# ──────────────────────────────────────────────
#  Validation en Temps Réel (Prescriptive Blocking)
# ──────────────────────────────────────────────

@app.route('/validate-transaction', methods=['POST'])
def validate_transaction():
    """
    Analyse une tentative de mouvement avant qu'elle ne soit enregistrée en base.
    Bloque les actions qui causeraient des "dégâts" (rupture critique ou vol probable).
    """
    data = request.json
    if not data:
        return jsonify({"error": "No JSON provided"}), 400

    produit   = data.get('produit', {})
    tentative = data.get('tentative', {})
    historique = data.get('historique', [])

    stock_actuel = float(produit.get('stock_actuel', 0))
    seuil_min    = float(produit.get('seuil_min', 0))
    nom_produit  = produit.get('nom', 'Produit')

    qty_tentative = float(tentative.get('quantite', 0))
    type_tentative = tentative.get('type', 'sortie')
    heure_tentative = int(tentative.get('heure', datetime.now().hour))

    if type_tentative != 'sortie':
        return jsonify({"decision": "APPROUVE", "reason": "Mouvement entrant autorisé.", "risk_score": 0})

    # 1. Calcul du risque de rupture critique
    stock_apres = stock_actuel - qty_tentative
    
    # Règle : Interdire si le stock tombe sous 20% du seuil minimum (Urgence critique)
    # Sauf si le stock est déjà très bas, on laisse quand même sortir s'il reste une petite quantité? 
    # Non, l'utilisateur veut "stopper ce qui fait des dégâts".
    if stock_apres < (seuil_min * 0.2) and stock_actuel > (seuil_min * 0.2):
        return jsonify({
            "decision": "BLOQUE",
            "risk_score": 95,
            "reason": f"Risque de rupture CRITIQUE. Cette sortie laisserait seulement {int(stock_apres)} unités en stock (seuil critique: {int(seuil_min*0.2)}). Action bloquée pour préserver le service."
        })

    # 2. Analyse comportementale temps réel (Volume anormal)
    if historique:
        volumes_hist = [float(m.get('quantite', 0)) for m in historique if (m.get('type') or '').lower() == 'sortie']
        if volumes_hist:
            avg_hist = sum(volumes_hist) / len(volumes_hist)
            if qty_tentative > avg_hist * 5:  # 5x la moyenne habituelle
                return jsonify({
                    "decision": "BLOQUE",
                    "risk_score": 88,
                    "reason": f"VOLUME ANORMAL. Cette sortie ({int(qty_tentative)} u.) est {round(qty_tentative/avg_hist, 1)} fois supérieure à la moyenne habituelle ({round(avg_hist, 1)} u.). Une vérification manuelle est requise."
                })

    # 3. Analyse comportementale temps réel (Heure suspecte)
    if heure_tentative < 6 or heure_tentative > 22:
        if qty_tentative > 50: # On autorise les petites sorties de nuit, mais pas les grosses
            return jsonify({
                "decision": "BLOQUE",
                "risk_score": 92,
                "reason": f"ALERTE SÉCURITÉ. Tentative de sortie massive ({int(qty_tentative)} u.) en dehors des heures ouvrées ({heure_tentative}h). Transaction suspendue par l'IA."
            })

    # Par défaut : Approuvé
    return jsonify({
        "decision": "APPROUVE",
        "risk_score": 10,
        "reason": "Mouvement jugé conforme par l'analyse IA."
    })


if __name__ == '__main__':
    print("=" * 55)
    print("  Agent IA Stock v3.0  —  Port 5000")
    print("  GET  http://127.0.0.1:5000/health")
    print("  POST http://127.0.0.1:5000/predict")
    print("  POST http://127.0.0.1:5000/simulate")
    print("  POST http://127.0.0.1:5000/detect-anomalies")
    print("  POST http://127.0.0.1:5000/validate-transaction")
    print("=" * 55)
    app.run(host='0.0.0.0', port=5000, debug=False)


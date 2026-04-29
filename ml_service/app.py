from __future__ import annotations

from pathlib import Path
from datetime import datetime

import joblib
import pandas as pd
from flask import Flask, jsonify, request
from flask_cors import CORS


BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "fuel_model.pkl"

app = Flask(__name__)
CORS(app)


def load_bundle() -> dict:
    if not MODEL_PATH.exists():
        from train_model import train

        train()
    return joblib.load(MODEL_PATH)


MODEL_BUNDLE = load_bundle()


def _clean_transactions(transactions: list[dict]) -> pd.DataFrame:
    frame = pd.DataFrame(transactions)
    if frame.empty:
        return pd.DataFrame(
            columns=[
                "date",
                "amount",
                "quantity_liters",
                "price_per_liter",
                "station_name",
                "vehicle_id",
            ]
        )

    for column in ["amount", "quantity_liters", "price_per_liter"]:
        frame[column] = pd.to_numeric(frame.get(column, 0), errors="coerce").fillna(0)

    frame["date"] = pd.to_datetime(frame.get("date"), errors="coerce", utc=True)
    frame = frame.dropna(subset=["date"]).copy()
    frame["station_name"] = frame.get("station_name", "").fillna("").astype(str)
    frame["vehicle_id"] = frame.get("vehicle_id", "").fillna("").astype(str)
    frame["month"] = frame["date"].dt.tz_convert(None).dt.to_period("M").astype(str)
    return frame.sort_values("date")


def _month_summary(frame: pd.DataFrame) -> list[dict]:
    if frame.empty:
        return []

    grouped = (
        frame.groupby("month")
        .agg(
            total_liters=("quantity_liters", "sum"),
            total_cost=("amount", "sum"),
            transaction_count=("quantity_liters", "count"),
            avg_price=("price_per_liter", "mean"),
        )
        .reset_index()
        .sort_values("month")
    )

    return [
        {
            "month": row["month"],
            "total_liters": round(float(row["total_liters"]), 2),
            "total_cost": round(float(row["total_cost"]), 2),
            "transaction_count": int(row["transaction_count"]),
            "avg_price": round(float(row["avg_price"]), 3),
        }
        for _, row in grouped.iterrows()
    ]


def _build_features(frame: pd.DataFrame, vehicles: list[dict]) -> dict:
    recent_cutoff = pd.Timestamp.now(tz="UTC") - pd.Timedelta(days=30)
    recent = frame[frame["date"] >= recent_cutoff] if not frame.empty else frame
    if recent.empty:
        recent = frame.tail(10)

    avg_consumption_values = [
        float(vehicle.get("average_consumption", 0))
        for vehicle in vehicles
        if str(vehicle.get("average_consumption", "")).strip() not in ["", "0", "0.0"]
    ]
    avg_consumption = sum(avg_consumption_values) / len(avg_consumption_values) if avg_consumption_values else 7.0
    avg_price = float(recent["price_per_liter"].replace(0, pd.NA).dropna().mean()) if not recent.empty else 2.55
    total_recent_liters = float(recent["quantity_liters"].sum()) if not recent.empty else 0.0
    estimated_distance = total_recent_liters / avg_consumption * 100 if avg_consumption > 0 else 0.0
    trips_per_week = len(recent) / 4.345 if not recent.empty else 0.0
    daily_liters = recent.set_index("date")["quantity_liters"].resample("D").sum() if not recent.empty else pd.Series(dtype=float)
    traffic_index = min(1.0, float(daily_liters.std() / daily_liters.mean())) if len(daily_liters) > 1 and daily_liters.mean() > 0 else 0.35

    return {
        "distance_km": round(estimated_distance, 2),
        "fuel_price_tnd": round(avg_price if pd.notna(avg_price) else 2.55, 3),
        "vehicle_age_years": 5.0,
        "avg_consumption_l_100km": round(avg_consumption, 2),
        "trips_per_week": round(trips_per_week, 2),
        "traffic_index": round(traffic_index, 2),
    }


def _predict_from_features(features: dict) -> dict:
    model_features = MODEL_BUNDLE["features"]
    frame = pd.DataFrame([{feature: float(features[feature]) for feature in model_features}], columns=model_features)
    prediction = max(0.0, float(MODEL_BUNDLE["model"].predict(frame)[0]))
    return {
        "predicted_monthly_liters": round(prediction, 2),
        "estimated_monthly_cost_tnd": round(prediction * float(features["fuel_price_tnd"]), 2),
        "model_metrics": MODEL_BUNDLE.get("metrics", {}),
        "input": features,
    }


def _detect_anomalies(frame: pd.DataFrame) -> list[dict]:
    if frame.empty or len(frame) < 3:
        return []

    mean = frame["quantity_liters"].mean()
    std = frame["quantity_liters"].std()
    if std == 0 or pd.isna(std):
        high_volume = frame[frame["quantity_liters"] > mean * 1.35].tail(3)
    else:
        high_volume = frame[frame["quantity_liters"] > mean + (1.5 * std)].tail(3)

    expensive_mean = frame["price_per_liter"].replace(0, pd.NA).dropna().mean()
    expensive = (
        frame[frame["price_per_liter"] > expensive_mean * 1.08].tail(3)
        if pd.notna(expensive_mean)
        else pd.DataFrame()
    )

    anomalies = pd.concat([high_volume, expensive]).drop_duplicates(subset=["id", "date"]).tail(5)
    return [
        {
            "date": row["date"].strftime("%Y-%m-%d"),
            "quantity_liters": round(float(row["quantity_liters"]), 2),
            "station_name": row["station_name"],
            "message": "Transaction inhabituelle par rapport a votre historique.",
        }
        for _, row in anomalies.iterrows()
    ]


def _recommendations(frame: pd.DataFrame, monthly: list[dict], prediction: dict, anomalies: list[dict]) -> list[str]:
    if frame.empty:
        return ["Ajoutez des transactions pour recevoir des recommandations personnalisees."]

    recommendations = []
    if len(monthly) >= 2:
        current = monthly[-1]["total_liters"]
        previous = monthly[-2]["total_liters"]
        if previous > 0 and current > previous * 1.15:
            recommendations.append("Votre consommation augmente ce mois-ci. Verifiez les trajets repetitifs et la pression des pneus.")
        elif previous > 0 and current < previous * 0.9:
            recommendations.append("Bonne tendance: votre consommation est inferieure au mois precedent.")

    avg_price = frame["price_per_liter"].replace(0, pd.NA).dropna().mean()
    if pd.notna(avg_price):
        expensive = frame[frame["price_per_liter"] > avg_price * 1.05]
        if not expensive.empty:
            recommendations.append("Certaines transactions ont un prix/litre eleve. Comparez les stations avant le plein.")
        else:
            recommendations.append(f"Votre prix moyen est d'environ {avg_price:.3f} TND/L. Gardez ce repere pour comparer vos prochains pleins.")

    if anomalies:
        recommendations.append("Une ou plusieurs transactions semblent inhabituelles. Consultez les details pour confirmer.")

    recent_liters = float(frame.tail(30)["quantity_liters"].sum())
    if recent_liters > 0 and prediction["predicted_monthly_liters"] > recent_liters * 1.1:
        recommendations.append("Une hausse est possible prochainement. Planifiez vos recharges avant les longs trajets.")

    if len(monthly) < 2:
        recommendations.append("Ajoutez des transactions sur plusieurs mois pour obtenir une comparaison mensuelle plus precise.")

    total_liters = float(frame["quantity_liters"].sum())
    total_cost = float(frame["amount"].sum())
    if total_liters > 0 and total_cost > 0:
        recommendations.append(f"Vous avez consomme {total_liters:.1f} L pour environ {total_cost:.1f} TND dans l'historique analyse.")

    return recommendations[:4] or ["Votre profil de consommation est stable pour le moment."]


def _trend_text(monthly: list[dict]) -> dict:
    if len(monthly) < 2:
        return {
            "text": "Pas encore assez de transactions pour comparer les tendances.",
            "variation": "0%",
            "period": "this month",
        }

    current = monthly[-1]["total_liters"]
    previous = monthly[-2]["total_liters"]
    variation = ((current - previous) / previous * 100) if previous > 0 else 0
    sign = "+" if variation >= 0 else ""
    direction = "augmente" if variation >= 0 else "diminue"

    return {
        "text": f"Votre consommation {direction} de {abs(variation):.1f}% par rapport au mois precedent.",
        "variation": f"{sign}{variation:.1f}%",
        "period": "this month",
    }


@app.get("/health")
def health():
    return jsonify(
        {
            "ok": True,
            "model": MODEL_PATH.name,
            "features": MODEL_BUNDLE["features"],
            "metrics": MODEL_BUNDLE.get("metrics", {}),
        }
    )


@app.post("/predict")
def predict():
    payload = request.get_json(silent=True) or {}
    features = MODEL_BUNDLE["features"]

    missing = [feature for feature in features if feature not in payload]
    if missing:
        return jsonify({"error": "Missing required fields", "missing": missing}), 422

    try:
        values = {feature: float(payload[feature]) for feature in features}
    except (TypeError, ValueError):
        return jsonify({"error": "All feature values must be numeric"}), 422

    frame = pd.DataFrame([values], columns=features)
    prediction = float(MODEL_BUNDLE["model"].predict(frame)[0])
    prediction = max(0.0, prediction)

    fuel_price = values["fuel_price_tnd"]
    estimated_cost = prediction * fuel_price

    return jsonify(
        {
            "predicted_monthly_liters": round(prediction, 2),
            "estimated_monthly_cost_tnd": round(estimated_cost, 2),
            "model_metrics": MODEL_BUNDLE.get("metrics", {}),
            "input": values,
        }
    )


@app.post("/insights")
def insights():
    payload = request.get_json(silent=True) or {}
    transactions = payload.get("transactions", [])
    vehicles = payload.get("vehicles", [])

    if not isinstance(transactions, list):
        return jsonify({"error": "transactions must be a list"}), 422
    if not isinstance(vehicles, list):
        vehicles = []

    frame = _clean_transactions(transactions)
    monthly = _month_summary(frame)
    features = _build_features(frame, vehicles)
    prediction = _predict_from_features(features)
    anomalies = _detect_anomalies(frame)
    trend = _trend_text(monthly)
    recommendations = _recommendations(frame, monthly, prediction, anomalies)

    return jsonify(
        {
            "generated_at": datetime.utcnow().isoformat() + "Z",
            "transaction_count": int(len(frame)),
            "prediction": prediction,
            "monthly_comparison": monthly[-6:],
            "anomalies": anomalies,
            "recommendations": recommendations,
            "dashboard_insight": trend,
        }
    )


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5001, debug=False, use_reloader=False)

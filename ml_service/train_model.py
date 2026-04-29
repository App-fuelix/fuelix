from __future__ import annotations

from pathlib import Path

import joblib
import pandas as pd
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_absolute_error, r2_score
from sklearn.model_selection import train_test_split


BASE_DIR = Path(__file__).resolve().parent
DATA_PATH = BASE_DIR / "data" / "fuel_transactions_sample.csv"
MODEL_PATH = BASE_DIR / "fuel_model.pkl"

FEATURES = [
    "distance_km",
    "fuel_price_tnd",
    "vehicle_age_years",
    "avg_consumption_l_100km",
    "trips_per_week",
    "traffic_index",
]
TARGET = "monthly_fuel_liters"


def create_sample_dataset() -> None:
    """Create a small fallback dataset when the CSV is missing."""
    DATA_PATH.parent.mkdir(parents=True, exist_ok=True)
    rows = [
        [420, 2.52, 2, 6.2, 8, 0.35, 31.6],
        [680, 2.52, 5, 7.4, 12, 0.45, 59.4],
        [310, 2.48, 1, 5.8, 6, 0.25, 22.4],
        [890, 2.55, 8, 8.9, 15, 0.62, 97.5],
        [540, 2.51, 4, 6.9, 10, 0.40, 44.8],
        [760, 2.58, 6, 7.8, 13, 0.52, 72.1],
        [260, 2.49, 3, 6.1, 5, 0.28, 19.7],
        [1020, 2.60, 10, 9.4, 18, 0.70, 120.6],
    ]
    pd.DataFrame(rows, columns=[*FEATURES, TARGET]).to_csv(DATA_PATH, index=False)


def train() -> dict[str, float]:
    if not DATA_PATH.exists():
        create_sample_dataset()

    dataset = pd.read_csv(DATA_PATH)
    missing = set([*FEATURES, TARGET]) - set(dataset.columns)
    if missing:
        raise ValueError(f"Dataset is missing required columns: {sorted(missing)}")

    x = dataset[FEATURES]
    y = dataset[TARGET]

    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=0.25,
        random_state=42,
    )

    model = LinearRegression()
    model.fit(x_train, y_train)

    predictions = model.predict(x_test)
    metrics = {
        "mae": round(float(mean_absolute_error(y_test, predictions)), 3),
        "r2": round(float(r2_score(y_test, predictions)), 3),
    }

    joblib.dump(
        {
            "model": model,
            "features": FEATURES,
            "target": TARGET,
            "metrics": metrics,
        },
        MODEL_PATH,
    )

    return metrics


if __name__ == "__main__":
    result = train()
    print(f"Saved model to {MODEL_PATH}")
    print(f"Metrics: MAE={result['mae']}, R2={result['r2']}")

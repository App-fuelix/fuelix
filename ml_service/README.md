# FueliX ML Service

Small Flask service that trains a scikit-learn `LinearRegression` model for fuel consumption prediction.

## Setup

```powershell
cd ml_service
python -m pip install -r requirements.txt
python train_model.py
python app.py
```

The API runs on `http://127.0.0.1:5001`.

## Endpoints

- `GET /health`
- `POST /predict`
- `POST /insights`

Example request:

```json
{
  "distance_km": 620,
  "fuel_price_tnd": 2.54,
  "vehicle_age_years": 5,
  "avg_consumption_l_100km": 7.4,
  "trips_per_week": 12,
  "traffic_index": 0.48
}
```

`/insights` is the endpoint used by Laravel. Laravel sends the authenticated
user's Firestore transactions and vehicles, then the service cleans the data
with pandas, predicts future consumption, detects anomalies, compares months,
and returns recommendations for the Flutter AI Insights screen.

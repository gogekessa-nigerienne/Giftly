
import pandas as pd
import joblib

from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import LabelEncoder

# Load training data
data = pd.read_csv("training_data.csv")

# Features used by the model
features = [
    "relationship",
    "occasion",
    "interest",
    "budget"
]

X = data[features].copy()
y = data["category"].copy()

# Convert text into numbers
encoders = {}

for column in features:
    encoder = LabelEncoder()
    X[column] = encoder.fit_transform(X[column])
    encoders[column] = encoder

# Train Random Forest model
model = RandomForestClassifier(
    n_estimators=100,
    random_state=42
)

model.fit(X, y)

# Save model and encoders
joblib.dump(
    {
        "model": model,
        "encoders": encoders,
        "features": features
    },
    "gift_model.pkl"
)

print("Giftly recommendation model trained successfully!")

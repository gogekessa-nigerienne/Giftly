
from pathlib import Path

import pandas as pd

BASE_DIR = Path(__file__).resolve().parent
PRODUCTS_FILE = BASE_DIR / "products.csv"

products = pd.read_csv(PRODUCTS_FILE)


def clean(value):
    if pd.isna(value):
        return ""
    return str(value).strip().lower()


def get_budget_category(budget):
    if budget <= 100:
        return "50-100"
    elif budget <= 250:
        return "100-250"
    return "250-500"


# Load the trained model if available.
saved_data = None
MODEL_FILE = BASE_DIR / "gift_model.pkl"

if MODEL_FILE.exists():
    try:
        import joblib
        saved_data = joblib.load(MODEL_FILE)
    except Exception as error:
        print("Saved model unavailable:", error)


def predict_category(relationship, occasion, interest, budget):
    if saved_data is None:
        return None

    try:
        model = saved_data["model"]
        encoders = saved_data["encoders"]
        features = saved_data["features"]

        answers = {
            "relationship": relationship,
            "occasion": occasion,
            "interest": interest,
            "budget": get_budget_category(budget)
        }

        preferences = pd.DataFrame([answers])

        for column in features:
            encoder = encoders[column]
            value = str(preferences.loc[0, column])

            if value not in encoder.classes_:
                return None

            preferences[column] = encoder.transform([value])

        return str(model.predict(preferences)[0])

    except Exception as error:
        print("Model prediction skipped:", error)
        return None


def recommend_gifts(
    relationship,
    occasion,
    interest,
    budget,
    recipient_age="",
    personality="",
    style="",
    gift_type="",
    extra_notes=""
):
    try:
        budget = float(budget)
    except (TypeError, ValueError):
        raise ValueError("Please enter a valid budget.")

    if budget <= 0:
        raise ValueError("Budget must be greater than zero.")

    if products.empty:
        return products.copy()

    results = products.copy()

    required_columns = [
        "product_id", "name", "category", "price",
        "interests", "occasions"
    ]

    missing = [column for column in required_columns
               if column not in results.columns]

    if missing:
        raise ValueError(
            "Missing catalogue columns: " + ", ".join(missing)
        )

    results["price"] = pd.to_numeric(
        results["price"], errors="coerce"
    )

    results = results.dropna(subset=["price"])
    results = results[results["price"] <= budget].copy()

    if results.empty:
        return results

    predicted_category = predict_category(
        relationship, occasion, interest, budget
    )

    interest = clean(interest)
    occasion = clean(occasion)

    results["match_score"] = 0
    results["match_reason"] = ""

    for index, product in results.iterrows():
        score = 0
        reasons = []

        product_interests = clean(product["interests"])
        product_occasions = clean(product["occasions"])
        product_category = clean(product["category"])

        # Interest match
        if interest and interest in product_interests:
            score += 5
            reasons.append("Matches their interest")

        # Occasion match
        if occasion and occasion in product_occasions:
            score += 4
            reasons.append("Suitable for the occasion")

        # Optional model category match
        if predicted_category:
            if product_category == clean(predicted_category):
                score += 2
                reasons.append("Matches the suggested category")

        results.at[index, "match_score"] = score
        results.at[index, "match_reason"] = (
            " • ".join(reasons)
            if reasons else "Within your budget"
        )

    # Prefer stronger preference matches, then better budget fit.
    results["budget_fit"] = results["price"] / budget

    results = results.sort_values(
        by=["match_score", "budget_fit"],
        ascending=[False, False]
    ).reset_index(drop=True)

    return results


def get_other_products(recommended_ids):
    recommended_ids = [str(value) for value in recommended_ids]

    remaining = products.copy()
    remaining["product_id"] = remaining["product_id"].astype(str)

    remaining = remaining[
        ~remaining["product_id"].isin(recommended_ids)
    ].copy()

    return remaining.reset_index(drop=True)

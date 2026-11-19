
from flask import Flask, request, jsonify

from recommend import recommend_gifts, get_other_products

app = Flask(__name__)


@app.route("/recommend", methods=["POST"])
def get_recommendations():

    data = request.get_json(silent=True) or {}

    required = ["relationship", "occasion", "interest", "budget"]

    for field in required:
        if data.get(field) in (None, ""):
            return jsonify({
                "success": False,
                "message": f"Please provide {field}."
            }), 400

    try:
        budget = float(data["budget"])

        if budget <= 0:
            raise ValueError

    except (TypeError, ValueError):
        return jsonify({
            "success": False,
            "message": "Please enter a valid budget amount."
        }), 400

    try:
        results = recommend_gifts(
            relationship=data["relationship"],
            occasion=data["occasion"],
            interest=data["interest"],
            budget=budget,
            recipient_age=data.get("recipient_age", ""),
            personality=data.get("personality", ""),
            style=data.get("style", ""),
            gift_type=data.get("gift_type", ""),
            extra_notes=data.get("extra_notes", "")
        )

        # Show only the four strongest recommendations.
        top_results = results.head(4)

        recommendations = []

        for _, product in top_results.iterrows():
            recommendations.append({
                "product_id": int(product["product_id"]),
                "name": str(product["name"]),
                "category": str(product["category"]),
                "price": float(product["price"]),
                "match_score": int(product["match_score"]),
                "match_reason": str(product["match_reason"])
            })

        recommended_ids = [
            product["product_id"] for product in recommendations
        ]

        # All remaining catalogue products appear below.
        other_results = get_other_products(recommended_ids)

        other_products = []

        for _, product in other_results.iterrows():
            other_products.append({
                "product_id": int(product["product_id"]),
                "name": str(product["name"]),
                "category": str(product["category"]),
                "price": float(product["price"])
            })

        return jsonify({
            "success": True,
            "budget": budget,
            "recommendations": recommendations,
            "other_products": other_products
        })

    except Exception as error:
        return jsonify({
            "success": False,
            "message": str(error)
        }), 500


if __name__ == "__main__":
    print("Giftly Recommendation API is running...")
    app.run(host="127.0.0.1", port=5000, debug=True)

<?php

require_once __DIR__ . '/../includes/session.php';



$error = "";

if (empty($_SESSION["gift_finder_csrf"])) {
    $_SESSION["gift_finder_csrf"] = bin2hex(random_bytes(32));
}



if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $submittedToken = $_POST["csrf_token"] ?? "";
    if (!is_string($submittedToken) || !hash_equals($_SESSION["gift_finder_csrf"], $submittedToken)) {
        $error = "Your session has expired. Please refresh the page and try again.";
    } else {



        $preferences = [

            "relationship" => trim($_POST["relationship"] ?? ""),

            "occasion" => trim($_POST["occasion"] ?? ""),

            "interest" => trim($_POST["interest"] ?? ""),

            "budget" => trim($_POST["budget"] ?? ""),

            "recipient_age" => trim($_POST["recipient_age"] ?? ""),

            "personality" => trim($_POST["personality"] ?? ""),

            "style" => trim($_POST["style"] ?? ""),

            "gift_type" => trim($_POST["gift_type"] ?? ""),

            "extra_notes" => trim($_POST["extra_notes"] ?? "")

        ];



        if (

            $preferences["relationship"] === "" ||

            $preferences["occasion"] === "" ||

            $preferences["interest"] === "" ||

            !is_numeric($preferences["budget"]) ||

            (float) $preferences["budget"] <= 0

        ) {

            $error = "Please complete the required fields and enter a valid budget.";

        } else {



            $jsonData = json_encode($preferences);



            $options = [

                "http" => [

                    "header" => "Content-Type: application/json\r\n",

                    "method" => "POST",

                    "content" => $jsonData,

                    "timeout" => 20,

                    "ignore_errors" => true

                ]

            ];



            $context = stream_context_create($options);



            $response = @file_get_contents(

                "http://127.0.0.1:5000/recommend",

                false,

                $context

            );



            $httpCode = 0;



            if (isset($http_response_header[0])) {

                preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches);

                $httpCode = (int) ($matches[1] ?? 0);

            }



            if ($response === false) {

                $error = "We could not connect to the Giftly recommendation system. Please try again.";

            } else {



                $result = json_decode($response, true);



                if (

                    $httpCode === 200 &&

                    is_array($result) &&

                    !empty($result["success"])

                ) {

                    $_SESSION["giftly_preferences"] = $preferences;

                    $_SESSION["giftly_results"] = $result;



                    header("Location: gift_results.php");

                    exit;

                }



                $error = $result["message"] ?? "Something went wrong. Please try again.";

            }

        }

    }
}

?>

<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gift Finder | Giftly</title>



    <link rel="stylesheet" href="../assets/css/gift-finder.css">

</head>



<body>



    <header class="gf-header">

        <a href="../pages/home/Home.php" class="gf-logo">

            <img src="../assets/Logo.png" alt="Giftly">

            <span>Giftly</span>

        </a>



        <nav>

            <a href="../pages/home/Home.php">Home</a>

            <a href="gift_finder.php" class="active">Gift Finder</a>

        </nav>

    </header>



    <main class="gf-page">



        <section class="gf-hero">

            <div class="hero-text">

                <span class="eyebrow">YOUR PERSONAL GIFT ASSISTANT</span>



                <h1>Let's find a gift<br>that feels <span>just right.</span></h1>





            </div>



            <div class="robot-scene">

                <div class="robot-glow"></div>



                <div class="robot">

                    <div class="robot-antenna">

                        <span></span>

                    </div>



                    <div class="robot-head">

                        <div class="robot-ear left"></div>

                        <div class="robot-ear right"></div>



                        <div class="robot-face">

                            <div class="robot-eyes">

                                <span></span>

                                <span></span>

                            </div>

                            <div class="robot-smile"></div>

                        </div>



                        <div class="robot-cheek left"></div>

                        <div class="robot-cheek right"></div>

                    </div>



                    <div class="robot-neck"></div>



                    <div class="robot-body">

                        <div class="robot-heart">♥</div>

                        <div class="robot-body-line"></div>

                    </div>



                    <div class="robot-arm left"></div>

                    <div class="robot-arm right"></div>

                </div>



                <div class="robot-message">

                    <span>Hi there!</span>

                    I'm Giftly Bot. Let's find something special!

                </div>



                <span class="floating-star star-one">✦</span>

                <span class="floating-star star-two">✧</span>

                <span class="floating-star star-three">♥</span>

            </div>

        </section>



        <section class="gf-form-section">



            <div class="form-heading">

                <span class="eyebrow">LET'S GET TO KNOW THEM</span>

                <h2>Who are we shopping for?</h2>

                <p>A few details will help us find a gift they may love.</p>

            </div>



            <?php if ($error !== ""): ?>

                <div class="gf-error">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php endif; ?>



            <form method="POST" action="gift_finder.php" id="giftFinderForm">
                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION['gift_finder_csrf'], ENT_QUOTES, 'UTF-8'); ?>">



                <div class="form-grid">



                    <div class="form-field">

                        <label for="relationship">Who is this gift for? <span>\*</span></label>

                        <select name="relationship" id="relationship" required>

                            <option value="">Choose a relationship</option>

                            <option>Friend</option>

                            <option>Mother</option>

                            <option>Father</option>

                            <option>Partner</option>

                            <option>Sibling</option>

                            <option>Colleague</option>

                            <option>Other</option>

                        </select>

                    </div>



                    <div class="form-field">

                        <label for="occasion">What's the occasion? <span>\*</span></label>

                        <select name="occasion" id="occasion" required>

                            <option value="">Choose an occasion</option>

                            <option>Birthday</option>

                            <option>Anniversary</option>

                            <option>Graduation</option>

                            <option>Thank You</option>

                            <option>Just Because</option>

                            <option>Other</option>

                        </select>

                    </div>



                    <div class="form-field">

                        <label for="interest">What are they interested in? <span>\*</span></label>

                        <select name="interest" id="interest" required>

                            <option value="">Choose an interest</option>

                            <option>Fashion</option>

                            <option>Beauty</option>

                            <option>Technology</option>

                            <option>Sports</option>

                            <option>Books</option>

                            <option>Food</option>

                            <option>Other</option>

                        </select>

                    </div>



                    <div class="form-field">

                        <label for="recipient_age">What is their age range?</label>

                        <select name="recipient_age" id="recipient_age">

                            <option value="">Prefer not to say</option>

                            <option>Under 18</option>

                            <option>18-24</option>

                            <option>25-34</option>

                            <option>35-44</option>

                            <option>45-54</option>

                            <option>55 and above</option>

                        </select>

                    </div>



                    <div class="form-field">

                        <label for="personality">How would you describe them?</label>

                        <select name="personality" id="personality">

                            <option value="">Choose their personality</option>

                            <option>Creative</option>

                            <option>Adventurous</option>

                            <option>Quiet and thoughtful</option>

                            <option>Outgoing and social</option>

                            <option>Practical</option>

                            <option>Romantic</option>

                            <option>Simple and calm</option>

                        </select>

                    </div>



                    <div class="form-field">

                        <label for="style">What is their style?</label>

                        <select name="style" id="style">

                            <option value="">Choose a style</option>

                            <option>Elegant</option>

                            <option>Minimalist</option>

                            <option>Trendy</option>

                            <option>Classic</option>

                            <option>Colorful</option>

                            <option>Traditional</option>

                            <option>Not sure</option>

                        </select>

                    </div>



                    <div class="form-field full-width">

                        <label for="gift_type">What kind of gift would you prefer?</label>

                        <select name="gift_type" id="gift_type">

                            <option value="">No particular preference</option>

                            <option>Something useful</option>

                            <option>Something personal</option>

                            <option>Something beautiful</option>

                            <option>A surprise</option>

                            <option>An experience</option>

                        </select>

                    </div>



                    <div class="form-field full-width budget-field">

                        <label for="budget">What's your budget? <span>\*</span></label>



                        <div class="budget-input">

                            <span>GHS</span>

                            <input type="number" name="budget" id="budget" min="1" step="0.01"
                                placeholder="Enter your budget" required>

                        </div>



                        <small>Enter any amount you are comfortable spending.</small>

                    </div>



                    <div class="form-field full-width">

                        <label for="extra_notes">Anything else we should know?</label>

                        <textarea name="extra_notes" id="extra_notes" rows="4"
                            placeholder="For example: They love handmade things, enjoy reading, or already own many accessories..."></textarea>

                    </div>



                </div>



                <div class="form-bottom">

                    <p><span>\*</span> Required fields</p>



                    <button type="submit" id="findGiftButton">

                        <span>Find My Gift</span>

                        <span class="button-arrow">→</span>

                    </button>

                </div>



            </form>

        </section>



    </main>



    <footer class="gf-footer">

        <p>Made by Giftly. A little thought goes a long way.</p>

    </footer>



    <script src="../assets/js/gift-finder.js"></script>



</body>



</html>
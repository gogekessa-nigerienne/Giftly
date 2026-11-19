<?php

require_once __DIR__ . '/../includes/session.php';

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| GET USER PREFERENCES AND RESULTS
|--------------------------------------------------------------------------
*/

$preferences = $_SESSION['giftly_preferences'] ?? [];
$results = $_SESSION['giftly_results'] ?? [];

if (empty($preferences) || empty($results)) {
    header('Location: gift_finder.php');
    exit;
}

$recommendations = $results['recommendations'] ?? [];

if (!is_array($recommendations)) {
    $recommendations = [];
}

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE PATHS
|--------------------------------------------------------------------------
*/

$assetsFolder = __DIR__ . '/../assets';
$assetsUrl = '../assets/';

$imageMap = [
    'fashion handbag' => 'OIP (1).webp',
    'leather handbag' => 'OIP (3).webp',
    'jewelry set' => 'best-handmade-african-gifts-3_1800x.webp',
    'personalized necklace' => 'Catherine-Grisez_handcrafted-art-and-jewelry_best-gifts_3954_600x.webp',
    'beauty gift box' => '71FhnzXDJ9L._AC_SL1500_.jpg',
    'gift hamper' => 'ggb-4610_4.jpg',
    'chocolate gift box' => 'OIP (12).webp',
];

/*
|--------------------------------------------------------------------------
| READ PRODUCT CSV
|--------------------------------------------------------------------------
*/

$products = [];

$productsFile = __DIR__ . '/products.csv';

if (is_file($productsFile) && ($handle = fopen($productsFile, 'r')) !== false) {

    $headers = fgetcsv($handle);

    if (is_array($headers)) {

        $headers = array_map(
            fn($header) => strtolower(trim((string) $header)),
            $headers
        );

        while (($row = fgetcsv($handle)) !== false) {

            if (count($row) !== count($headers)) {
                continue;
            }

            $product = array_combine($headers, $row);

            if ($product) {
                $products[] = $product;
            }
        }
    }

    fclose($handle);
}

/*
|--------------------------------------------------------------------------
| PRODUCT FIELD HELPERS
|--------------------------------------------------------------------------
*/

function getProductValue($product, $possibleNames, $default = '')
{
    foreach ($possibleNames as $name) {

        $name = strtolower($name);

        if (
            isset($product[$name]) &&
            trim((string) $product[$name]) !== ''
        ) {
            return trim((string) $product[$name]);
        }
    }

    return $default;
}

function normaliseName($name)
{
    return strtolower(
        trim(
            preg_replace('/\s+/', ' ', (string) $name)
        )
    );
}

/*
|--------------------------------------------------------------------------
| FIND PRODUCT IN CSV
|--------------------------------------------------------------------------
*/

function findProduct($recommendation, $products)
{
    if (is_string($recommendation)) {
        $recommendation = ['name' => $recommendation];
    }

    if (!is_array($recommendation)) {
        return null;
    }

    $recommendationName = getProductValue(
        $recommendation,
        ['product_name', 'name', 'title', 'gift_name']
    );

    if ($recommendationName === '') {
        return null;
    }

    foreach ($products as $product) {

        $productName = getProductValue(
            $product,
            ['product_name', 'name', 'title', 'gift_name']
        );

        if (
            $productName !== '' &&
            normaliseName($productName) === normaliseName($recommendationName)
        ) {
            return array_merge($product, $recommendation);
        }
    }

    return $recommendation;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

function getProductImage($product, $imageMap, $assetsFolder, $assetsUrl)
{
    $name = getProductValue(
        $product,
        ['product_name', 'name', 'title', 'gift_name']
    );

    $mappedImage = $imageMap[normaliseName($name)] ?? '';

    $image = getProductValue(
        $product,
        ['image', 'image_path', 'image_url', 'photo'],
        $mappedImage
    );

    if ($image === '') {
        $image = $mappedImage;
    }

    if ($image === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $image)) {
        return $image;
    }

    $image = str_replace('\\', '/', $image);
    $image = basename($image);

    if (!is_file($assetsFolder . DIRECTORY_SEPARATOR . $image)) {
        return '';
    }

    return $assetsUrl . rawurlencode($image);
}

/*
|--------------------------------------------------------------------------
| PREPARE RECOMMENDED PRODUCTS
|--------------------------------------------------------------------------
*/

$recommendedProducts = [];

foreach ($recommendations as $recommendation) {

    $product = findProduct($recommendation, $products);

    if ($product === null) {
        continue;
    }

    $name = getProductValue(
        $product,
        ['product_name', 'name', 'title', 'gift_name']
    );

    if ($name === '') {
        continue;
    }

    $product['display_name'] = $name;

    $product['display_description'] = getProductValue(
        $product,
        ['description', 'details', 'match_reason', 'reason', 'explanation'],
        'A gift selected based on the preferences you provided.'
    );

    $product['display_price'] = getProductValue(
        $product,
        ['price', 'cost', 'amount'],
        ''
    );

    $product['display_image'] = getProductImage(
        $product,
        $imageMap,
        $assetsFolder,
        $assetsUrl
    );

    $recommendedProducts[] = $product;
}

/*
|--------------------------------------------------------------------------
| RECIPIENT INFORMATION
|--------------------------------------------------------------------------
*/

$recipient = $preferences['relationship'] ?? 'your recipient';
$occasion = $preferences['occasion'] ?? 'your occasion';
$budget = $preferences['budget'] ?? '';

$recommendationMessage = $results['message'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Your Gift Recommendations | Giftly</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #fff8fb;
            color: #333;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
        }

        /* HEADER */

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            padding: 16px 6%;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #8b1e3f;
        }

        nav {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        nav a {
            color: #8b1e3f;
            font-size: 14px;
            font-weight: bold;
            padding: 10px 16px;
            border-radius: 22px;
            background: #fff0f4;
            transition: 0.3s;
        }

        nav a:hover {
            background: #8b1e3f;
            color: #fff;
        }

        /* PAGE HEADING */

        .page-heading {
            text-align: center;
            padding: 55px 20px 35px;
            background: linear-gradient(135deg, #8b1e3f, #c94f76);
            color: white;
        }

        .page-heading h1 {
            font-size: 34px;
            margin-bottom: 12px;
        }

        .page-heading p {
            font-size: 15px;
            line-height: 1.7;
        }

        /* PREFERENCES */

        .preferences {
            max-width: 1050px;
            margin: 30px auto;
            padding: 22px 25px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }

        .preferences h2 {
            color: #8b1e3f;
            font-size: 19px;
            margin-bottom: 15px;
        }

        .preference-details {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .preference-item {
            background: #fff0f4;
            color: #8b1e3f;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 13px;
        }

        /* RECOMMENDATIONS */

        .recommendations {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 25px;
        }

        .section-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-heading h2 {
            color: #8b1e3f;
            font-size: 27px;
            margin-bottom: 10px;
        }

        .section-heading p {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }

        .gift-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
        }

        .gift-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
            transition: 0.3s;
        }

        .gift-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 22px rgba(139, 30, 63, 0.13);
        }

        .gift-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
            background: #fff0f4;
        }

        .image-placeholder {
            height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff0f4;
            color: #8b1e3f;
            font-size: 14px;
        }

        .gift-info {
            padding: 20px;
        }

        .gift-info h3 {
            color: #8b1e3f;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .gift-info p {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .price {
            color: #8b1e3f !important;
            font-weight: bold;
            font-size: 16px !important;
        }

        .empty-message {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            color: #777;
            line-height: 1.7;
        }

        /* ACTIONS */

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 15px;
            margin: 45px 20px 60px;
        }

        .action-button {
            display: inline-block;
            padding: 13px 25px;
            border-radius: 25px;
            background: #8b1e3f;
            color: white;
            font-weight: bold;
            font-size: 14px;
            transition: 0.3s;
        }

        .action-button:hover {
            background: #c94f76;
            transform: translateY(-2px);
        }

        .action-button.secondary {
            background: #fff0f4;
            color: #8b1e3f;
            border: 1px solid #e9c2ce;
        }

        .action-button.secondary:hover {
            background: #f8dce5;
        }

        /* FOOTER */

        footer {
            background: #35101e;
            color: white;
            text-align: center;
            padding: 28px 15px;
        }

        footer .footer-logo {
            font-size: 24px;
            font-weight: bold;
            color: #ff9fba;
            margin-bottom: 10px;
        }

        footer p {
            font-size: 13px;
            color: #ead6dd;
            line-height: 1.7;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {

            .gift-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

        }

        @media (max-width: 600px) {

            header {
                flex-direction: column;
            }

            nav {
                justify-content: center;
            }

            .page-heading h1 {
                font-size: 27px;
            }

            .gift-grid {
                grid-template-columns: 1fr;
            }

            .preferences {
                margin: 20px 15px;
            }

            .recommendations {
                padding: 0 15px;
            }

        }
    </style>

</head>

<body>

    <!-- HEADER -->

    <header>

        <a href="../pages/home/Home.php" class="logo">
            Giftly.
        </a>

        <nav>

            <a href="../pages/home/Home.php">Home</a>

            <a href="../pages/shop/gifts.php">Available Gifts</a>

            <a href="gift_finder.php">Gift Finder</a>

        </nav>

    </header>

    <!-- PAGE HEADING -->

    <section class="page-heading">

        <h1>Your Personalised Gift Recommendations</h1>

        <p>
            We found gift ideas based on the preferences you shared.
        </p>

    </section>

    <!-- PREFERENCES -->

    <section class="preferences">

        <h2>Your Gift Preferences</h2>

        <div class="preference-details">

            <span class="preference-item">
                Recipient: <?= h($recipient) ?>
            </span>

            <span class="preference-item">
                Occasion: <?= h($occasion) ?>
            </span>

            <?php if ($budget !== ''): ?>

                <span class="preference-item">
                    Budget: GHS <?= h($budget) ?>
                </span>

            <?php endif; ?>

            <?php if (!empty($preferences['interest'])): ?>

                <span class="preference-item">
                    Interest: <?= h($preferences['interest']) ?>
                </span>

            <?php endif; ?>

            <?php if (!empty($preferences['personality'])): ?>

                <span class="preference-item">
                    Personality: <?= h($preferences['personality']) ?>
                </span>

            <?php endif; ?>

        </div>

    </section>

    <!-- RECOMMENDED GIFTS -->

    <main class="recommendations">

        <div class="section-heading">

            <h2>Recommended Gifts</h2>

            <p>
                These suggestions are based on the information you provided.
            </p>

        </div>

        <?php if (!empty($recommendationMessage)): ?>

            <p style="text-align:center; margin-bottom:25px; color:#777;">
                <?= h($recommendationMessage) ?>
            </p>

        <?php endif; ?>

        <?php if (!empty($recommendedProducts)): ?>

            <div class="gift-grid">

                <?php foreach ($recommendedProducts as $product): ?>

                    <article class="gift-card">

                        <?php if (!empty($product['display_image'])): ?>

                            <img class="gift-image" src="<?= h($product['display_image']) ?>"
                                alt="<?= h($product['display_name']) ?>">

                        <?php else: ?>

                            <div class="image-placeholder">
                                Image not available
                            </div>

                        <?php endif; ?>

                        <div class="gift-info">

                            <h3>
                                <?= h($product['display_name']) ?>
                            </h3>

                            <p>
                                <?= h($product['display_description']) ?>
                            </p>

                            <?php if ($product['display_price'] !== ''): ?>

                                <p class="price">
                                    GHS <?= h($product['display_price']) ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-message">

                <h3>No recommendations could be displayed.</h3>

                <p>
                    The recommendation system did not return any matching
                    products. Please try the Gift Finder again.
                </p>

            </div>

        <?php endif; ?>

    </main>

    <!-- ACTION BUTTONS -->

    <div class="actions">

        <a href="../pages/shop/gifts.php" class="action-button">
            Browse All Gifts
        </a>

        <a href="gift_finder.php" class="action-button secondary">
            Try Gift Finder Again
        </a>

    </div>

    <!-- FOOTER -->

    <footer>

        <div class="footer-logo">Giftly.</div>

        <p>
            Making every moment special, one gift at a time.
        </p>

        <p>
            &copy; 2026 Giftly. All rights reserved.
        </p>

    </footer>

</body>

</html>
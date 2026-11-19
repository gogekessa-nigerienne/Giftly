<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

$categories = $pdo->query(
    "SELECT category_id, category_name
     FROM categories
     WHERE is_active = 1
     ORDER BY category_name"
)->fetchAll();

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token'] ?? ''
        )
    ) {
        exit('Invalid request. Please refresh the page.');
    }

    $name = trim($_POST['product_name'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock_quantity'] ?? '0';
    $active = isset($_POST['is_active']) ? 1 : 0;
    $featured = isset($_POST['is_featured']) ? 1 : 0;

    if ($name === '' || strlen($name) > 150) {
        $error = 'Enter a product name (maximum 150 characters).';
    } elseif (strlen($brand) > 100) {
        $error = 'Brand must not exceed 100 characters.';
    } elseif (!is_numeric($price) || (float) $price <= 0) {
        $error = 'Enter a valid price greater than zero.';
    } elseif (!ctype_digit((string) $stock) || (int) $stock < 0) {
        $error = 'Enter a valid stock quantity.';
    } else {
        $checkCategory = $pdo->prepare(
            "SELECT COUNT(*) FROM categories
             WHERE category_id = ? AND is_active = 1"
        );
        $checkCategory->execute([$categoryId]);

        if (!$checkCategory->fetchColumn()) {
            $error = 'Please select a valid category.';
        }
    }

    $imagePath = null;

    if (
        $error === '' && isset($_FILES['product_image'])
        && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['product_image'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Image upload failed. Please try again.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Image must be smaller than 5 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            if (
                !isset($allowed[$mime]) ||
                getimagesize($file['tmp_name']) === false
            ) {
                $error = 'Only JPG, PNG and WebP images are allowed.';
            } else {
                $uploadDirectory =
                    __DIR__ . '/../assets/uploads/products/';

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                $filename = bin2hex(random_bytes(16))
                    . '.' . $allowed[$mime];

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        $uploadDirectory . $filename
                    )
                ) {
                    $imagePath = 'assets/uploads/products/' . $filename;
                } else {
                    $error = 'Could not save the uploaded image.';
                }
            }
        }
    }

    if ($error === '') {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'product';
        }

        // Make sure the slug is unique.
        $baseSlug = $slug;
        $number = 1;

        $checkSlug = $pdo->prepare(
            "SELECT COUNT(*) FROM products WHERE slug = ?"
        );

        while (true) {
            $checkSlug->execute([$slug]);

            if ((int) $checkSlug->fetchColumn() === 0) {
                break;
            }

            $number++;
            $slug = $baseSlug . '-' . $number;
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO products
                (category_id, product_name, brand, slug,
                 description, price, image_path,
                 stock_quantity, is_active, is_featured)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $categoryId,
                $name,
                $brand !== '' ? $brand : null,
                $slug,
                $description !== '' ? $description : null,
                $price,
                $imagePath,
                (int) $stock,
                $active,
                $featured
            ]);

            header('Location: products.php?added=1');
            exit;

        } catch (PDOException $e) {
            if ($imagePath !== null) {
                @unlink(__DIR__ . '/../' . $imagePath);
            }

            $error = 'Could not save the product. Please check the database.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Product - Giftly</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff5f7;
            font-family: Arial, sans-serif;
            color: #444;
        }

        .container {
            max-width: 750px;
            margin: 35px auto;
            padding: 30px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 20px #0000000c;
        }

        h1 {
            color: #a64d68;
        }

        label {
            display: block;
            margin-top: 18px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 15px;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .check {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .check input {
            width: auto;
        }

        button,
        .back {
            display: inline-block;
            margin-top: 22px;
            padding: 13px 20px;
            border: none;
            border-radius: 7px;
            background: #bd6b83;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .back {
            background: #777;
        }

        .error {
            background: #ffe7e7;
            color: #a00000;
            padding: 12px;
            border-radius: 7px;
        }

        .help {
            color: #888;
            font-size: 13px;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>Add New Product</h1>
        <p>Enter the details of the product you want to sell.</p>

        <?php if ($error): ?>
            <p class="error">
                <?= h($error) ?>
            </p>
        <?php endif; ?>

        <?php if (count($categories) === 0): ?>
            <p class="error">
                No active categories exist. Please create a category first.
            </p>
        <?php else: ?>

            <form method="POST" enctype="multipart/form-data">

                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">

                <label>Product Name *</label>
                <input type="text" name="product_name" maxlength="150" required>

                <label>Brand</label>
                <input type="text" name="brand" maxlength="100" placeholder="Enter brand name">

                <label>Category *</label>
                <select name="category_id" required>
                    <option value="">Choose a category</option>

                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['category_id'] ?>">
                            <?= h($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Description</label>
                <textarea name="description" placeholder="Describe this product..."></textarea>

                <label>Price (GHS) *</label>
                <input type="number" name="price" min="0.01" step="0.01" required>

                <label>Stock Quantity *</label>
                <input type="number" name="stock_quantity" min="0" value="0" required>

                <label>Product Image</label>
                <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                <p class="help">JPG, PNG or WebP. Maximum size: 5 MB.</p>

                <label class="check">
                    <input type="checkbox" name="is_active" checked>
                    Active product
                </label>

                <label class="check">
                    <input type="checkbox" name="is_featured">
                    Featured product
                </label>

                <button type="submit">Save Product</button>

                <a class="back" href="products.php">Cancel</a>

            </form>

        <?php endif; ?>

    </div>

</body>

</html>
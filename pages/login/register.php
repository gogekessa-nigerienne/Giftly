<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$auth = new AuthController();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = $auth->registerUser($_POST);

    if ($result === true) {
        $_SESSION['success_message'] =
            'Your account has been created successfully. Please log in.';

        header('Location: ../customer/login.php');
        exit();
    }

    $error = $result;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Giftly</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 20%, #fce5eb 0, transparent 30%),
                radial-gradient(circle at 90% 80%, #f9e5e9 0, transparent 28%),
                #fff9fa;
            color: #34252a;
        }

        /* Header */
        .header {
            height: 82px;
            padding: 0 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.85);
            border-bottom: 1px solid #f1e3e7;
        }

        .logo img {
            width: 125px;
            height: auto;
            display: block;
        }

        .home-link {
            color: #70283f;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 11px 20px;
            border: 1px solid #ead5dc;
            border-radius: 25px;
            transition: 0.25s;
        }

        .home-link:hover {
            background: #70283f;
            color: white;
            border-color: #70283f;
        }

        /* Main layout */
        .page-container {
            width: 100%;
            padding: 45px 6% 65px;
        }

        .page-intro {
            margin-bottom: 28px;
        }

        .page-intro .eyebrow {
            color: #a45b72;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }

        .page-intro h1 {
            font-size: clamp(29px, 3vw, 39px);
            color: #512033;
            margin-bottom: 9px;
            font-weight: 600;
            letter-spacing: -1px;
        }

        .page-intro p {
            color: #88777d;
            font-size: 14px;
            font-weight: 400;
        }

        /* Registration layout */
        .register-layout {
            display: grid;
            grid-template-columns: 0.85fr 1.5fr;
            min-height: 550px;
            border-radius: 18px;
            overflow: hidden;
            background: white;
            box-shadow: 0 18px 55px rgba(91, 35, 54, 0.10);
            border: 1px solid #f1e4e8;
        }

        /* Welcome panel */
        .welcome-panel {
            position: relative;
            overflow: hidden;
            padding: 40px 42px 25px;
            background: linear-gradient(145deg, #70283f, #4d1b30);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
            flex: 1;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .welcome-panel h2 {
            font-size: 31px;
            line-height: 1.5;
            font-weight: 600;
            letter-spacing: -0.7px;
            max-width: 350px;
        }

        /* Gift illustration at the bottom */
        .gift-illustration {
            width: 100%;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            position: relative;
            flex-shrink: 0;
            margin-top: 15px;
        }

        .gift-box {
            position: relative;
            width: 145px;
            height: 105px;
            background: #f4c5d3;
            border-radius: 5px;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.16);
        }

        .gift-box::before {
            content: "";
            position: absolute;
            left: 59px;
            top: 0;
            height: 100%;
            width: 27px;
            background: #a84e6c;
        }

        .gift-lid {
            position: absolute;
            width: 165px;
            height: 25px;
            background: #f9d9e3;
            left: -10px;
            top: -23px;
            border-radius: 5px;
        }

        .gift-ribbon {
            position: absolute;
            width: 52px;
            height: 28px;
            border: 9px solid #e9a8bd;
            border-radius: 50%;
            top: -49px;
            left: 48px;
            transform: rotate(-25deg);
        }

        .gift-ribbon::after {
            content: "";
            position: absolute;
            width: 52px;
            height: 28px;
            border: 9px solid #e9a8bd;
            border-radius: 50%;
            left: 23px;
            top: 1px;
            transform: rotate(55deg);
        }

        /* Form panel */
        .form-panel {
            padding: 48px 55px;
            background: white;
        }

        .form-heading {
            margin-bottom: 28px;
        }

        .form-heading h2 {
            color: #542237;
            font-size: 25px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .form-heading p {
            color: #928187;
            font-size: 12px;
            font-weight: 400;
        }

        .error-message {
            background: #fff1f2;
            color: #a52b3a;
            border-left: 4px solid #c34d60;
            padding: 13px 15px;
            margin-bottom: 22px;
            border-radius: 5px;
            font-size: 12px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 12px;
            color: #57434a;
            font-weight: 600;
            margin-bottom: 9px;
        }

        .form-group input {
            width: 100%;
            height: 49px;
            padding: 0 15px;
            border: 1px solid #e9dfe2;
            border-radius: 7px;
            background: #fffdfd;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            color: #392b30;
            outline: none;
            transition: 0.2s;
        }

        .form-group input::placeholder {
            color: #c0b3b8;
            font-size: 11px;
        }

        .form-group input:focus {
            border-color: #9a4c66;
            background: white;
            box-shadow: 0 0 0 3px rgba(154, 76, 102, 0.09);
        }

        .password-note {
            font-size: 10px;
            color: #a18d94;
            margin-top: 7px;
        }

        .form-footer {
            margin-top: 32px;
            padding-top: 25px;
            border-top: 1px solid #f1e8eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .register-button {
            padding: 15px 32px;
            border: none;
            border-radius: 7px;
            background: #70283f;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 7px 16px rgba(112, 40, 63, 0.17);
            transition: 0.25s;
        }

        .register-button:hover {
            background: #511b30;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(112, 40, 63, 0.22);
        }

        .login-text {
            font-size: 11px;
            color: #928187;
        }

        .login-text a {
            color: #70283f;
            font-weight: 700;
            text-decoration: none;
            margin-left: 3px;
        }

        .login-text a:hover {
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 950px) {
            .page-container {
                padding: 35px 4%;
            }

            .register-layout {
                grid-template-columns: 0.8fr 1.2fr;
            }

            .welcome-panel {
                padding: 35px 28px 25px;
            }

            .form-panel {
                padding: 40px 30px;
            }

            .welcome-panel h2 {
                font-size: 27px;
            }
        }

        @media (max-width: 700px) {
            .register-layout {
                grid-template-columns: 1fr;
            }

            .welcome-panel {
                min-height: 390px;
                padding: 35px 28px 25px;
            }

            .welcome-panel h2 {
                font-size: 26px;
            }

            .gift-illustration {
                height: 130px;
                margin-top: 15px;
            }

            .form-panel {
                padding: 35px 25px;
            }
        }

        @media (max-width: 480px) {
            .header {
                height: 70px;
                padding: 0 5%;
            }

            .logo img {
                width: 105px;
            }

            .home-link {
                font-size: 11px;
                padding: 9px 13px;
            }

            .page-container {
                padding: 28px 4% 40px;
            }

            .page-intro h1 {
                font-size: 27px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .form-panel {
                padding: 30px 20px;
            }

            .form-footer {
                align-items: stretch;
                flex-direction: column;
            }

            .register-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header class="header">
        <a href="../home.php" class="logo">
            <img src="../assets/Logo.png" alt="Giftly Logo">
        </a>

        <a href="../home.php" class="home-link">Back to Home</a>
    </header>

    <main class="page-container">

        <div class="page-intro">

            <h1>A little joy starts here.</h1>
            <p>Create your account and make every gifting moment special.</p>
        </div>

        <div class="register-layout">

            <section class="welcome-panel">

                <div class="welcome-content">
                    <h2>Make every moment worth celebrating.</h2>
                </div>

                <div class="gift-illustration">
                    <div class="gift-box">
                        <div class="gift-lid"></div>
                        <div class="gift-ribbon"></div>
                    </div>
                </div>

            </section>

            <section class="form-panel">

                <div class="form-heading">
                    <h2>Create your account</h2>
                    <p>Enter your details below to get started.</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" placeholder="Enter your first name"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" placeholder="Enter your last name"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email"
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" placeholder="Enter your phone number"
                                value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Create a password"
                                minlength="8" required>
                            <span class="password-note">At least 8 characters</span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Enter your password again" minlength="8" required>
                        </div>

                    </div>

                    <div class="form-footer">

                        <button type="submit" class="register-button">
                            Create My Account
                        </button>

                        <p class="login-text">
                            Already have an account?
                            <a href="../customer/login.php">Log in</a>
                        </p>

                    </div>

                </form>

            </section>

        </div>

    </main>

</body>

</html>
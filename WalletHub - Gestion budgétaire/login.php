<?php
require 'config.php';
require 'functions/user.php';

$errors = [];
$success = '';
$email = $_GET['email'] ?? ''; // Pre-fill from GET if redirected from register

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email)) {
        $errors['email'] = "Veuillez renseigner l'email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    }
    if (empty($password)) {
        $errors['password'] = "Veuillez renseigner le mot de passe.";
    }

    // 
    if (empty($errors)) {
        $user = logIn($email, $password, $pdo);
        if ($user) {
            $_SESSION['user'] = $user;
            $success = "Connexion réussie !";
            // header("Refresh: 2; Location: dashboard.php");
            // exit;
        } else {
            $errors['general'] = "Email ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - WalletHub</title>
    <link rel="stylesheet" href="./css/login.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml" href='data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M16.5008 14.1502H16.5098M19 4.00098H6.2C5.0799 4.00098 4.51984 4.00098 4.09202 4.21896C3.71569 4.41071 3.40973 4.71667 3.21799 5.093C3 5.52082 3 6.08087 3 7.20098V16.801C3 17.9211 3 18.4811 3.21799 18.909C3.40973 19.2853 3.71569 19.5912 4.09202 19.783C4.51984 20.001 5.07989 20.001 6.2 20.001H17.8C18.9201 20.001 19.4802 20.001 19.908 19.783C20.2843 19.5912 20.5903 19.2853 20.782 18.909C21 18.4811 21 17.9211 21 16.801V11.201C21 10.0809 21 9.52082 20.782 9.093C20.5903 8.71667 20.2843 8.41071 19.908 8.21896C19.4802 8.00098 18.9201 8.00098 17.8 8.00098H7M16.9508 14.1502C16.9508 14.3987 16.7493 14.6002 16.5008 14.6002C16.2523 14.6002 16.0508 14.3987 16.0508 14.1502C16.0508 13.9017 16.2523 13.7002 16.5008 13.7002C16.7493 13.7002 16.9508 13.9017 16.9508 14.1502Z" stroke="%23000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>

<body>

    <div class="container" data-aos="fade-down" data-aos-duration="1600">

        <div class="title_container">
            <h2 class="title">Connexion</h2>
            <span class="subtitle">Entrez vos identifiants pour accéder à votre compte.</span>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <p class="error"><i class="fa-solid fa-circle-exclamation"></i>
                <?= sanitize($errors['general']) ?>
            </p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><i class="fa-solid fa-check-circle"></i>&nbsp; <?= sanitize($success) ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">

            <label for="email">Email :</label>
            <div class="input-icon">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="name@mail.com"
                    value="<?= sanitize($email) ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?>
                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                    <?= sanitize($errors['email']) ?>
                </span>
            <?php endif; ?>

            <label for="password">Mot de passe :</label>
            <div class="input-icon">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <?php if (!empty($errors['password'])): ?>
                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                    <?= sanitize($errors['password']) ?>
                </span>
            <?php endif; ?>

            <input name="submit" type="submit" value="Se connecter">
        </form>

        <div class="separator">
            <hr class="line"><span>Ou</span>
            <hr class="line">
        </div>
        <p class="switch-form">
            Pas de compte ? <a href="register.php">Inscrivez-vous ici</a>.
        </p>
    </div>
    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>

</body>

<?php if ($success): ?>
    <script>
        setTimeout(function() {
            window.location.href = "dashboard.php";
        }, 2000);
    </script>
<?php endif; ?>

</html>
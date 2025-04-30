<?php
require 'config.php';
require 'functions/user.php';

$errors = [];
$success = '';
$nom   = '';
$email = $_GET['email'] ?? ''; // Pre-fill from GET if redirected from login

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
    $nom = sanitize($_POST['nom'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($nom)) {
        $errors['nom'] = "Veuillez renseigner le nom.";
    }
    if (empty($email)) {
        $errors['email'] = "Veuillez renseigner l'email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    }
    if (empty($password)) {
        $errors['password'] = "Veuillez renseigner le mot de passe.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Le mot de passe doit contenir au moins 6 caractères.";
    }
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Veuillez confirmer le mot de passe.";
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmtCheck->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtCheck->execute();
        if ($stmtCheck->fetchColumn() > 0) {
            $errors['email'] = "Cet email est déjà utilisé.";
        } else {
            $user = ['nom' => $nom, 'email' => $email, 'password' => $password];
            if (addUser($user, $pdo)) {
                $success = "Inscription réussie ! Redirection vers la connexion...";
                $redirectUrl = "login.php?email=" . urlencode($email);

                // header("Refresh: 2; location: login.php?email=" . urlencode($email));
                // exit;
            } else {
                $errors['general'] = "Erreur lors de l'inscription.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - WalletHub</title>
    <link rel="stylesheet" href="./css/register.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml" href='data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M16.5008 14.1502H16.5098M19 4.00098H6.2C5.0799 4.00098 4.51984 4.00098 4.09202 4.21896C3.71569 4.41071 3.40973 4.71667 3.21799 5.093C3 5.52082 3 6.08087 3 7.20098V16.801C3 17.9211 3 18.4811 3.21799 18.909C3.40973 19.2853 3.71569 19.5912 4.09202 19.783C4.51984 20.001 5.07989 20.001 6.2 20.001H17.8C18.9201 20.001 19.4802 20.001 19.908 19.783C20.2843 19.5912 20.5903 19.2853 20.782 18.909C21 18.4811 21 17.9211 21 16.801V11.201C21 10.0809 21 9.52082 20.782 9.093C20.5903 8.71667 20.2843 8.41071 19.908 8.21896C19.4802 8.00098 18.9201 8.00098 17.8 8.00098H7M16.9508 14.1502C16.9508 14.3987 16.7493 14.6002 16.5008 14.6002C16.2523 14.6002 16.0508 14.3987 16.0508 14.1502C16.0508 13.9017 16.2523 13.7002 16.5008 13.7002C16.7493 13.7002 16.9508 13.9017 16.9508 14.1502Z" stroke="%23000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>

<body>
    <div class="container" data-aos="fade-down" data-aos-duration="1600">
        <div class="register-form">

            <div class="title_container">
                <h2 class="title">Inscription</h2>
                <span class="subtitle">Créez votre compte en quelques secondes.</span>
            </div>

            <?php if ($success): ?>
                <p class="success"><i class="fa-solid fa-check-circle"></i>&nbsp; <?= sanitize($success) ?></p>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                    <?= sanitize($errors['general']) ?>
                </span>
            <?php endif; ?>

            <form action="register.php" method="post">

                <label for="nom">Nom :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-signature"></i>
                    <input type="text" id="nom" name="nom" placeholder="name" value="<?= sanitize($nom) ?>">
                </div>
                <?php if (isset($errors['nom'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($errors['nom']) ?>
                    </span>
                <?php endif; ?>


                <label for="email">Email :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="name@mail.com"
                        value="<?= sanitize($email) ?>">
                </div>
                <?php if (isset($errors['email']) && strpos($errors['email'], 'déjà utilisé') === false): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($errors['email']) ?>
                    </span>
                <?php endif; ?>

                <?php if (isset($errors['email']) && strpos($errors['email'], 'déjà utilisé') !== false): ?>
                    <p class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($errors['email']) ?>
                    </p>
                <?php endif; ?>

                <label for="password">Mot de passe :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••">
                </div>
                <?php if (isset($errors['password'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($errors['password']) ?>
                    </span>
                <?php endif; ?>

                <label for="confirm_password">Confirmer le mot de passe :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••">
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= sanitize($errors['confirm_password']) ?>
                    </span>
                <?php endif; ?>

                <input type="submit" name="submit" value=" S'inscrire">
            </form>

            <div class="separator">
                <hr class="line"><span>Ou</span>
                <hr class="line">
            </div>
            <p class="switch-form">
                Déjà un compte ? <a href="login.php">Connectez-vous ici</a>.
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
            window.location.href = "<?= $redirectUrl ?>";
        }, 2000);
    </script>
<?php endif; ?>

</html>
<?php exit; ?>
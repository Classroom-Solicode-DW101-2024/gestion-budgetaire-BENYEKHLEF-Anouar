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

    if (empty($errors)) {
        // 
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
    <title>Inscription - Gestion Budget</title>
    <link rel="stylesheet" href="./css/register.css?v=<?php echo time(); ?>">
    <link rel="icon"
        href="data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M3.17157 20.8284C4.34315 22 6.22876 22 10 22H14C17.7712 22 19.6569 22 20.8284 20.8284C22 19.6569 22 17.7712 22 14C22 12.8302 22 11.8419 21.965 11M20.8284 7.17157C19.6569 6 17.7712 6 14 6H10C6.22876 6 4.34315 6 3.17157 7.17157C2 8.34315 2 10.2288 2 14C2 15.1698 2 16.1581 2.03496 17' stroke='%231C274C' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M12 2C13.8856 2 14.8284 2 15.4142 2.58579C16 3.17157 16 4.11438 16 6M8.58579 2.58579C8 3.17157 8 4.11438 8 6' stroke='%231C274C' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M12 17.3333C13.1046 17.3333 14 16.5871 14 15.6667C14 14.7462 13.1046 14 12 14C10.8954 14 10 13.2538 10 12.3333C10 11.4129 10.8954 10.6667 12 10.6667M12 17.3333C10.8954 17.3333 10 16.5871 10 15.6667M12 17.3333V18M12 10V10.6667M12 10.6667C13.1046 10.6667 14 11.4129 14 12.3333' stroke='%231C274C' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"
        type="image/x-icon">
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

            <?php if (!empty($errors['email']) && strpos($errors['email'], 'déjà utilisé') !== false): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($errors['email']) ?>
                </p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="success"><i class="fa-solid fa-check-circle"></i>&nbsp; <?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form action="register.php" method="post">

                <label for="nom">Nom :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-signature"></i>
                    <input type="text" id="nom" name="nom" placeholder="name" value="<?= htmlspecialchars($nom) ?>"
                        required>
                </div>
                <?php if (!empty($errors['nom'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= htmlspecialchars($errors['nom']) ?>
                    </span>
                <?php endif; ?>


                <label for="email">Email :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="name@mail.com"
                        value="<?= htmlspecialchars($email) ?>" required>
                </div>
                <?php if (!empty($errors['email']) && strpos($errors['email'], 'déjà utilisé') === false): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= htmlspecialchars($errors['email']) ?>
                    </span>
                <?php endif; ?>



                <label for="password">Mot de passe :</label>
                <div class="input-icon">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                        <?= htmlspecialchars($errors['password']) ?>
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
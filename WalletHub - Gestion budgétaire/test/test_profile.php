<?php
require 'config.php';

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$success = '';
$errors = [];

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($nom)) {
        $errors['nom'] = "Le nom est requis.";
    }
    if (empty($email)) {
        $errors['email'] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = "Cet email est déjà utilisé.";
        }
    }

    // Password change validation
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        if (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = "Mot de passe actuel incorrect.";
        }
        if (strlen($newPassword) < 8) {
            $errors['new_password'] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = "Les mots de passe ne correspondent pas.";
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Update basic info
            $stmt = $pdo->prepare("UPDATE users SET nom = ?, email = ? WHERE id = ?");
            $stmt->execute([$nom, $email, $userId]);

            // Update password if provided
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);
            }

            $pdo->commit();
            $success = "Profil mis à jour avec succès.";
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = "Une erreur est survenue lors de la mise à jour du profil.";
        }
    }
    
    // If there are errors, open the modal when page reloads
    $openModalOnLoad = isset($errors);
}

// Function to get user creation date formatted
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Gestion Budget</title>
    <link rel="stylesheet" href="./css/transactions.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml" href='data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M16.5008 14.1502H16.5098M19 4.00098H6.2C5.0799 4.00098 4.51984 4.00098 4.09202 4.21896C3.71569 4.41071 3.40973 4.71667 3.21799 5.093C3 5.52082 3 6.08087 3 7.20098V16.801C3 17.9211 3 18.4811 3.21799 18.909C3.40973 19.2853 3.71569 19.5912 4.09202 19.783C4.51984 20.001 5.07989 20.001 6.2 20.001H17.8C18.9201 20.001 19.4802 20.001 19.908 19.783C20.2843 19.5912 20.5903 19.2853 20.782 18.909C21 18.4811 21 17.9211 21 16.801V11.201C21 10.0809 21 9.52082 20.782 9.093C20.5903 8.71667 20.2843 8.41071 19.908 8.21896C19.4802 8.00098 18.9201 8.00098 17.8 8.00098H7M16.9508 14.1502C16.9508 14.3987 16.7493 14.6002 16.5008 14.6002C16.2523 14.6002 16.0508 14.3987 16.0508 14.1502C16.0508 13.9017 16.2523 13.7002 16.5008 13.7002C16.7493 13.7002 16.9508 13.9017 16.9508 14.1502Z" stroke="%23000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>

<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <nav class="nav-items">
                <a href="home.php" class="nav-item" title="Accueil"><i class="fa-solid fa-home"></i></a>
                <a href="dashboard.php" class="nav-item" title="Tableau de bord"><i class="fa-solid fa-gauge-simple"></i></a>
                <a href="transactions.php" class="nav-item" title="Transactions"><i class="fa-solid fa-exchange-alt"></i></a>
                <a href="profile.php" class="nav-item active" title="Profil"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php" class="nav-item" title="Déconnexion"><i class="fa-solid fa-sign-out-alt"></i></a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="main-content" data-aos="fade-right" data-aos-duration="1600">
            <header>
                <h1>Votre Profil</h1>
            </header>

            <?php if ($success): ?>
                <p class="success"><i class="fa-solid fa-check-circle"></i> <?= sanitize($success) ?></p>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['general']) ?></p>
            <?php endif; ?>

            <button id="open-modal" class="btn primary"><i class="fa-solid fa-user-edit"></i>&nbsp; Modifier votre profil</button>

            <section class="profile-section">
                <h3><i class="fa-solid fa-user"></i> Informations personnelles</h3>
                <div class="profile-info">
                    <div class="profile-item">
                        <div class="profile-label">Nom:</div>
                        <div class="profile-value"><i class="fa-solid fa-user"></i> <?= sanitize($user['nom']) ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">Email:</div>
                        <div class="profile-value"><i class="fa-solid fa-envelope"></i> <?= sanitize($user['email']) ?></div>
                    </div>
                    <div class="profile-item">
                        <div class="profile-label">Membre depuis:</div>
                        <div class="profile-value"><i class="fa-solid fa-calendar"></i> <?= isset($user['created_at']) ? formatDate($user['created_at']) : 'Non disponible' ?></div>
                    </div>
                </div>
            </section>
            
            <!-- Modal for editing profile -->
            <div id="profile-modal" class="modal">
                <div class="modal-content">
                    <span id="close-modal" class="modal-close">&times;</span>
                    <h3><i class="fa-solid fa-user-edit"></i> Modifier votre profil</h3>
                    <form method="post" action="profile.php">
                        <div class="input-group">
                            <label for="nom">Nom complet:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-user"></i>
                                <input type="text" id="nom" name="nom" value="<?= sanitize($user['nom']) ?>">
                            </div>
                            <?php if (isset($errors['nom'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['nom']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="input-group">
                            <label for="email">Email:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-envelope"></i>
                                <input type="email" id="email" name="email" value="<?= sanitize($user['email']) ?>">
                            </div>
                            <?php if (isset($errors['email'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['email']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="input-group">
                            <label for="current_password">Mot de passe actuel:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="current_password" name="current_password">
                            </div>
                            <?php if (isset($errors['current_password'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['current_password']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="input-group">
                            <label for="new_password">Nouveau mot de passe:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-key"></i>
                                <input type="password" id="new_password" name="new_password">
                            </div>
                            <?php if (isset($errors['new_password'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['new_password']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirmer le nouveau mot de passe:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-key"></i>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['confirm_password']) ?></span>
                            <?php endif; ?>
                        </div>

                        <input type="submit" value="Mettre à jour">
                    </form>
                </div>
            </div>

            <div class="action-buttons">
                <a href="dashboard.php" class="btn secondary"><i class="fa-solid fa-gauge-simple"></i> Retour au tableau de bord</a>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const openBtn = document.getElementById('open-modal');
        const closeBtn = document.getElementById('close-modal');
        const modal = document.getElementById('profile-modal');

        openBtn.addEventListener('click', () => {
            modal.classList.add('active');
        });

        closeBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        // Close if click outside content
        modal.addEventListener('click', e => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
        
        // Open modal on load if there are errors
        <?php if (!empty($errors)): ?>
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('profile-modal').classList.add('active');
        });
        <?php endif; ?>
    </script>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
<?php
require 'config.php';
require 'functions/transactions.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$errors = [];
$success = '';

if (!isset($_GET['id'])) {
    header('Location: transactions.php');
    exit;
}

$id = sanitize($_GET['id']);

// Fetch the transaction details
$stmt = $pdo->prepare("SELECT t.*, c.nom as category_name, c.type 
FROM transactions t JOIN categories c ON t.category_id = c.id 
WHERE t.id = :id AND t.user_id = :user_id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$transaction) {
    die("Transaction non trouvée.");
}

// Fetch categories from database
$stmt = $pdo->prepare("SELECT nom, type FROM categories WHERE type = :type");
$stmt->execute(['type' => 'revenu']);
$revenuCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt->execute(['type' => 'depense']);
$depenseCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = sanitize($_POST['type'] ?? '');
    $montant = sanitize($_POST['montant'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date_transaction = sanitize($_POST['date_transaction'] ?? '');

    // Validation
    if (empty($type) || !in_array($type, ['revenu', 'depense'])) {
        $errors['type'] = "Type de transaction invalide.";
    }
    if (empty($montant) || !is_numeric($montant) || $montant <= 0) {
        $errors['montant'] = "Montant invalide.";
    }
    if (empty($category)) {
        $errors['category'] = "Catégorie requise.";
    }
    if (empty($date_transaction)) {
        $errors['date_transaction'] = "Date de transaction requise.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE nom = :nom AND type = :type");
        $stmt->bindParam(':nom', $category, PDO::PARAM_STR);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->execute();
        $catData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($catData) {
            $newTransaction = [
                'category_id'   => $catData['id'],
                'montant'       => $montant,
                'description'   => $description,
                'date_transaction' => $date_transaction
            ];
            if (editTransaction($id, $newTransaction, $pdo)) {
                $success = "Transaction modifiée avec succès.";
                header("Refresh: 2; url=transactions.php");
            } else {
                $errors['general'] = "Erreur lors de la mise à jour.";
            }
        } else {
            $errors['category'] = "Catégorie invalide.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Transaction - WalletHub</title>
    <link rel="stylesheet" href="./css/edit_transactions.css?v=<?php echo time(); ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M3.17157 20.8284C4.34315 22 6.22876 22 10 22H14C17.7712 22 19.6569 22 20.8284 20.8284C22 19.6569 22 17.7712 22 14C22 12.8302 22 11.8419 21.965 11M20.8284 7.17157C19.6569 6 17.7712 6 14 6H10C6.22876 6 4.34315 6 3.17157 7.17157C2 8.34315 2 10.2288 2 14C2 15.1698 2 16.1581 2.03496 17' stroke='%231C274C' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M12 2C13.8856 2 14.8284 2 15.4142 2.58579C16 3.17157 16 4.11438 16 6M8.58579 2.58579C8 3.17157 8 4.11438 8 6' stroke='%231C274C' stroke-width='1.5' stroke-linecap='round'/%3E%3Cpath d='M12 17.3333C13.1046 17.3333 14 16.5871 14 15.6667C14 14.7462 13.1046 14 12 14C10.8954 14 10 13.2538 10 12.3333C10 11.4129 10.8954 10.6667 12 10.6667M12 17.3333C10.8954 17.3333 10 16.5871 10 15.6667M12 17.3333V18M12 10V10.6667M12 10.6667C13.1046 10.6667 14 11.4129 14 12.3333' stroke='%231C274C' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <nav class="nav-items">
                <a href="home.php" class="nav-item" title="Accueil"><i class="fa-solid fa-home"></i></a>
                <a href="dashboard.php" class="nav-item" title="Tableau de bord"><i class="fa-solid fa-gauge-simple"></i></a>
                <a href="transactions.php" class="nav-item active" title="Transactions"><i
                        class="fa-solid fa-exchange-alt"></i></a>
                <a href="profile.php" class="nav-item" title="Profil"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php" class="nav-item" title="Déconnexion"><i class="fa-solid fa-sign-out-alt"></i></a>
            </nav>
        </aside>

        <div class="main-content">
            <header>
                <h1>Modifier la Transaction</h1>
            </header>
            <?php if ($success): ?>
                <p class="success"><i class="fa-solid fa-check-circle"></i> <?= sanitize($success) ?></p>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['general']) ?></p>
            <?php endif; ?>
            
            <section class="edit-transaction">
                <h3><i class="fa-solid fa-pen"></i> Modifier une transaction</h3>
                <form action="edit_transaction.php?id=<?= $id ?>" method="post">
                    <div class="input-group">
                        <label for="type">Type:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-exchange-alt"></i>
                            <select name="type" id="type">
                                <option value="" disabled>Choisir un type</option>
                                <option value="revenu" <?= $transaction['type'] == 'revenu' ? 'selected' : '' ?>>Revenu</option>
                                <option value="depense" <?= $transaction['type'] == 'depense' ? 'selected' : '' ?>>Dépense</option>
                            </select>
                        </div>
                        <?php if (isset($errors['type'])): ?>
                            <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['type']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label for="montant">Montant:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-euro-sign"></i>
                            <input type="number" step="0.01" name="montant" id="montant" value="<?= sanitize($_POST['montant'] ?? $transaction['montant']) ?>">
                        </div>
                        <?php if (isset($errors['montant'])): ?>
                            <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['montant']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label for="category">Catégorie:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-tag"></i>
                            <select name="category" id="category">
                                <option value="" selected disabled>Choisir une catégorie</option>
                            </select>
                        </div>
                        <?php if (isset($errors['category'])): ?>
                            <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['category']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="input-group">
                        <label for="description">Description:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-align-left"></i>
                            <textarea name="description" id="description"><?= sanitize($_POST['description'] ?? $transaction['description']) ?></textarea>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="date_transaction">Date:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-calendar"></i>
                            <input type="date" name="date_transaction" id="date_transaction" value="<?= sanitize($_POST['date_transaction'] ?? $transaction['date_transaction']) ?>">
                        </div>
                        <?php if (isset($errors['date_transaction'])): ?>
                            <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['date_transaction']) ?></span>
                        <?php endif; ?>
                    </div>
                    <input type="submit" value="Modifier">
                </form>
            </section>
            <p><a href="transactions.php"><- Retour à la gestion des transactions</a></p>
        </div>
    </div>

    <script>
        const categories = {
            revenu: <?php echo json_encode($revenuCategories); ?>,
            depense: <?php echo json_encode($depenseCategories); ?>
        };

        const typeSelect = document.getElementById('type');
        const categorySelect = document.getElementById('category');
        const existingType = "<?= sanitize($transaction['type'], ENT_QUOTES) ?>";
        const existingCategory = "<?= sanitize($transaction['category_name'], ENT_QUOTES) ?>";

        function populateCategories() {
            categorySelect.innerHTML = '<option value="" selected disabled>Choisir une catégorie</option>';
            if (categories[typeSelect.value]) {
                categories[typeSelect.value].forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    if (cat === existingCategory || (cat === "<?= sanitize($_POST['category'] ?? '') ?>")) {
                        opt.selected = true;
                    }
                    categorySelect.appendChild(opt);
                });
            }
        }

        typeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            if (selectedType === 'revenu') {
                this.style.borderLeft = '4px solid var(--secondary-color)';
            } else if (selectedType === 'depense') {
                this.style.borderLeft = '4px solid var(--danger-color)';
            } else {
                this.style.borderLeft = 'none';
            }
            populateCategories();
        });

        typeSelect.value = existingType;
        populateCategories();
        categorySelect.value = existingCategory;
    </script>
</body>

</html>
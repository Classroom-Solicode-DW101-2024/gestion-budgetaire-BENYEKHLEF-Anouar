<?php
require 'config.php';
require 'functions/transactions.php';
require 'functions/categories.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$errors = [];
$success = '';

// Fetch categories 
$stmt = $pdo->prepare("SELECT nom, type FROM categories WHERE type = :type");
$stmt->execute(['type' => 'revenu']);
$revenuCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt->execute(['type' => 'depense']);
$depenseCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch years for filtering
$years = getTransactionYears($pdo, $userId);
$months = [
    1 => 'Janvier',
    2 => 'Février',
    3 => 'Mars',
    4 => 'Avril',
    5 => 'Mai',
    6 => 'Juin',
    7 => 'Juillet',
    8 => 'Août',
    9 => 'Septembre',
    10 => 'Octobre',
    11 => 'Novembre',
    12 => 'Décembre'
];

// Handle adding a new transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $type = sanitize($_POST['type'] ?? '');
    $montant = sanitize($_POST['montant'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date_transaction = sanitize($_POST['date_transaction'] ?? '');

    // Validation
    if (empty($type) || !in_array($type, ['revenu', 'depense'])) {
        $errors['type'] = "Type de transaction invalide ou manquant.";
    }
    if (empty($montant) || !is_numeric($montant) || $montant <= 0) {
        $errors['montant'] = "Montant invalide ou manquant.";
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
            $transaction = [
                'user_id'       => $userId,
                'category_id'   => $catData['id'],
                'montant'       => $montant,
                'description'   => $description,
                'date_transaction' => $date_transaction
            ];
            if (addTransaction($transaction, $pdo)) {
                $success = "Transaction ajoutée avec succès.";
            } else {
                $errors['general'] = "Impossible d'ajouter la transaction.";
            }
        } else {
            $errors['category'] = "Catégorie invalide.";
        }
    }

    if (empty($errors)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;

    } else {
        // If there are errors, keep the modal open
        $openModalOnLoad = true;
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = sanitize($_GET['id'] ?? '');
    if (deleteTransaction($id, $pdo)) {
        $success = "Transaction supprimée avec succès.";
    } else {
        $errors['general'] = "Impossible de supprimer.";
    }
}

// Retrieve transactions with filter
if (isset($_GET['year']) && $_GET['year'] !== '' && isset($_GET['month']) && $_GET['month'] !== '') {
    $year = intval($_GET['year']);
    $month = intval($_GET['month']);
    $transactionsList = listTransactionsByMonth($pdo, $userId, $year, $month);
    $filterText = "Transactions pour {$months[$month]} $year";
} else {
    $transactionsList = listTransactions($pdo, $userId);
    $filterText = "Toutes les transactions";
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - WalletHub</title>
    <link rel="stylesheet" href="./css/transactions.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/svg+xml"
        href='data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M16.5008 14.1502H16.5098M19 4.00098H6.2C5.0799 4.00098 4.51984 4.00098 4.09202 4.21896C3.71569 4.41071 3.40973 4.71667 3.21799 5.093C3 5.52082 3 6.08087 3 7.20098V16.801C3 17.9211 3 18.4811 3.21799 18.909C3.40973 19.2853 3.71569 19.5912 4.09202 19.783C4.51984 20.001 5.07989 20.001 6.2 20.001H17.8C18.9201 20.001 19.4802 20.001 19.908 19.783C20.2843 19.5912 20.5903 19.2853 20.782 18.909C21 18.4811 21 17.9211 21 16.801V11.201C21 10.0809 21 9.52082 20.782 9.093C20.5903 8.71667 20.2843 8.41071 19.908 8.21896C19.4802 8.00098 18.9201 8.00098 17.8 8.00098H7M16.9508 14.1502C16.9508 14.3987 16.7493 14.6002 16.5008 14.6002C16.2523 14.6002 16.0508 14.3987 16.0508 14.1502C16.0508 13.9017 16.2523 13.7002 16.5008 13.7002C16.7493 13.7002 16.9508 13.9017 16.9508 14.1502Z" stroke="%23000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>


<body>
    <div class="app-container">
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <nav class="nav-items">
                <a href="home.php" class="nav-item" title="Accueil"><i class="fa-solid fa-home"></i></a>
                <a href="dashboard.php" class="nav-item" title="Tableau de bord"><i
                        class="fa-solid fa-gauge-simple"></i></a>
                <a href="transactions.php" class="nav-item active" title="Transactions"><i
                        class="fa-solid fa-exchange-alt"></i></a>
                <a href="profile.php" class="nav-item" title="Profil"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php" class="nav-item" title="Déconnexion"><i class="fa-solid fa-sign-out-alt"></i></a>
            </nav>
        </aside>

        <div class="main-content" data-aos="fade-right" data-aos-duration="1600">
            <header>
                <h1>Gestion des Transactions</h1>
            </header>

            <?php if ($success): ?>
                <p class="success"><i class="fa-solid fa-check-circle"></i> <?= sanitize($success) ?></p>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?= sanitize($errors['general']) ?>
                </p>
            <?php endif; ?>

            <button id="open-modal" class="btn primary"><i class="fa-solid fa-plus-circle"></i>&nbsp; Ajouter une
                transaction</button>

            <div id="transaction-modal" class="modal">
                <div class="modal-content">
                    <span id="close-modal" class="modal-close">&times;</span>
                    <h3><i class="fa-solid fa-plus-circle"></i> Ajouter une transaction</h3>
                    <form action="transactions.php" method="post">
                        <input type="hidden" name="action" value="add">

                        <div class="input-group">
                            <label>Type:</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="type" value="revenu"
                                        <?= (isset($_POST['type']) && $_POST['type'] === 'revenu') ? 'checked' : '' ?>>
                                    <span class="radio-label revenu"><i class="fa-solid fa-arrow-up"></i> Revenu</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="type" value="depense"
                                        <?= (isset($_POST['type']) && $_POST['type'] === 'depense') ? 'checked' : '' ?>>
                                    <span class="radio-label depense"><i class="fa-solid fa-arrow-down"></i>
                                        Dépense</span>
                                </label>
                            </div>
                            <?php if (isset($errors['type'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                                    <?= sanitize($errors['type']) ?></span>
                            <?php endif; ?>
                        </div>



                        <div class="input-group">
                            <label for="montant">Montant:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-dollar-sign"></i>
                                <input type="number" step="0.01" name="montant" id="montant"
                                    value="<?= sanitize($_POST['montant'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['montant'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                                    <?= sanitize($errors['montant']) ?></span>
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
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                                    <?= sanitize($errors['category']) ?></span>
                            <?php endif; ?>
                        </div>


                        <div class="input-group">
                            <label for="description">Description:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-align-left"></i>
                                <textarea name="description"
                                    id="description"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="date_transaction">Date:</label>
                            <div class="input-icon">
                                <i class="fa-solid fa-calendar"></i>
                                <input type="date" name="date_transaction" id="date_transaction"
                                    value="<?= sanitize($_POST['date_transaction'] ?? '') ?>">
                            </div>
                            <?php if (isset($errors['date_transaction'])): ?>
                                <span class="error"><i class="fa-solid fa-circle-exclamation"></i>
                                    <?= sanitize($errors['date_transaction']) ?></span>
                            <?php endif; ?>
                        </div>

                        <input type="submit" value="Ajouter">
                    </form>
                </div>
            </div>

            <section class="filter-transactions">
                <h3><i class="fa-solid fa-filter"></i> Filtrer les transactions</h3>
                <form method="get" action="transactions.php">

                    <div class="input-group">
                        <label for="year">Année:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-calendar"></i>
                            <select name="year" id="year">
                                <option value="">Toutes les années</option>
                                <?php foreach ($years as $yearOption): ?>
                                    <option value="<?= $yearOption ?>"
                                        <?= (isset($_GET['year']) && $_GET['year'] == $yearOption) ? 'selected' : '' ?>>
                                        <?= $yearOption ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="month">Mois:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-calendar-day"></i>
                            <select name="month" id="month">
                                <option value="">Tous les mois</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>"
                                        <?= (isset($_GET['month']) && $_GET['month'] == $m) ? 'selected' : '' ?>>
                                        <?= $months[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <input type="submit" value="Filtrer">
                </form>
            </section>

            <section class="transaction-history">
                <h3><i class="fa-solid fa-list"></i> <?= $filterText ?></h3>
                <table border="0" cellpadding="5" cellspacing="0">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Catégorie</th>
                        <th>Montant</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                    <?php if (empty($transactionsList)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;"><i
                                    class="fa-regular fa-file"></i><br>
                                Aucune transaction pour cette
                                période.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactionsList as $trans): ?>
                            <tr>
                                <td><?= sanitize($trans['date_transaction']) ?></td>
                                <td class="transaction-type">
                                    <?php if ($trans['type'] === 'revenu'): ?>
                                        <i class="fa-solid fa-arrow-up"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-arrow-down"></i>
                                    <?php endif; ?>
                                    <?= sanitize($trans['type']) ?>
                                </td>
                                <td><i class="fa-solid fa-tags"></i>&nbsp; <?= sanitize($trans['category_name']) ?></td>
                                <td class="amount-<?= $trans['type'] ?>">
                                    <?= sanitize(number_format($trans['montant'], 2, ',', ' ')) ?> DH
                                </td>
                                <td><?= sanitize($trans['description'] ?? 'Sans description') ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_transaction.php?id=<?= $trans['id'] ?>" class="action-btn edit-btn"
                                            title="Modifier">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="transactions.php?action=delete&id=<?= $trans['id'] ?><?= isset($_GET['year']) ? '&year=' . urlencode($_GET['year']) : '' ?><?= isset($_GET['month']) ? '&month=' . urlencode($_GET['month']) : '' ?>"
                                            class="action-btn delete-btn" title="Supprimer"
                                            onclick="return confirm(' Voulez-vous supprimer cette transaction ?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </table>
            </section>
            <!-- <p><a href="dashboard.php">
                    <- Retour au tableau de bord</a>
            </p> -->
        </div>
    </div>

    <script>
        const categories = {
            revenu: <?php echo json_encode($revenuCategories); ?>,
            depense: <?php echo json_encode($depenseCategories); ?>
        };

        const typeRadios = document.querySelectorAll('input[name="type"]');
        const categorySelect = document.getElementById('category');

        function populateCategories(selectedType) {
            categorySelect.innerHTML = '<option value="" selected disabled>Choisir une catégorie</option>';

            if (selectedType && categories[selectedType]) {
                categories[selectedType].forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat;
                    option.textContent = cat;
                    <?php if (isset($_POST['category'])): ?>
                        if (cat === '<?= sanitize($_POST['category']) ?>') option.selected = true;
                    <?php endif; ?>
                    categorySelect.appendChild(option);
                });
            }
        }

        typeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedType = this.value;
                if (selectedType === 'revenu') {
                    this.style.borderLeft = '4px solid var(--secondary-color)';
                } else if (selectedType === 'depense') {
                    this.style.borderLeft = '4px solid var(--danger-color)';
                } else {
                    this.style.borderLeft = 'none';
                }
                populateCategories(this.value);
            });
        });
    </script>

    <script>
        const openBtn = document.getElementById('open-modal');
        const closeBtn = document.getElementById('close-modal');
        const modal = document.getElementById('transaction-modal');

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
    </script>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>

    <script>
        AOS.init();

        <?php if (!empty($errors)): ?>
            window.addEventListener('DOMContentLoaded', () => {
                document.getElementById('transaction-modal').classList.add('active');
            });
        <?php endif; ?>

        // Auto-submit form when year or month changes
        document.getElementById('year').addEventListener('change', function() {
            document.querySelector('form').submit();
        });
        document.getElementById('month').addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    </script>


</body>

</html>
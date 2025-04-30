<?php
require 'config.php';
require 'functions/dashboard.php';

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$success = '';
$errors = [];

// Set default year and month
$year = date('Y');
$month = date('m');

// Override year/month if provided in GET with validation
if (isset($_GET['year']) && is_numeric($_GET['year']) && isset($_GET['month']) && is_numeric($_GET['month'])) {
    $tempYear = intval($_GET['year']);
    $tempMonth = intval($_GET['month']);
    if ($tempYear >= 2000 && $tempYear <= date('Y') && $tempMonth >= 1 && $tempMonth <= 12) {
        $year = $tempYear;
        $month = $tempMonth;
    } else {
        $errors['filter'] = 'Année ou mois invalide.';
    }
}

// Load year list and month names
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

// Fetch user details and balance
$userDetails = detailsUser($pdo, $userId);
$currentSolde = soldUser($pdo, $userId);

// Monthly financial summary
$totalIncome = totalIncomes($pdo, $userId, $year, $month);
$totalExpense = totalExpenses($pdo, $userId, $year, $month);
$highestIncome = highestIncome($pdo, $userId, $year, $month);
$highestExpense = highestExpense($pdo, $userId, $year, $month);

// Fetch categories for income and expense breakdown
$stmt = $pdo->prepare("SELECT nom, type FROM categories WHERE type = :type");
$stmt->execute(['type' => 'revenu']);
$revenuCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt->execute(['type' => 'depense']);
$depenseCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Sum income per category
$incomeByCategory = [];
foreach ($revenuCategories as $category) {
    $incomeByCategory[$category] = totalIncomesByCategory($category, $pdo, $userId, $year, $month);
}

// Sum expense per category
$expenseByCategory = [];
foreach ($depenseCategories as $category) {
    $expenseByCategory[$category] = totalExpensesByCategory($category, $pdo, $userId, $year, $month);
}

// Format current month name in French using $months array
// if (isset($months[$month])) {
//     $monthName = $months[$month];
// } else {
//     $monthName = 'Inconnu';
// }

setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
$monthName = ucfirst(strftime('%B', mktime(0, 0, 0, $month, 10)));

// Net flow and style
$netFlow = $totalIncome - $totalExpense;
if ($netFlow >= 0) {
    $netFlowClass = 'income';
} else {
    $netFlowClass = 'expense';
}

// Budget usage percentage
$budgetPercentage = 0;
if ($totalIncome > 0) {
    $budgetPercentage = ($totalExpense / $totalIncome) * 100;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - WalletHub</title>
    <link rel="stylesheet" href="./css/dashboard.css?v=<?php echo time(); ?>">
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
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <nav class="nav-items">
                <a href="home.php" class="nav-item" title="Accueil"><i class="fa-solid fa-home"></i></a>
                <a href="dashboard.php" class="nav-item active" title="Tableau de bord"><i
                        class="fa-solid fa-gauge-simple"></i></a>
                <a href="transactions.php" class="nav-item" title="Transactions"><i
                        class="fa-solid fa-exchange-alt"></i></a>
                <a href="profile.php" class="nav-item" title="Profil"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php" class="nav-item" title="Déconnexion"><i class="fa-solid fa-sign-out-alt"></i></a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="main-content" data-aos="fade-right" data-aos-duration="1600">
            <header>
                <h1>Bienvenue, <?= htmlspecialchars($userDetails['nom'] ?? 'Utilisateur') ?></h1>
            </header>

            <?php if ($success): ?>
                <p class="success"><i class="fa-solid fa-check-circle"></i> <?php echo sanitize($success); ?></p>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo sanitize($errors['general']); ?>
                </p>
            <?php endif; ?>

            <?php if (isset($errors['filter'])): ?>
                <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo sanitize($errors['filter']); ?>
                </p>
            <?php endif; ?>


            <!-- Filter Section -->
            <section class="filter-dashboard dashboard-section">
                <h2><i class="fa-solid fa-filter"></i> Filtrer les données</h2>
                <form method="get" action="dashboard.php">
                    <div class="input-group">
                        <label for="year">Année:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-calendar"></i>
                            <select name="year" id="year">
                                <?php foreach ($years as $yearOption): ?>
                                    <option value="<?= $yearOption; ?>"
                                        <?php echo ($year == $yearOption) ? 'selected' : ''; ?>>
                                        <?php echo $yearOption; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="month">Mois:</label>
                        <div class="input-icon">
                            <i class="fa-solid fa-calendar-day"></i>
                            <select name="month" id="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($month == $m) ? 'selected' : ''; ?>>
                                        <?php echo $months[$m]; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <input type="submit" value="Appliquer">
                </form>
            </section>

            <!-- Current Balance Section -->
            <section class="dashboard-section">
                <h2><i class="fa-solid fa-wallet"></i> Solde actuel</h2>
                <div class="info-box" data-aos="zoom-in" data-aos-delay="100">
                    <span class="label">Solde actuel</span>
                    <span class="amount"><?= number_format($currentSolde, 2, ',', ' '); ?> DH</span>
                </div>
            </section>

            <!-- Monthly Summary Section -->
            <section class="dashboard-section">
                <h2><i class="fa-solid fa-chart-line"></i> Résumé - <?php echo $monthName; ?> <?php echo $year; ?></h2>
                <div class="summary-container">
                    <div class="info-box" data-aos="zoom-in" data-aos-delay="200">
                        <span class="label">Total revenus</span>
                        <span class="amount income"><?php echo number_format($totalIncome, 2, ',', ' '); ?> DH</span>
                    </div>
                    <div class="info-box" data-aos="zoom-in" data-aos-delay="300">
                        <span class="label">Total dépenses</span>
                        <span class="amount expense"><?php echo number_format($totalExpense, 2, ',', ' '); ?> DH</span>
                    </div>
                    <div class="info-box" data-aos="zoom-in" data-aos-delay="400">
                        <span class="label">Solde du mois</span>
                        <span
                            class="amount <?php echo $netFlowClass; ?>"><?php echo number_format($netFlow, 2, ',', ' '); ?>
                            DH</span>
                    </div>
                </div>
            </section>

            <!-- Budget Usage Section -->
            <section class="dashboard-section">
                <h2><i class="fa-solid fa-chart-pie"></i> Utilisation du budget</h2>
                <div class="budget-progress" data-aos="fade-up" data-aos-delay="450">
                    <div class="progress-info">
                        <span><?php echo number_format($budgetPercentage, 1, ',', ' '); ?>% du budget utilisé</span>
                    </div>
                </div>
            </section>

            <!-- Notable Transactions Section -->
            <section class="dashboard-section">
                <h2><i class="fa-solid fa-star"></i> Transactions marquantes</h2>
                <div class="summary-container">
                    <div class="info-box" data-aos="zoom-in" data-aos-delay="500">
                        <span class="label">Plus grand revenu</span>
                        <span class="amount income"><?php echo number_format($highestIncome, 2, ',', ' '); ?> DH</span>
                    </div>
                    <div class="info-box" data-aos="zoom-in" data-aos-delay="600">
                        <span class="label">Plus grosse dépense</span>
                        <span class="amount expense"><?php echo number_format($highestExpense, 2, ',', ' '); ?> DH</span>
                    </div>
                </div>
            </section>

            <!-- Income by Category Section -->
            <section class="dashboard-section">
                <h2><i class="fa-solid fa-tags"></i> Revenus par catégorie</h2>
                <div class="category-container">
                    <?php if (empty($incomeByCategory) || array_sum($incomeByCategory) == 0): ?>
                        <div class="info-box empty">
                            <i class="fa-regular fa-folder-open"></i>
                            <span>Aucun revenu pour cette période</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($incomeByCategory as $category => $amount): ?>
                            <?php if ($amount > 0): ?>
                                <div class="info-box category-box" data-aos="fade-up" data-aos-delay="700">
                                    <span class="label"><i class="fa-solid fa-tag"></i> <?php echo sanitize($category); ?></span>
                                    <span class="amount income"><?php echo number_format($amount, 2, ',', ' '); ?> DH</span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Expenses by Category Section -->
            <section class="dashboard-section">
                <h2><i class="fa-solid fa-tags"></i> Dépenses par catégorie</h2>
                <div class="category-container">
                    <?php if (empty($expenseByCategory) || array_sum($expenseByCategory) == 0): ?>
                        <div class="info-box empty">
                            <i class="fa-regular fa-folder-open"></i>
                            <span>Aucune dépense pour cette période</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($expenseByCategory as $category => $amount): ?>
                            <?php if ($amount > 0): ?>
                                <div class="info-box category-box" data-aos="fade-up" data-aos-delay="750">
                                    <span class="label"><i class="fa-solid fa-tag"></i> <?php echo sanitize($category); ?></span>
                                    <span class="amount expense"><?php echo number_format($amount, 2, ',', ' '); ?> DH</span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <div class="action-buttons">
                <a href="transactions.php" class="btn primary"><i class="fa-solid fa-list"></i> Gérer les
                    transactions</a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();

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
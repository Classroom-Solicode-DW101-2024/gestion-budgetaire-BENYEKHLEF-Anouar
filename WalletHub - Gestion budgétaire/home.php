<?php
require 'config.php';
require 'functions/dashboard.php';

// Redirect if user not logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userDetails = detailsUser($pdo, $userId);
$currentSolde = soldUser($pdo, $userId);

// Get current month and year
$currentYear = date('Y');
$currentMonth = date('m');

// Monthly financial summary for current month
$totalIncome = totalIncomes($pdo, $userId, $currentYear, $currentMonth);
$totalExpense = totalExpenses($pdo, $userId, $currentYear, $currentMonth);
$netFlow = $totalIncome - $totalExpense;

// Get recent transactions (last 5)
$stmt = $pdo->prepare("
    SELECT t.id, t.montant, t.description, t.date_transaction, c.nom as category_name, c.type 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = :user_id 
    ORDER BY t.date_transaction DESC 
    LIMIT 5
");
$stmt->execute(['user_id' => $userId]);
$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent transactions (last 5)
// $stmt = $pdo->prepare("
//     SELECT t.id, t.montant, t.description, t.date_transaction, c.nom as category_name, c.type 
//     FROM transactions t 
//     JOIN categories c ON t.category_id = c.id 
//     WHERE t.user_id = :user_id 
//     ORDER BY t.date_transaction DESC 
//     LIMIT 5
// ");

// $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
// $stmt->execute();
// $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Format month name
setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
$monthName = ucfirst(strftime('%B', mktime(0, 0, 0, $currentMonth, 10)));

// Determine style class for net flow
$netFlowClass = ($netFlow >= 0) ? 'income' : 'expense';

// $netFlow = $totalIncome - $totalExpense;
// if ($netFlow >= 0) {
//     $netFlowClass = 'income';
// } else {
//     $netFlowClass = 'expense';
// }

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - WalletHub</title>
    <link rel="stylesheet" href="./css/home.css?v=<?php echo time(); ?>">
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
                <a href="home.php" class="nav-item active" title="Accueil"><i class="fa-solid fa-home"></i></a>
                <a href="dashboard.php" class="nav-item" title="Tableau de bord"><i
                        class="fa-solid fa-gauge-simple"></i></a>
                <a href="transactions.php" class="nav-item" title="Transactions"><i
                        class="fa-solid fa-exchange-alt"></i></a>
                <a href="profile.php" class="nav-item" title="Profil"><i class="fa-solid fa-user"></i></a>
                <a href="logout.php" class="nav-item" title="Déconnexion"><i class="fa-solid fa-sign-out-alt"></i></a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="main-content" data-aos="fade-right" data-aos-duration="1500">
            <header>
                <div class="welcome-message">
                    <h1>Bienvenue, <?= htmlspecialchars($userDetails['nom'] ?? 'Utilisateur') ?></h1>
                    <p class="date-display"><?= date('d ') . $monthName . date(' Y') ?></p>
                </div>
            </header>

            <!-- Hero Section -->
            <section class="hero-section" data-aos="fade-up" data-aos-delay="100">
                <div class="hero-content">
                    <div class="hero-text">
                        <h2>Gérez vos finances efficacement</h2>
                        <p>Suivez vos revenus et dépenses, analysez vos habitudes financières, et atteignez vos
                            objectifs.</p>
                    </div>
                    <div class="hero-balance">
                        <div class="balance-card">
                            <span class="label">Solde actuel</span>
                            <span class="amount"><?= number_format($currentSolde, 2, ',', ' ') ?> DH</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Stats Section -->
            <section class="quick-stats-section dashboard-section" data-aos="fade-up" data-aos-delay="90">
                <h2><i class="fa-solid fa-chart-simple"></i> Aperçu du mois de <?= $monthName ?></h2>
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon income-icon">
                            <i class="fa-solid fa-arrow-up"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Revenus</span>
                            <span class="stat-value income"><?= number_format($totalIncome, 2, ',', ' ') ?> DH</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon expense-icon">
                            <i class="fa-solid fa-arrow-down"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Dépenses</span>
                            <span class="stat-value expense"><?= number_format($totalExpense, 2, ',', ' ') ?> DH</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon <?= $netFlowClass ?>-icon">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                        <div class="stat-details">
                            <span class="stat-label">Solde du mois</span>
                            <span class="stat-value <?= $netFlowClass ?>"><?= number_format($netFlow, 2, ',', ' ') ?>
                                DH</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Transactions Section -->
            <section class="recent-transactions dashboard-section" data-aos="fade-up" data-aos-delay="80">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Transactions récentes</h2>
                <?php if (empty($recentTransactions)): ?>
                <div class="empty-transactions">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>Aucune transaction récente</p>
                </div>
                <?php else: ?>
                <div class="transactions-list">
                    <?php foreach ($recentTransactions as $trans): ?>
                    <div class="transaction-item">
                        <div class="transaction-icon <?= $trans['type'] === 'revenu' ? 'income' : 'expense' ?>">
                            <i
                                class="fa-solid <?= $trans['type'] === 'revenu' ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i>
                        </div>

                        <!-- <div class="transaction-icon 
                             <?php if ($trans['type'] === 'revenu') echo 'income'; else echo 'expense'; ?>">
                            <i class="fa-solid 
                             <?php if ($trans['type'] === 'revenu') echo 'fa-arrow-up'; else echo 'fa-arrow-down'; ?>">
                            </i>
                        </div> -->

                        <div class="transaction-details">
                            <div class="transaction-top">
                                <span
                                    class="transaction-category"><?= htmlspecialchars($trans['category_name']) ?></span>
                                <span
                                    class="transaction-amount <?= $trans['type'] === 'revenu' ? 'income' : 'expense' ?>">
                                    <?= number_format($trans['montant'], 2, ',', ' ') ?> DH
                                </span>
                            </div>
                            <div class="transaction-bottom">
                                <span
                                    class="transaction-description"><?= htmlspecialchars($trans['description'] ?? 'Sans description') ?></span>
                                <span
                                    class="transaction-date"><?= htmlspecialchars($trans['date_transaction']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="view-all">
                    <a href="transactions.php" class="btn secondary"><i class="fa-solid fa-list"></i> Voir toutes les
                        transactions</a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Quick Actions Section -->
            <section class="quick-actions dashboard-section" data-aos="fade-up" data-aos-delay="70">
                <h2><i class="fa-solid fa-bolt"></i> Actions rapides</h2>
                <div class="actions-grid">
                    <a href="transactions.php" class="action-card">
                        <div class="action-icon primary">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <span>Ajouter une transaction</span>
                    </a>
                    <a href="dashboard.php" class="action-card">
                        <div class="action-icon secondary">
                            <i class="fa-solid fa-chart-pie"></i>
                        </div>
                        <span>Voir les statistiques</span>
                    </a>
                    <a href="profile.php" class="action-card">
                        <div class="action-icon tertiary">
                            <i class="fa-solid fa-user-gear"></i>
                        </div>
                        <span>Gérer votre profil</span>
                    </a>
                </div>
            </section>
        </div>
    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
    AOS.init();
    </script>
</body>

</html>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser les transactions si elles n'existent pas
if (!isset($_SESSION['transactions'])) {
    $_SESSION['transactions'] = [];
}

// Initialisation de la langue
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fr';
}

// Initialisation de la devise par défaut
if (!isset($_SESSION['default_currency'])) {
    $_SESSION['default_currency'] = 'EUR';
}

// Mise à jour de la devise par défaut si demandé
if (isset($_POST['currency'])) {
    $_SESSION['default_currency'] = $_POST['currency'];
}

// Changement de langue si demandé
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Tableau des traductions
$translations = [
    'fr' => [
        'dashboard_title' => ' Gestion des Dépenses',
        'search_placeholder' => 'Rechercher...',
        'all' => 'Tout',
        'this_month' => 'Ce mois',
        'new_transaction' => 'Nouvelle Transaction',
        'total_balance' => 'Solde Total',
        'last_update' => 'Mise à jour',
        'income' => 'Revenus',
        'total_income' => 'Total des revenus',
        'expenses' => 'Dépenses',
        'total_expenses' => 'Total des dépenses',
        'expense_distribution' => 'Répartition des Dépenses',
        'monthly_evolution' => 'Évolution Mensuelle',
        'recent_transactions' => 'Transactions Récentes',
        'date' => 'Date',
        'type' => 'Type',
        'category' => 'Catégorie',
        'description' => 'Description',
        'amount' => 'Montant',
        'payment_method' => 'Moyen de paiement',
        'tags' => 'Tags',
        'actions' => 'Actions',
        'categories' => [
            'food' => 'Alimentation',
            'transport' => 'Transport',
            'housing' => 'Logement',
            'leisure' => 'Loisirs',
            'health' => 'Santé',
            'shopping' => 'Shopping',
            'bills' => 'Factures',
            'education' => 'Éducation',
            'investment' => 'Investissement',
            'others' => 'Autres'
        ],
        'payment_methods' => [
            'credit_card' => 'Carte bancaire',
            'cash' => 'Espèces',
            'transfer' => 'Virement',
            'check' => 'Chèque',
            'direct_debit' => 'Prélèvement'
        ]
    ],
    'en' => [
        'dashboard_title' => 'Dashboard - Expense Manager',
        'search_placeholder' => 'Search...',
        'all' => 'All',
        'this_month' => 'This Month',
        'new_transaction' => 'New Transaction',
        'total_balance' => 'Total Balance',
        'last_update' => 'Last update',
        'income' => 'Income',
        'total_income' => 'Total income',
        'expenses' => 'Expenses',
        'total_expenses' => 'Total expenses',
        'expense_distribution' => 'Expense Distribution',
        'monthly_evolution' => 'Monthly Evolution',
        'recent_transactions' => 'Recent Transactions',
        'date' => 'Date',
        'type' => 'Type',
        'category' => 'Category',
        'description' => 'Description',
        'amount' => 'Amount',
        'payment_method' => 'Payment Method',
        'tags' => 'Tags',
        'actions' => 'Actions',
        'categories' => [
            'food' => 'Food',
            'transport' => 'Transport',
            'housing' => 'Housing',
            'leisure' => 'Leisure',
            'health' => 'Health',
            'shopping' => 'Shopping',
            'bills' => 'Bills',
            'education' => 'Education',
            'investment' => 'Investment',
            'others' => 'Others'
        ],
        'payment_methods' => [
            'credit_card' => 'Credit Card',
            'cash' => 'Cash',
            'transfer' => 'Transfer',
            'check' => 'Check',
            'direct_debit' => 'Direct Debit'
        ]
    ]
];

// Fonction helper pour la traduction
function t($key) {
    global $translations;
    $lang = $_SESSION['lang'];
    $keys = explode('.', $key);
    $value = $translations[$lang];
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $key;
        }
    }
    return $value;
}

// Liste des devises disponibles avec leurs symboles
$currencies = [
    'EUR' => '€',
    'USD' => '$',
    'GBP' => '£',
    'JPY' => '¥',
    'CHF' => 'CHF',
    'CAD' => 'C$',
    'AUD' => 'A$',
    'CNY' => '¥',
    'TRY' => '₺'
];

// Devise par défaut
$defaultCurrency = $_SESSION['default_currency'];

// Traitement de l'ajout d'une transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $transaction = [
            'id' => uniqid(),
            'date' => $_POST['date'],
            'type' => $_POST['type'],
            'category' => $_POST['category'],
            'description' => $_POST['description'],
            'amount' => floatval($_POST['amount']),
            'currency' => $_POST['currency'],
            'payment_method' => $_POST['payment_method'],
            'tags' => isset($_POST['tags']) ? explode(',', $_POST['tags']) : [],
            'created_at' => time()
        ];
        $_SESSION['transactions'][] = $transaction;
    } elseif ($_POST['action'] === 'delete' && isset($_POST['transaction_id'])) {
        $_SESSION['transactions'] = array_filter($_SESSION['transactions'], function($t) {
            return $t['id'] !== $_POST['transaction_id'];
        });
    }
    header('Location: /dashboard');
    exit;
}

// Filtres
$currentMonth = date('Y-m');
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Calculer les totaux
$summary = [
    'total_income' => 0,
    'total_expenses' => 0,
    'balance' => 0,
    'categories' => [],
    'monthly' => [],
    'payment_methods' => []
];

$filteredTransactions = $_SESSION['transactions'];

// Appliquer les filtres
if ($filter === 'month') {
    $currentYear = date('Y');
    $currentMonth = date('m');
    $filteredTransactions = array_filter($filteredTransactions, function($t) use ($currentYear, $currentMonth) {
        $transactionYear = date('Y', strtotime($t['date']));
        $transactionMonth = date('m', strtotime($t['date']));
        return $transactionYear == $currentYear && $transactionMonth == $currentMonth;
    });
}

if ($category) {
    $filteredTransactions = array_filter($filteredTransactions, function($t) use ($category) {
        return $t['category'] === $category;
    });
}

if ($search) {
    $filteredTransactions = array_filter($filteredTransactions, function($t) use ($search) {
        return stripos($t['description'], $search) !== false || 
               stripos($t['category'], $search) !== false;
    });
}

foreach ($filteredTransactions as $t) {
    $month = substr($t['date'], 0, 7);
    
    if ($t['type'] === 'income') {
        $summary['total_income'] += $t['amount'];
    } else {
        $summary['total_expenses'] += $t['amount'];
        if (!isset($summary['categories'][$t['category']])) {
            $summary['categories'][$t['category']] = 0;
        }
        $summary['categories'][$t['category']] += $t['amount'];
    }

    // Statistiques mensuelles
    if (!isset($summary['monthly'][$month])) {
        $summary['monthly'][$month] = ['income' => 0, 'expenses' => 0];
    }
    $summary['monthly'][$month][$t['type']] += $t['amount'];

    // Statistiques par moyen de paiement
    if (!isset($summary['payment_methods'][$t['payment_method']])) {
        $summary['payment_methods'][$t['payment_method']] = 0;
    }
    if ($t['type'] === 'expense') {
        $summary['payment_methods'][$t['payment_method']] += $t['amount'];
    }
}

$summary['balance'] = $summary['total_income'] - $summary['total_expenses'];

// Trier les transactions par date
usort($filteredTransactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Fonction pour formater le montant avec la devise
function formatAmount($amount, $currency) {
    global $currencies;
    $symbol = $currencies[$currency] ?? $currency;
    return number_format($amount, 2) . ' ' . $symbol;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4158D0">
    <meta name="description" content="Application de gestion des dépenses personnelles">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Dépenses">
    
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/svg+xml" href="/icon.html">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    
    <title><?php echo t('dashboard_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(45deg, #4158D0, #C850C0);
            --border-radius: 15px;
            --box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        :root[data-theme="light"] {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --hover-bg: #f8f9fa;
        }

        :root[data-theme="dark"] {
            --bg-color: #121212;
            --text-color: #ffffff;
            --card-bg: #1e1e1e;
            --border-color: #333333;
            --shadow-color: rgba(0, 0, 0, 0.5);
            --hover-bg: #2d2d2d;
            --muted-text: #cccccc;
            --table-hover: #2d2d2d;
            --table-bg: #1e1e1e;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body[data-theme="dark"] {
            background-color: var(--bg-color);
        }

        .navbar {
            background: var(--primary-gradient) !important;
            padding: 1rem 0;
            box-shadow: var(--box-shadow);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .search-form input {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: 0 8px 16px var(--shadow-color);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.15);
        }

        .stat-card {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
        }

        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 1rem 0;
        }

        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }

        .filter-btn {
            border-radius: 20px;
            padding: 0.5rem 1.2rem;
            transition: all 0.3s ease;
            margin: 0 0.3rem;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .table tr {
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: var(--border-radius);
            transition: all 0.2s ease;
        }

        .table tr:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .table td, .table th {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .table th {
            font-weight: 600;
            color: var(--text-color);
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 15px;
            font-weight: 500;
        }

        .tag {
            background: #e9ecef;
            color: #495057;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            margin: 0.2rem;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .tag:hover {
            background: #dee2e6;
            transform: translateY(-1px);
        }

        .chart-container {
            padding: 1.5rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1.5rem;
        }

        .modal-content {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-radius: 20px;
            border: none;
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1.5rem;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .form-control, .form-select {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-radius: 10px;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--card-bg);
            color: var(--text-color);
            border-color: #4158D0;
            box-shadow: 0 0 0 0.25rem rgba(65, 88, 208, 0.1);
        }

        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #f8f9fa;
            border: 1px solid var(--border-color);
        }

        .btn-action {
            width: 35px;
            height: 35px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .theme-switch {
            position: relative;
            width: 60px;
            height: 30px;
            margin: 0 15px;
            display: inline-block;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .theme-switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            transition: .4s;
            border-radius: 30px;
            backdrop-filter: blur(4px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .theme-switch-slider:before {
            position: absolute;
            content: "🌞";
            height: 24px;
            width: 24px;
            left: 2px;
            bottom: 1px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        input:checked + .theme-switch-slider {
            background-color: rgba(0, 0, 0, 0.4);
        }

        input:checked + .theme-switch-slider:before {
            transform: translateX(30px);
            content: "🌙";
            background-color: #2b3035;
            color: white;
        }

        .theme-switch-slider:hover {
            border-color: rgba(255, 255, 255, 0.5);
        }

        .theme-switch-slider:active:before {
            transform: scale(0.9);
        }

        input:checked + .theme-switch-slider:active:before {
            transform: translateX(30px) scale(0.9);
        }

        @media (max-width: 768px) {
            .stat-card h2 {
                font-size: 1.8rem;
            }

            .filter-btn {
                margin-bottom: 0.5rem;
            }

            .table {
                font-size: 0.9rem;
            }
        }

        [data-theme="dark"] .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .table {
            color: var(--text-color);
        }

        [data-theme="dark"] .table tr {
            background-color: var(--table-bg);
        }

        [data-theme="dark"] .table tr:hover {
            background-color: var(--hover-bg);
        }

        [data-theme="dark"] .table td {
            color: var(--text-color);
        }

        [data-theme="dark"] .table th {
            color: var(--muted-text);
        }

        [data-theme="dark"] .text-muted {
            color: var(--muted-text) !important;
        }

        [data-theme="dark"] .badge {
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .tag {
            background-color: #333333;
            color: var(--text-color);
            border: 1px solid #444444;
        }

        [data-theme="dark"] .tag:hover {
            background-color: #444444;
        }

        [data-theme="dark"] .btn-outline-primary {
            color: #90caf9;
            border-color: #90caf9;
        }

        [data-theme="dark"] .btn-outline-primary:hover {
            background-color: #90caf9;
            color: #121212;
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: #2d2d2d;
            border-color: #444444;
            color: var(--text-color);
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: #333333;
            border-color: #90caf9;
            box-shadow: 0 0 0 0.25rem rgba(144, 202, 249, 0.25);
        }

        [data-theme="dark"] .form-control::placeholder {
            color: #888888;
        }

        [data-theme="dark"] .chart-container {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        [data-theme="dark"] .modal-content {
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .modal-body {
            background-color: var(--card-bg);
            color: var(--text-color);
        }

        [data-theme="dark"] .input-group-text {
            background-color: #333333;
            border-color: #444444;
            color: var(--text-color);
        }

        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-theme="dark"] .btn-light {
            background-color: #333333;
            border-color: #444444;
            color: var(--text-color);
        }

        [data-theme="dark"] .btn-light:hover {
            background-color: #444444;
            border-color: #555555;
            color: var(--text-color);
        }

        [data-theme="dark"] .form-text {
            color: var(--muted-text);
        }

        .language-switch {
            margin: 0 15px;
            display: flex;
            align-items: center;
        }

        .language-switch a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .language-switch a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .language-switch a:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .language-switch .separator {
            color: rgba(255, 255, 255, 0.5);
            margin: 0 5px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">
                <i class="bi bi-wallet2"></i> <?php echo t('dashboard_title'); ?>
            </a>
            <div class="d-flex align-items-center">
                <!-- Language Switch -->
                <div class="language-switch">
                    <a href="?lang=fr" class="<?php echo $_SESSION['lang'] === 'fr' ? 'active' : ''; ?>">FR</a>
                    <span class="separator">|</span>
                    <a href="?lang=en" class="<?php echo $_SESSION['lang'] === 'en' ? 'active' : ''; ?>">EN</a>
                </div>
                <!-- Theme Switch -->
                <div class="theme-switch-wrapper me-3">
                    <label class="theme-switch" title="<?php echo t('change_theme'); ?>">
                        <input type="checkbox" id="theme-toggle">
                        <span class="theme-switch-slider"></span>
                    </label>
                </div>
                <form class="d-flex">
                    <input type="search" name="search" class="form-control" placeholder="<?php echo t('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-light ms-2" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex flex-wrap gap-2">
                            <a href="?filter=all" class="btn filter-btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-grid-3x3-gap-fill me-2"></i><?php echo t('all'); ?>
                            </a>
                            <a href="?filter=month" class="btn filter-btn <?php echo $filter === 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-calendar-event me-2"></i><?php echo t('this_month'); ?>
                            </a>
                            <?php foreach (array_keys($summary['categories']) as $cat): ?>
                            <a href="?category=<?php echo urlencode($cat); ?>" 
                               class="btn filter-btn <?php echo $category === $cat ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-tag me-2"></i><?php echo htmlspecialchars($cat); ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                            <i class="bi bi-plus-lg me-2"></i><?php echo t('new_transaction'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card h-100">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-white p-3 me-3">
                            <i class="bi bi-wallet text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0"><?php echo t('total_balance'); ?></h5>
                            <small class="text-white-50"><?php echo t('last_update'); ?>: <?php echo date('d/m/Y H:i'); ?></small>
                        </div>
                    </div>
                    <h2 class="mb-0"><?php echo formatAmount($summary['balance'], $defaultCurrency); ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                                <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0"><?php echo t('income'); ?></h5>
                                <small class="text-muted"><?php echo t('total_income'); ?></small>
                            </div>
                        </div>
                        <h2 class="text-success mb-0"><?php echo formatAmount($summary['total_income'], $defaultCurrency); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                                <i class="bi bi-graph-down-arrow text-danger fs-4"></i>
                            </div>
                            <div>
                                <h5 class="card-title mb-0"><?php echo t('expenses'); ?></h5>
                                <small class="text-muted"><?php echo t('total_expenses'); ?></small>
                            </div>
                        </div>
                        <h2 class="text-danger mb-0"><?php echo formatAmount($summary['total_expenses'], $defaultCurrency); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="chart-title">
                        <i class="bi bi-pie-chart-fill me-2"></i>
                        <?php echo t('expense_distribution'); ?>
                    </h5>
                    <canvas id="expensesChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5 class="chart-title">
                        <i class="bi bi-graph-up me-2"></i>
                        <?php echo t('monthly_evolution'); ?>
                    </h5>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Liste des transactions -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title d-flex align-items-center mb-4">
                    <i class="bi bi-list-ul me-2"></i>
                    <?php echo t('recent_transactions'); ?>
                </h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th><?php echo t('date'); ?></th>
                                <th><?php echo t('type'); ?></th>
                                <th><?php echo t('category'); ?></th>
                                <th><?php echo t('description'); ?></th>
                                <th><?php echo t('amount'); ?></th>
                                <th><?php echo t('payment_method'); ?></th>
                                <th><?php echo t('tags'); ?></th>
                                <th><?php echo t('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filteredTransactions as $t): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-calendar-date me-2"></i>
                                        <?php echo date('d/m/Y', strtotime($t['date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $t['type'] === 'income' ? 'bg-success' : 'bg-danger'; ?>">
                                        <i class="bi <?php echo $t['type'] === 'income' ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle'; ?> me-1"></i>
                                        <?php echo $t['type'] === 'income' ? t('income') : t('expenses'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-tag me-2"></i>
                                        <?php echo htmlspecialchars($t['category']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($t['description']); ?></td>
                                <td class="fw-bold <?php echo $t['type'] === 'income' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo formatAmount($t['amount'], $t['currency'] ?? $defaultCurrency); ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-credit-card me-2"></i>
                                        <?php echo htmlspecialchars($t['payment_method'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($t['tags'])): ?>
                                        <?php foreach ($t['tags'] as $tag): ?>
                                            <span class="tag">
                                                <i class="bi bi-hash me-1"></i>
                                                <?php echo htmlspecialchars($tag); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-action">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Transaction -->
    <div class="modal fade" id="addTransactionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 bg-gradient-primary text-white" style="background: linear-gradient(45deg, #4158D0, #C850C0);">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle-fill me-2"></i>
                        <?php echo t('new_transaction'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="add">
                        
                        <!-- Type de transaction avec icônes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-arrow-left-right me-2"></i><?php echo t('type'); ?>
                            </label>
                            <div class="d-flex gap-2">
                                <div class="form-check form-check-inline flex-fill">
                                    <input class="form-check-input" type="radio" name="type" id="typeIncome" value="income" required>
                                    <label class="form-check-label w-100 py-2 px-3 rounded-3 text-center border" for="typeIncome" style="cursor: pointer;">
                                        <i class="bi bi-graph-up-arrow text-success"></i>
                                        <?php echo t('income'); ?>
                                    </label>
                                </div>
                                <div class="form-check form-check-inline flex-fill">
                                    <input class="form-check-input" type="radio" name="type" id="typeExpense" value="expense" required>
                                    <label class="form-check-label w-100 py-2 px-3 rounded-3 text-center border" for="typeExpense" style="cursor: pointer;">
                                        <i class="bi bi-graph-down-arrow text-danger"></i>
                                        <?php echo t('expenses'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Montant et devise -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-currency-exchange me-2"></i><?php echo t('amount'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-cash"></i>
                                </span>
                                <input type="number" name="amount" class="form-control form-control-lg" step="0.01" required placeholder="0.00">
                                <select name="currency" class="form-select form-select-lg" style="max-width: 120px;">
                                    <?php foreach ($currencies as $code => $symbol): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $code . ' (' . $symbol . ')'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Catégorie avec icônes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-tag me-2"></i><?php echo t('category'); ?>
                            </label>
                            <select name="category" class="form-select form-select-lg" required>
                                <option value="food">🍽️ <?php echo t('categories.food'); ?></option>
                                <option value="transport">🚗 <?php echo t('categories.transport'); ?></option>
                                <option value="housing">🏠 <?php echo t('categories.housing'); ?></option>
                                <option value="leisure">🎮 <?php echo t('categories.leisure'); ?></option>
                                <option value="health">⚕️ <?php echo t('categories.health'); ?></option>
                                <option value="shopping">🛍️ <?php echo t('categories.shopping'); ?></option>
                                <option value="bills">📄 <?php echo t('categories.bills'); ?></option>
                                <option value="education">📚 <?php echo t('categories.education'); ?></option>
                                <option value="investment">📈 <?php echo t('categories.investment'); ?></option>
                                <option value="others">📌 <?php echo t('categories.others'); ?></option>
                            </select>
                        </div>

                        <!-- Moyen de paiement avec icônes -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-credit-card me-2"></i><?php echo t('payment_method'); ?>
                            </label>
                            <select name="payment_method" class="form-select form-select-lg" required>
                                <option value="credit_card">💳 <?php echo t('payment_methods.credit_card'); ?></option>
                                <option value="cash">💵 <?php echo t('payment_methods.cash'); ?></option>
                                <option value="transfer">🏦 <?php echo t('payment_methods.transfer'); ?></option>
                                <option value="check">📑 <?php echo t('payment_methods.check'); ?></option>
                                <option value="direct_debit">⚡ <?php echo t('payment_methods.direct_debit'); ?></option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-pencil me-2"></i><?php echo t('description'); ?>
                            </label>
                            <textarea name="description" class="form-control" rows="2" required placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                        </div>

                        <!-- Date -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-calendar me-2"></i><?php echo t('date'); ?>
                            </label>
                            <input type="date" name="date" class="form-control form-control-lg" required>
                        </div>

                        <!-- Tags -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="bi bi-tags me-2"></i><?php echo t('tags'); ?>
                            </label>
                            <input type="text" name="tags" class="form-control form-control-lg" placeholder="urgent, mensuel, personnel">
                            <div class="form-text"><?php echo t('tags_instructions'); ?></div>
                        </div>

                        <button type="submit" class="btn btn-gradient btn-lg w-100">
                            <i class="bi bi-plus-lg me-2"></i><?php echo t('add_transaction'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Switcher
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('theme-toggle');
            
            // Charger le thème sauvegardé
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            document.body.setAttribute('data-theme', savedTheme);
            themeToggle.checked = savedTheme === 'dark';

            // Gérer le changement de thème
            themeToggle.addEventListener('change', function() {
                const theme = this.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
                document.body.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                
                // Mettre à jour les graphiques avec des couleurs adaptées au thème
                updateChartsTheme(theme);
            });
        });

        // Fonction pour mettre à jour les thèmes des graphiques
        function updateChartsTheme(theme) {
            const textColor = theme === 'dark' ? '#e9ecef' : '#212529';
            const gridColor = theme === 'dark' ? '#495057' : '#dee2e6';
            
            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = gridColor;
            
            // Mettre à jour le graphique des dépenses
            if (window.expensesChart) {
                window.expensesChart.options.plugins.legend.labels.color = textColor;
                window.expensesChart.update();
            }
            
            // Mettre à jour le graphique mensuel
            if (window.monthlyChart) {
                window.monthlyChart.options.scales.x.grid.color = gridColor;
                window.monthlyChart.options.scales.y.grid.color = gridColor;
                window.monthlyChart.options.scales.x.ticks.color = textColor;
                window.monthlyChart.options.scales.y.ticks.color = textColor;
                window.monthlyChart.update();
            }
        }

        // Graphique des dépenses
        const ctx = document.getElementById('expensesChart').getContext('2d');
        const categories = <?php echo json_encode(array_keys($summary['categories'])); ?>;
        const amounts = <?php echo json_encode(array_values($summary['categories'])); ?>;
        
        window.expensesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: amounts,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#36A2EB'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Graphique mensuel
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($summary['monthly']); ?>;
        const months = Object.keys(monthlyData);
        const incomes = months.map(m => monthlyData[m].income);
        const expenses = months.map(m => monthlyData[m].expenses);

        window.monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: '<?php echo t('income'); ?>',
                        data: incomes,
                        borderColor: '#28a745',
                        fill: false
                    },
                    {
                        label: '<?php echo t('expenses'); ?>',
                        data: expenses,
                        borderColor: '#dc3545',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Initialiser le thème des graphiques
        updateChartsTheme(localStorage.getItem('theme') || 'light');
    </script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('Service Worker enregistré avec succès:', registration.scope);
                    })
                    .catch(error => {
                        console.log('Erreur lors de l\'enregistrement du Service Worker:', error);
                    });
            });
        }
    </script>
</body>
</html> 
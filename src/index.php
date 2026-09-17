<?php
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
]);

session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Profitability
$stmt = $pdo->query("SELECT SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as total_income, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_expense FROM transactions");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_income = $row['total_income'] ?? 0;
$total_expense = $row['total_expense'] ?? 0;
$profit = $total_income - $total_expense;

$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$category = trim($_GET['category'] ?? '');
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = 'description LIKE :search';
    $params['search'] = '%' . $search . '%';
}

if ($date_from !== '') {
    $where[] = '`date` >= :date_from';
    $params['date_from'] = $date_from;
}

if ($date_to !== '') {
    $where[] = '`date` <= :date_to';
    $params['date_to'] = $date_to;
}

if ($category !== '') {
    $where[] = 'category = :category';
    $params['category'] = $category;
}

if ($amount_min !== '' && is_numeric($amount_min)) {
    $where[] = 'amount >= :amount_min';
    $params['amount_min'] = (float) $amount_min;
}

if ($amount_max !== '' && is_numeric($amount_max)) {
    $where[] = 'amount <= :amount_max';
    $params['amount_max'] = (float) $amount_max;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$transactionError = '';
$transactions = [];
$perPage = 10;
$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$page = $page ?: 1;
$totalTransactions = 0;
$totalPages = 1;

try {
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM transactions $where_sql"
    );
    $countStmt->execute($params);
    $totalTransactions = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalTransactions / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare(
        "SELECT * FROM transactions
         $where_sql
         ORDER BY `date` DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Transaction query failed: ' . $e->getMessage());
    $transactionError = 'Tapahtumia ei voitu hakea. Yritä myöhemmin uudelleen.';
}

$paginationParams = $_GET;
unset($paginationParams['page']);
$paginationQuery = http_build_query($paginationParams);

?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pienyrityksen Taloushallinto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Pienyrityksen Taloushallinto</h1>
    <nav class="nav nav-pills nav-fill" style="margin-bottom: 20px;">
        <a href="index.php" class="nav-link active">Koti</a>
        <a href="add_transaction.php" class="nav-link">Lisää tapahtuma</a>
        <a href="reports.php" class="nav-link">Raportit</a>
        <a href="tax_reports.php" class="nav-link">Veroilmoitukset</a>
        <a href="logout.php" class="btn btn-danger" style="margin-left: 10px;">Kirjaudu ulos</a>
    </nav>
    <div class="container">
        <?php if ($transactionError): ?>
            <div class="alert alert-danger" role="alert">
                <?= escapeHtml($transactionError) ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col card text-center" style="width: 18rem; height: 10rem; margin-right: 20px; display: flex; align-items: center; justify-content: center;">
                <p>Kokonais tulot: <?php echo number_format($total_income, 2); ?> €</p>
            </div>
            <div class="col card text-center" style="width: 18rem; height: 10rem; margin-right: 20px; display: flex; align-items: center; justify-content: center;">
                <p>Kokonais menot: <?php echo number_format($total_expense, 2); ?> €</p>
            </div>
            <div class="col card text-center" style="width: 18rem; height: 10rem; display: flex; align-items: center; justify-content: center;">
                <p>Voittomarginaali: <?php echo number_format($profit, 2); ?> €</p>
            </div>
        </div>
        <div class="row">
            <div class="card text-center mt-4 p-3" style="margin-top: 20px;">
                <h2>Viimeisimmät tapahtumat</h2>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <tr>
                            <th>Päivämäärä</th>
                            <th>Tyyppi</th>
                            <th>Kategoria</th>
                            <th>Kuvaus</th>
                            <th>Summa</th>
                            <th>ALV</th>
                        </tr>

                        <?php foreach ($transactions as $row): ?>
                            <tr>
                                <td><?= escapeHtml($row['date']) ?></td>
                                <td><?= escapeHtml($row['type'] === 'income' ? 'Tulo' : 'Meno') ?></td>
                                <td><?= escapeHtml(ucfirst(str_replace('_', ' ', $row['category']))) ?></td>
                                <td><?= escapeHtml($row['description']) ?></td>
                                <td><?= number_format((float) $row['amount'], 2) ?> €</td>
                                <td><?= number_format((float) $row['vat_amount'], 2) ?> €</td>
                            </tr>
                        <?php endforeach; ?>

                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Tapahtumien sivutus">
                        <ul class="pagination justify-content-center mt-3 mb-0">
                            <?php for ($paginationPage = 1; $paginationPage <= $totalPages; $paginationPage++): ?>
                                <?php
                                $pageParams = $paginationParams;
                                $pageParams['page'] = $paginationPage;
                                $pageUrl = 'index.php?' . http_build_query($pageParams);
                                ?>
                                <li class="page-item <?= $paginationPage === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= escapeHtml($pageUrl) ?>">
                                        <?= $paginationPage ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <form method="get" class="card p-3 mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Haku kuvauksesta</label>
                <input type="text" id="search" name="search"
                       class="form-control"
                       value="<?= escapeHtml($search) ?>">
            </div>

            <div class="col-md-2">
                <label for="date_from" class="form-label">Päivämäärä alkaen</label>
                <input type="date" id="date_from" name="date_from"
                       class="form-control"
                       value="<?= escapeHtml($date_from) ?>">
            </div>

            <div class="col-md-2">
                <label for="date_to" class="form-label">Päivämäärä asti</label>
                <input type="date" id="date_to" name="date_to"
                       class="form-control"
                       value="<?= escapeHtml($date_to) ?>">
            </div>

            <div class="col-md-2">
                <label for="category" class="form-label">Kategoria</label>
                <select id="category" name="category" class="form-select">
                    <option value="">Kaikki kategoriat</option>
                    <option value="income" <?= $category === 'income' ? 'selected' : '' ?>>
                        Tulot
                    </option>
                    <option value="general_expense" <?= $category === 'general_expense' ? 'selected' : '' ?>>
                        Yleiset menot
                    </option>
                    <option value="travel" <?= $category === 'travel' ? 'selected' : '' ?>>
                        Matkakulut
                    </option>
                    <option value="phone_data" <?= $category === 'phone_data' ? 'selected' : '' ?>>
                        Puhelin ja data
                    </option>
                </select>
            </div>

            <div class="col-md-1">
                <label for="amount_min" class="form-label">Min. €</label>
                <input type="number" step="0.01" id="amount_min" name="amount_min"
                       class="form-control"
                       value="<?= escapeHtml($amount_min) ?>">
            </div>

            <div class="col-md-1">
                <label for="amount_max" class="form-label">Max. €</label>
                <input type="number" step="0.01" id="amount_max" name="amount_max"
                       class="form-control"
                       value="<?= escapeHtml($amount_max) ?>">
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Hae</button>
            </div>
        </div>

        <div class="mt-3">
            <a href="index.php" class="btn btn-outline-secondary">Tyhjennä suodattimet</a>
        </div>
    </form>
</body>
</html>

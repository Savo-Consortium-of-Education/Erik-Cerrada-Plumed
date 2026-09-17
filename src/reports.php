<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$reportError = '';
$quarters = [];

$currentYear = (int) date('Y');
$selectedYear = filter_input(
    INPUT_GET,
    'year',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 2000, 'max_range' => 2100]]
);

if ($selectedYear === false || $selectedYear === null) {
    $selectedYear = $currentYear;
}

$yearStart = sprintf('%04d-01-01', $selectedYear);
$nextYearStart = sprintf('%04d-01-01', $selectedYear + 1);

try {
    // Profitability
    $stmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_expense
         FROM transactions
         WHERE `date` >= :year_start AND `date` < :next_year_start"
    );
    $stmt->execute([
        'year_start' => $yearStart,
        'next_year_start' => $nextYearStart
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_income = $row['total_income'] ?? 0;
    $total_expense = $row['total_expense'] ?? 0;
    $profit = $total_income - $total_expense;

    // Quarterly reports
    for ($q = 1; $q <= 4; $q++) {
        $startMonth = (($q - 1) * 3) + 1;
        $quarterStart = sprintf('%04d-%02d-01', $selectedYear, $startMonth);

        if ($q === 4) {
            $quarterEnd = $nextYearStart;
        } else {
            $quarterEnd = sprintf(
                '%04d-%02d-01',
                $selectedYear,
                $startMonth + 3
            );
        }

        $stmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as expense
             FROM transactions
             WHERE `date` >= :quarter_start
               AND `date` < :quarter_end"
        );
        $stmt->execute([
            'quarter_start' => $quarterStart,
            'quarter_end' => $quarterEnd
        ]);
        $quarters[$q] = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Report query failed: ' . $e->getMessage());
    $reportError = 'Raportin tietoja ei voitu hakea. Yritä myöhemmin uudelleen.';
    $total_income = 0;
    $total_expense = 0;
    $profit = 0;

    for ($q = 1; $q <= 4; $q++) {
        $quarters[$q] = ['income' => 0, 'expense' => 0];
    }
}

$chart_data = [
    'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
    'income' => [],
    'expense' => [],
    'profit' => []
];

foreach ($quarters as $data) {
    $income = (float) ($data['income'] ?? 0);
    $expense = (float) ($data['expense'] ?? 0);

    $chart_data['income'][] = $income;
    $chart_data['expense'][] = $expense;
    $chart_data['profit'][] = $income - $expense;
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Raportit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .report-chart {
            position: relative;
            height: 360px;
        }
    </style>
</head>
<body>
    <h1>Raportit</h1>
    <nav class="nav nav-pills nav-fill" style="margin-bottom: 20px;">
        <a href="index.php" class="nav-link">Koti</a>
        <a href="add_transaction.php" class="nav-link">Lisää tapahtuma</a>
        <a href="reports.php" class="nav-link active">Raportit</a>
        <a href="tax_reports.php" class="nav-link">Veroilmoitukset</a>
        <a href="logout.php" class="btn btn-danger" style="margin-left: 10px;">Kirjaudu ulos</a>
    </nav>
    <div class="container">
        <?php if ($reportError): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($reportError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <h2>Yrityksen kannattavuus</h2>

        <form method="get" class="row g-3 align-items-end mb-4">
            <div class="col-auto">
                <label for="year" class="form-label">Raportin vuosi</label>
                <select id="year" name="year" class="form-select">
                    <?php for ($year = $currentYear; $year >= $currentYear - 10; $year--): ?>
                        <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>>
                            <?= $year ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Näytä raportti</button>
            </div>
        </form>

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
        <div class="card text-center mt-4 p-3">
            <h2>Kvartaaliraportit</h2>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <tr>
                        <th>Kvartaali</th>
                        <th>Tulot</th>
                        <th>Menot</th>
                        <th>Voittomarginaali</th>
                    </tr>
                    <?php foreach ($quarters as $q => $data): ?>
                    <tr>
                        <td>Q<?php echo $q; ?></td>
                        <td><?php echo number_format($data['income'] ?? 0, 2); ?> €</td>
                        <td><?php echo number_format($data['expense'] ?? 0, 2); ?> €</td>
                        <td><?php echo number_format(($data['income'] ?? 0) - ($data['expense'] ?? 0), 2); ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="card mt-4 p-3">
            <h2>Talouden kehitys kvartaaleittain</h2>
            <div class="report-chart">
                <canvas id="quarterlyChart" aria-label="Kvartaalien tulot, menot ja voitot" role="img"></canvas>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    const chartData = <?= json_encode(
        $chart_data,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ); ?>;

    new Chart(document.getElementById('quarterlyChart'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: 'Tulot',
                    data: chartData.income,
                    backgroundColor: '#198754'
                },
                {
                    label: 'Menot',
                    data: chartData.expense,
                    backgroundColor: '#dc3545'
                },
                {
                    label: 'Voitto',
                    data: chartData.profit,
                    backgroundColor: '#0d6efd'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => `${value.toLocaleString('fi-FI')} €`
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: context =>
                            `${context.dataset.label}: ${context.parsed.y.toLocaleString('fi-FI')} €`
                    }
                }
            }
        }
    });
</script>
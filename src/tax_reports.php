<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if (isset($_GET['export'])) {
    $type = $_GET['export'];
    $filename = ($type == 'vat') ? 'alv_ilmoitus.csv' : 'veroilmoitus.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['Päivämäärä', 'Tyyppi', 'Kategoria', 'Kuvaus', 'Summa', 'ALV-prosentti', 'ALV-summa']);

    $stmt = $pdo->query("SELECT * FROM transactions ORDER BY date");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['date'],
            $row['type'] == 'income' ? 'Tulo' : 'Meno',
            ucfirst(str_replace('_', ' ', $row['category'])),
            $row['description'],
            $row['amount'],
            $row['vat_rate'],
            $row['vat_amount']
        ]);
    }

    fclose($output);
    exit;
}

// VAT summary
$stmt = $pdo->query("SELECT SUM(vat_amount) as total_vat FROM transactions WHERE type='income'");
$vat_payable = $stmt->fetch(PDO::FETCH_ASSOC)['total_vat'] ?? 0;

$stmt = $pdo->query("SELECT SUM(vat_amount) as total_vat FROM transactions WHERE type='expense'");
$vat_deductible = $stmt->fetch(PDO::FETCH_ASSOC)['total_vat'] ?? 0;

$vat_balance = $vat_payable - $vat_deductible;

// Tax summary
$stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE type='income'");
$total_income = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $pdo->query("SELECT SUM(amount) as total FROM transactions WHERE type='expense'");
$total_expense = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Veroilmoitukset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
    </style>
</head>
<body>
    <h1>Veroilmoitukset</h1>
    <nav class="nav nav-pills nav-fill" style="margin-bottom: 20px;">
        <a href="index.php" class="nav-link">Koti</a>
        <a href="add_transaction.php" class="nav-link">Lisää tapahtuma</a>
        <a href="reports.php" class="nav-link">Raportit</a>
        <a href="tax_reports.php" class="nav-link active">Veroilmoitukset</a>
        <a href="logout.php" class=" btn btn-danger" style="margin-left: 10px;">Kirjaudu ulos</a>
    </nav>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="card mt-4 p-3">
                    <h2>ALV-ilmoitus</h2>
                    <p>ALV maksettava: <?php echo number_format($vat_payable, 2); ?> €</p>
                    <p>ALV vähennettävä: <?php echo number_format($vat_deductible, 2); ?> €</p>
                    <p>ALV-saldo: <?php echo number_format($vat_balance, 2); ?> €</p>
                </div>
            </div>
            <div class="col">
                <div class="card mt-4 p-3">
                    <h2>Veroilmoitus</h2>
                    <p>Kokonais tulot: <?php echo number_format($total_income, 2); ?> €</p>
                    <p>Kokonais menot: <?php echo number_format($total_expense, 2); ?> €</p>
                    <p>Verotettava tulo: <?php echo number_format($total_income - $total_expense, 2); ?> €</p>
                </div>
            </div>
            <div class="card mt-4 p-3">
                <h1>CSV-viennit</h1>
                <div class="container">
                    <div class="row">
                        <div class="col">
                            <button class="btn btn-primary" onclick="window.location.href='?export=vat'">Vie ALV-ilmoitus CSV:ään</button>
                        </div>
                        <div class="col">
                            <button class="btn btn-primary" onclick="window.location.href='?export=tax'">Vie veroilmoitus CSV:ään</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
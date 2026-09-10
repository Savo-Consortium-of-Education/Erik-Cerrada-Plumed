<?php
require 'config.php';

// Profitability
$stmt = $pdo->query("SELECT SUM(CASE WHEN type='income' THEN amount ELSE 0 END) as total_income, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) as total_expense FROM transactions");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$total_income = $row['total_income'] ?? 0;
$total_expense = $row['total_expense'] ?? 0;
$profit = $total_income - $total_expense;
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
    </nav>
    <div class="container">
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

                        <?php
                        $stmt = $pdo->query("SELECT * FROM transactions ORDER BY date DESC LIMIT 10");
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . $row['date'] . "</td>";
                            echo "<td>" . ($row['type'] == 'income' ? 'Tulo' : 'Meno') . "</td>";
                            echo "<td>" . ucfirst(str_replace('_', ' ', $row['category'])) . "</td>";
                            echo "<td>" . $row['description'] . "</td>";
                            echo "<td>" . number_format($row['amount'], 2) . " €</td>";
                            echo "<td>" . number_format($row['vat_amount'], 2) . " €</td>";
                            echo "</tr>";
                        }
                        ?>
                    </table>
                </div>
            </div>
        </div>
</body>
</html>

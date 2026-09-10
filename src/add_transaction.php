<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date = $_POST['date'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $vat_rate = $_POST['vat_rate'];
    $vat_amount = ($amount * $vat_rate / 100) / (1 + $vat_rate / 100); // Calculate VAT amount

    $stmt = $pdo->prepare("INSERT INTO transactions (date, type, category, description, amount, vat_rate, vat_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$date, $type, $category, $description, $amount, $vat_rate, $vat_amount]);

    $message = 'Tapahtuma lisätty onnistuneesti!';
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lisää tapahtuma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 400px; }
        label { display: block; margin-top: 10px; }
        input, select { width: 100%; padding: 5px; }
        .message { color: green; }
    </style>
</head>
<body>
    <h1>Lisää tapahtuma</h1>
    <nav class="nav nav-pills nav-fill" style="margin-bottom: 20px;">
        <a href="index.php" class="nav-link">Koti</a>
        <a href="add_transaction.php" class="nav-link active">Lisää tapahtuma</a>
        <a href="reports.php" class="nav-link">Raportit</a>
        <a href="tax_reports.php" class="nav-link">Veroilmoitukset</a>
        <a href="logout.php" class="btn btn-danger" style="margin-left: 10px;">Kirjaudu ulos</a>
    </nav>
    <br><br>
    <?php if ($message) echo "<p class='message'>$message</p>"; ?>
    <div class="col card">
        <form method="post" style="margin: 20px;">
            <label>Päivämäärä:</label>
            <input type="date" class="form-control" name="date" required>

            <label>Tyyppi:</label>
            <select name="type" class="form-control" required>
                <option value="income">Tulo</option>
                <option value="expense">Meno</option>
            </select>

            <label>Kategoria:</label>
            <select name="category" class="form-control" required>
                <option value="income">Tulo</option>
                <option value="general_expense">Yleinen meno</option>
                <option value="travel">Matkalasku</option>
                <option value="phone_data">Puhelin ja tietoliikenne</option>
            </select>

            <label>Kuvaus:</label>
            <input type="text" class="form-control" name="description" required>

            <label>Summa (€):</label>
            <input type="number" step="0.01" class="form-control" name="amount" required>

            <label>ALV-prosentti:</label>
            <input type="number" step="0.01" class="form-control" name="vat_rate" value="24">

            <button type="submit"  class="btn btn-success" style="margin-top: 10px; width: 100%;">Lisää tapahtuma</button>
        </form>
    </div>
</body>
</html>
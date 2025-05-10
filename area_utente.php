<?php
session_start();

if (!isset($_SESSION['utente_loggato']) || $_SESSION['utente_loggato'] !== true) {
    header("Location: index.html");
    exit();
}

$nome = isset($_SESSION['nome']) ? htmlspecialchars($_SESSION['nome']) : 'N/D';
$cognome = isset($_SESSION['cognome']) ? htmlspecialchars($_SESSION['cognome']) : 'N/D';
$username = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'N/D';
$email = isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : 'N/D';
$data_registrazione = isset($_SESSION['data_registrazione']) ? htmlspecialchars($_SESSION['data_registrazione']) : 'N/D';


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utente - <?php echo $nome; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .dashboard-container {
            background-color: white;
            padding: 2rem;
            border-radius: .5rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            width: 100%;
            max-width: 800px;
        }
        .info-label {
            font-weight: bold;
        }
        .info-value {
            margin-bottom: 0.5rem;
        }
        .logout-btn-container {
            margin-top: 2rem;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="title-wrapper">
        <h1 class="display-5">Benvenuto/a, <?php echo $nome; ?>!</h1>
    </div>

    <div class="dashboard-container">
        <h2 class="mb-4">I tuoi Dati</h2>
        <div class="row">
            <div class="col-md-3 info-label">Nome Completo:</div>
            <div class="col-md-9 info-value"><?php echo $nome . " " . $cognome; ?></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-3 info-label">Username:</div>
            <div class="col-md-9 info-value"><?php echo $username; ?></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-3 info-label">Email:</div>
            <div class="col-md-9 info-value"><?php echo $email; ?></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-3 info-label">Data Registrazione:</div>
            <div class="col-md-9 info-value"><?php echo $data_registrazione; ?></div>
        </div>

        <div class="logout-btn-container">
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <p class="mt-5 mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?></p>

</body>
</html>
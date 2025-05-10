<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$percorso_utenti = __DIR__ . '/utenti.json';
$messaggio_cambio = "";
$tipo_messaggio_cambio = "danger";
$mostra_form_cambio_pw = true;

if (!isset($_SESSION['reset_codice_verificato_successo']) || $_SESSION['reset_codice_verificato_successo'] !== true || !isset($_SESSION['reset_email_in_corso'])) {

    $_SESSION['messaggio_errore_reset'] = "Processo di reset password non valido o scaduto. Richiedi un nuovo codice.";
    header("Location: dimenticata.php");
    exit();
}

$email_utente_da_cambiare = $_SESSION['reset_email_in_corso'];

if (isset($_POST['submit_nuova_password'])) {
    $nuova_password = isset($_POST['nuova_password']) ? $_POST['nuova_password'] : '';
    $conferma_nuova_password = isset($_POST['conferma_nuova_password']) ? $_POST['conferma_nuova_password'] : '';

    if (empty($nuova_password) || empty($conferma_nuova_password)) {
        $messaggio_cambio = "Entrambi i campi password sono obbligatori.";
    } elseif (strlen($nuova_password) < 6) {
        $messaggio_cambio = "La nuova password deve essere di almeno 6 caratteri.";
    } elseif ($nuova_password !== $conferma_nuova_password) {
        $messaggio_cambio = "Le password inserite non coincidono.";
    } else {
        if (file_exists($percorso_utenti) && is_readable($percorso_utenti) && is_writable($percorso_utenti)) {
            $contenuto_json = file_get_contents($percorso_utenti);
            $utenti = json_decode($contenuto_json, true);
            $utente_modificato_flag = false;

            if ($utenti !== null) {
                foreach ($utenti as $i => $utente) {
                    if (isset($utente['email']) && strtolower($utente['email']) === strtolower($email_utente_da_cambiare)) {
                        $utenti[$i]['password'] = password_hash($nuova_password, PASSWORD_DEFAULT);
                        $utente_modificato_flag = true;
                        break;
                    }
                }

                if ($utente_modificato_flag) {
                    $json_da_scrivere = json_encode($utenti, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    if (file_put_contents($percorso_utenti, $json_da_scrivere)) {
                        $messaggio_cambio = "Password cambiata con successo! Ora puoi effettuare il login con la nuova password.";
                        $tipo_messaggio_cambio = "success";
                        $mostra_form_cambio_pw = false;

                        unset($_SESSION['reset_email_in_corso']);
                        unset($_SESSION['reset_codice_verificato_successo']);

                    } else {
                        $messaggio_cambio = "Errore del server: Impossibile salvare la nuova password nel file JSON.";
                        error_log("Errore scrittura file JSON per cambio password: " . $percorso_utenti);
                    }
                } else {
                    $messaggio_cambio = "Errore critico: Utente non trovato per l'aggiornamento. Riprova l'intero processo di recupero password.";
                    $mostra_form_cambio_pw = false;
                }
            } else {
                $messaggio_cambio = "Errore del server: impossibile leggere i dati utente per l'aggiornamento.";
                error_log("Errore decoding JSON (cambio password) in cambia.php: " . json_last_error_msg());
            }
        } else {
            $messaggio_cambio = "Errore del server: file utenti non accessibile o non scrivibile per l'aggiornamento.";
            error_log("File utenti.json non accessibile/scrivibile in cambia.php: " . $percorso_utenti);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambia Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
            padding: 1rem;
        }
        .form-container {
            background-color: white;
            padding: 2rem;
            border-radius: .5rem;
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            width: 100%;
            max-width: 500px;
        }
        .title-wrapper {
            text-align: center;
            margin-bottom: 1.5rem;
        }
         .alert {
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="title-wrapper">
        <h1 class="display-5">Imposta Nuova Password</h1>
    </div>

    <?php if (!empty($messaggio_cambio)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($tipo_messaggio_cambio); ?> mb-4" role="alert">
        <?php echo htmlspecialchars($messaggio_cambio); ?>
    </div>
    <?php endif; ?>

    <?php if ($mostra_form_cambio_pw): ?>
    <div class="form-container">
        <p class="mb-3">Stai per cambiare la password per l'account associato a: <br><strong><?php echo htmlspecialchars($email_utente_da_cambiare); ?></strong></p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="mb-3">
                <label for="nuova_password" class="form-label">Nuova Password</label>
                <input type="password" name="nuova_password" id="nuova_password" class="form-control" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="conferma_nuova_password" class="form-label">Conferma Nuova Password</label>
                <input type="password" name="conferma_nuova_password" id="conferma_nuova_password" class="form-control" required minlength="6">
            </div>
            <div class="d-grid">
                <button type="submit" name="submit_nuova_password" class="btn btn-success">Imposta Nuova Password</button>
            </div>
        </form>
        <p class="text-center mt-3"><a href="dimenticata.php?annulla_reset=1">Annulla e torna indietro</a></p>
    </div>
    <?php elseif ($tipo_messaggio_cambio == "success"): ?>
        <div class="text-center">
            <a href="index.html" class="btn btn-primary">Vai al Login</a>
        </div>
    <?php endif; ?>

    <p class="mt-5 mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?></p>
</body>
</html>
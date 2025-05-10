<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$percorso_utenti = __DIR__ . '/utenti.json';
$messaggio_utente = "";
$tipo_messaggio = "danger";
function generaCodiceRecupero($lunghezza = 6) {
    $caratteri = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codice_generato = '';
    for ($i = 0; $i < $lunghezza; $i++) {
        $codice_generato .= $caratteri[rand(0, strlen($caratteri) - 1)];
    }
    return $codice_generato;
}

if (isset($_POST['submit_email_reset'])) {
    $email_inserita = isset($_POST['email_reset']) ? trim($_POST['email_reset']) : '';

    if (empty($email_inserita) || !filter_var($email_inserita, FILTER_VALIDATE_EMAIL)) {
        $messaggio_utente = "Per favore, inserisci un indirizzo email valido.";
    } else {
        $utente_trovato = null;
        if (file_exists($percorso_utenti) && is_readable($percorso_utenti)) {
            $contenuto_json = file_get_contents($percorso_utenti);
            $utenti = json_decode($contenuto_json, true);

            if ($utenti !== null) {
                foreach ($utenti as $utente) {
                    if (isset($utente['email']) && strtolower($utente['email']) === strtolower($email_inserita)) {
                        $utente_trovato = $utente;
                        break;
                    }
                }
            } else {
                $messaggio_utente = "Errore del server: impossibile leggere i dati utente.";
                error_log("Errore decoding JSON in dimenticata.php: " . json_last_error_msg());
            }
        } else {
            $messaggio_utente = "Errore del server: file utenti non trovato.";
            error_log("File utenti.json non trovato o non leggibile in: " . $percorso_utenti);
        }

        if ($utente_trovato) {
            $codice_recupero = generaCodiceRecupero();
            $_SESSION['reset_email'] = $utente_trovato['email'];
            $_SESSION['reset_codice'] = $codice_recupero;
            $email_destinatario = $utente_trovato['email'];
            $nome_utente = isset($utente_trovato['nome']) ? $utente_trovato['nome'] : 'Utente';
            $email_soggetto = "Codice di recupero password";
            $email_corpo = "Ciao " . htmlspecialchars($nome_utente) . ",\n\n"
                         . "Hai richiesto un codice per reimpostare la tua password.\n"
                         . "Il tuo codice di recupero è: " . $codice_recupero . "\n\n"
                         . "Se non hai richiesto tu questo codice, puoi ignorare questa email.\n\n"; 

            $email_headers = "From: noreply@salvo17.com\r\n" .
                             "Reply-To: noreply@salvo17.com\r\n" .
                             "X-Mailer: PHP/" . phpversion();

            if (mail($email_destinatario, $email_soggetto, $email_corpo, $email_headers)) {
                $messaggio_utente = "Se l'indirizzo email è registrato, riceverai un codice di recupero. Controlla la tua casella di posta (anche lo spam).";
                $tipo_messaggio = "success";
            } else {
                $messaggio_utente = "Errore nell'invio dell'email di recupero. Riprova più tardi.";
                error_log("Invio email recupero password fallito per: " . $email_destinatario);
            }
        } else {
            $messaggio_utente = "Se l'indirizzo email è registrato, riceverai un codice di recupero. Controlla la tua casella di posta (anche lo spam).";
            $tipo_messaggio = "info";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recupero Password</title>
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
        }
    </style>
</head>
<body>
    <div class="title-wrapper">
        <h1 class="display-5">Recupero Password</h1>
    </div>

    <?php if (!empty($messaggio_utente)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($tipo_messaggio); ?> mb-4" role="alert" style="max-width: 500px; width: 100%;">
        <?php echo htmlspecialchars($messaggio_utente); ?>
    </div>
    <?php endif; ?>

    <?php if ($_SERVER["REQUEST_METHOD"] != "POST" || ($tipo_messaggio == "danger" && isset($_POST['submit_email_reset']))): ?>
    <div class="form-container">
        <p class="mb-3 text-center">Inserisci l'indirizzo email associato al tuo account. Ti invieremo un codice per reimpostare la password.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="mb-3">
                <label for="email_reset" class="form-label">La tua E-Mail</label>
                <input type="email" name="email_reset" id="email_reset" class="form-control" placeholder="esempio@dominio.com" required
                       value="<?php echo isset($_POST['email_reset']) ? htmlspecialchars($_POST['email_reset']) : ''; ?>">
            </div>
            <div class="d-grid">
                <button type="submit" name="submit_email_reset" class="btn btn-primary">Invia Codice</button>
            </div>
        </form>
    </div>
    <?php elseif ($tipo_messaggio == "success" || $tipo_messaggio == "info"): ?>
        <div class="text-center">
            <p>Puoi tornare alla pagina di <a href="index.html">Login</a>.</p>
        </div>
    <?php endif; ?>

    <p class="mt-5 mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?></p>
</body>
</html>
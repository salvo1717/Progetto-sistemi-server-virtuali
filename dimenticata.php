<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$percorso_utenti = __DIR__ . '/utenti.json';
$messaggio_utente = "";
$tipo_messaggio = "danger";
$mostra_form_email = true;
$mostra_form_codice = false;
$email_per_reset_in_sessione = isset($_SESSION['reset_email_in_corso']) ? $_SESSION['reset_email_in_corso'] : '';

function generaCodiceRecupero($lunghezza = 6) {
    $caratteri = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codice_generato = '';
    for ($i = 0; $i < $lunghezza; $i++) {
        $codice_generato .= $caratteri[rand(0, strlen($caratteri) - 1)];
    }
    return $codice_generato;
}
if (isset($_GET['annulla_reset']) && $_GET['annulla_reset'] == '1') {
    unset($_SESSION['reset_email_in_corso'], $_SESSION['reset_codice_inviato']);
    $messaggio_utente = "Processo di reset annullato. Puoi inserire nuovamente la tua email.";
    $tipo_messaggio = "info";

}

elseif (isset($_POST['submit_email_reset'])) { 
    $email_inserita = isset($_POST['email_reset']) ? trim($_POST['email_reset']) : '';

    if (empty($email_inserita) || !filter_var($email_inserita, FILTER_VALIDATE_EMAIL)) {
        $messaggio_utente = "Per favore, inserisci un indirizzo email valido.";
    } else {
        $utente_trovato = null;
        if (file_exists($percorso_utenti) && is_readable($percorso_utenti)) {
            $contenuto_json = file_get_contents($percorso_utenti);
            $utenti = json_decode($contenuto_json, true);

            if ($utenti !== null) {
                foreach ($utenti as $utente_registrato) {
                    if (isset($utente_registrato['email']) && strtolower($utente_registrato['email']) === strtolower($email_inserita)) {
                        $utente_trovato = $utente_registrato;
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
            $_SESSION['reset_email_in_corso'] = $utente_trovato['email'];
            $_SESSION['reset_codice_inviato'] = $codice_recupero;

            $email_destinatario = $utente_trovato['email'];
            $nome_utente = isset($utente_trovato['nome']) ? $utente_trovato['nome'] : 'Utente';
            $email_soggetto = "Il tuo codice per il cambio password";
            $email_corpo = "Ciao " . htmlspecialchars($nome_utente) . ",\n\n"
                         . "Usa il seguente codice per procedere con il cambio della tua password:\n"
                         . "Codice: " . $codice_recupero . "\n\n"
                         . "Se non hai richiesto tu questo codice, puoi ignorare questa email.";
            $email_headers = "From: noreply@tuodominio.com\r\n" .
                             "Reply-To: noreply@tuodominio.com\r\n" .
                             "X-Mailer: PHP/" . phpversion();

            if (mail($email_destinatario, $email_soggetto, $email_corpo, $email_headers)) {
                $messaggio_utente = "È stato inviato un codice all'indirizzo " . htmlspecialchars($email_destinatario) . ". Inseriscilo qui sotto.";
                $tipo_messaggio = "success";
                $mostra_form_email = false;
                $mostra_form_codice = true;
                $email_per_reset_in_sessione = $email_destinatario;
            } else {
                $messaggio_utente = "Errore nell'invio dell'email. Riprova più tardi.";
                error_log("Invio email recupero (codice) fallito per: " . $email_destinatario);
            }
        } else {
            $messaggio_utente = "Se l'indirizzo email è registrato e valido, riceverai un codice. Controlla la tua casella di posta (anche lo spam).";
            $tipo_messaggio = "info";
            $mostra_form_email = false;
            $mostra_form_codice = true;
            if (!isset($_SESSION['reset_email_in_corso'])) {
                 $email_per_reset_in_sessione = '';
            }
        }
    }
} elseif (isset($_POST['submit_codice_reset'])) {
    $codice_inserito = isset($_POST['codice_reset_input']) ? trim($_POST['codice_reset_input']) : '';
    $email_associata_al_codice = isset($_SESSION['reset_email_in_corso']) ? $_SESSION['reset_email_in_corso'] : null;

    $mostra_form_email = false;
    $mostra_form_codice = true;

    if (empty($codice_inserito)) {
        $messaggio_utente = "Per favore, inserisci il codice ricevuto via email.";
    } 
    elseif (empty($email_associata_al_codice) || !isset($_SESSION['reset_codice_inviato']) ) {
        $messaggio_utente = "Sessione di reset non valida. Richiedi un nuovo codice.";
        $mostra_form_codice = false;
        $mostra_form_email = true;
        unset($_SESSION['reset_email_in_corso'], $_SESSION['reset_codice_inviato']);
    } 
    elseif (strtoupper($codice_inserito) === strtoupper($_SESSION['reset_codice_inviato'])) {
        $_SESSION['reset_codice_verificato_successo'] = true;
        unset($_SESSION['reset_codice_inviato']);

        header("Location: cambia.php");
        exit();
    } else {
        $messaggio_utente = "Codice di verifica errato. Riprova.";
    }
}
if (!$mostra_form_email && !$mostra_form_codice && 
    (isset($_POST['submit_email_reset']) && $tipo_messaggio == "success") ||
    (isset($_SESSION['reset_email_in_corso']) && isset($_SESSION['reset_codice_inviato']) && !isset($_POST['submit_codice_reset']))
   ) {
    $mostra_form_email = false;
    $mostra_form_codice = true;
    if(empty($messaggio_utente) || $tipo_messaggio != "success") {
         $messaggio_utente = "Inserisci il codice che ti è stato inviato all'indirizzo " . htmlspecialchars($_SESSION['reset_email_in_corso']) . ".";
         $tipo_messaggio = "info";
    }
    $email_per_reset_in_sessione = $_SESSION['reset_email_in_corso'];
}


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Dimenticata</title>
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
        <h1 class="display-5">Password Dimenticata</h1>
    </div>

    <?php if (!empty($messaggio_utente)): ?>
    <div class="alert alert-<?php echo htmlspecialchars($tipo_messaggio); ?> mb-4" role="alert">
        <?php echo htmlspecialchars($messaggio_utente); ?>
    </div>
    <?php endif; ?>

    <?php if ($mostra_form_email): ?>
    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="mb-3">
                <label for="email_reset" class="form-label">La tua E-Mail</label>
                <input type="email" name="email_reset" id="email_reset" class="form-control" placeholder="esempio@dominio.com" required
                       value="<?php echo isset($_POST['email_reset']) ? htmlspecialchars($_POST['email_reset']) : $email_per_reset_in_sessione; ?>">
            </div>
            <div class="d-grid">
                <button type="submit" name="submit_email_reset" class="btn btn-primary">Invia Codice</button>
            </div>
        </form>
        <p class="text-center mt-3"><a href="index.html">Torna al Login</a></p>
    </div>
    <?php endif; ?>

    <?php if ($mostra_form_codice): ?>
    <div class="form-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="mb-3">
                <label for="codice_reset_input" class="form-label">Codice di Verifica</label>
                <input type="text" name="codice_reset_input" id="codice_reset_input" class="form-control"
                       placeholder="Codice a 6 caratteri" pattern="[0-9a-zA-Z]{6}" required autofocus>
            </div>
            <div class="d-grid">
                <button type="submit" name="submit_codice_reset" class="btn btn-success">Verifica Codice e Procedi</button>
            </div>
        </form>
        <p class="text-center mt-3"><a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?annulla_reset=1">Annulla e richiedi nuovo codice</a></p>
    </div>
    <?php endif; ?>

    <?php
    if (!$mostra_form_email && !$mostra_form_codice && $tipo_messaggio !== 'danger' &&
        !(isset($_POST['submit_email_reset']) && $tipo_messaggio === 'success') 
       ):
    ?>
        <p class="text-center mt-3"><a href="index.html">Torna al Login</a></p>
    <?php endif; ?>


    <p class="mt-5 mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?></p>
</body>
</html>
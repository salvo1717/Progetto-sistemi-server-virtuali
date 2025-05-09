<?php 
    session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    $percorso = __DIR__ . '/utenti.json';
    $messaggio_login = "";
    $login_successo = false;
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_o_email_inserito = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password_inserita = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username_o_email_inserito) || empty($password_inserita)) {
        $messaggio_login = "Username/Email e Password sono obbligatori.";
    } else {
        if (file_exists($percorso) && is_readable($percorso)) {
            $contenuto_json = file_get_contents($percorso);
            $utenti_registrati = json_decode($contenuto_json, true);

            if ($utenti_registrati === null && json_last_error() !== JSON_ERROR_NONE) {
                $messaggio_login = "Errore del server: Impossibile leggere i dati utente.";
                error_log("Errore decoding JSON in login_utente.php: " . json_last_error_msg());
            } elseif (empty($utenti_registrati)) {
                 $messaggio_login = "Nessun utente registrato trovato.";
            } else {
                $utente_trovato = null;
                foreach ($utenti_registrati as $utente) {
                    if ((isset($utente['username']) && $utente['username'] === $username_o_email_inserito) || (isset($utente['email']) && $utente['email'] === $username_o_email_inserito)) {
                        $utente_trovato = $utente;
                        break;
                    }
                }

                if ($utente_trovato) {
                    if (isset($utente_trovato['password']) && password_verify($password_inserita, $utente_trovato['password'])) {
                        $login_successo = true;
                        $messaggio_login = "Login effettuato con successo! Benvenuto/a " . htmlspecialchars($utente_trovato['nome']) . "!";
                        $_SESSION['utente_loggato'] = true;
                        $_SESSION['username'] = $utente_trovato['username'];
                        $_SESSION['nome'] = $utente_trovato['nome'];

                        header("Location: dashboard.php");
                        exit;

                    } else {
                        $messaggio_login = "Credenziali non valide (password errata).";
                    }
                } else {
                    $messaggio_login = "Credenziali non valide (utente non trovato).";
                }
            }
        } else {
            $messaggio_login = "Errore del server: Il file degli utenti non è accessibile o non esiste.";
            error_log("File utenti.json non trovato o non leggibile in: " . $percorso);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css"> <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .message-container { padding: 2rem; background-color: white; border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); text-align: center; }
    </style>
</head>
<body>
    <div class="message-container">
        <?php if (!empty($messaggio_login) && !$login_successo):?>
            <h1 class="text-danger"><?php echo htmlspecialchars($messaggio_login); ?></h1>
            <a href="index.html" class="btn btn-primary mt-3">Riprova Login</a>
        <?php elseif (!$login_successo && $_SERVER["REQUEST_METHOD"] != "POST" && !(isset($_SESSION['utente_loggato']) && $_SESSION['utente_loggato'] === true) ): ?>
            <h1>Effettua il Login</h1>
             <p>Per favore, usa il <a href="index.html">modulo di login</a>.</p>
        <?php elseif ($login_successo && $_SERVER["REQUEST_METHOD"] == "POST" && (isset($_SESSION['utente_loggato'])) ): ?>
            <div class="container" style="text-align:center; display: flex; flex-direction: row;">
                <p style="font-family: Cal Sans, sans-serif;">Ciao $utente_trovato["nome"] $utente_trovato["cognome"]</p>
                <p style="font-family: Cal Sans, sans-serif;">Mail: $utente_trovato["email"]</p>
                <p style="font-family: Cal Sans, sans-serif;">Username: $utente_trovato["username"]</p>
                <p style="font-family: Cal Sans, sans-serif;">Registrato il: $utente_trovato["data_registrazione"]</p>
            </div>
        <?php endif; ?>
        </div>
</body>
</html>
?>
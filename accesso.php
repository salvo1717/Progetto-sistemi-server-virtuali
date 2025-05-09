<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$percorso = __DIR__ . '/utenti.json';
$messaggio_login = "";
$login_successo = false;

function generaCodice2FA($lunghezza = 6)
{
    $caratteri = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codice_generato = '';
    for ($i = 0; $i < $lunghezza; $i++) {
        $codice_generato .= $caratteri[rand(0, strlen($caratteri) - 1)];
    }
    return $codice_generato;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bottone_submit_codice_2fa'])) {
    $mostra_form_login = false;
    $mostra_form_2fa = true;

    $codice_2fa_inserito = isset($_POST['codice_2fa_input']) ? trim($_POST['codice_2fa_input']) : '';

    if (empty($codice_2fa_inserito)) {
        $messaggio_output = "Per favore, inserisci il codice.";
    } elseif (!isset($_SESSION['2fa_codice_salvato_in_sessione']) || !isset($_SESSION['2fa_dati_utente_per_login'])) {
        $messaggio_output = "Sessione di verifica scaduta o non valida. Riprova il login.";
        $mostra_form_2fa = false;
        $mostra_form_login = true;
        unset($_SESSION['2fa_codice_salvato_in_sessione'], $_SESSION['2fa_dati_utente_per_login'], $_SESSION['2fa_timestamp_scadenza']);
    } elseif (isset($_SESSION['2fa_timestamp_scadenza']) && time() > $_SESSION['2fa_timestamp_scadenza']) {
        $messaggio_output = "Codice di verifica scaduto. Riprova il login.";
        $mostra_form_2fa = false;
        $mostra_form_login = true;
        unset($_SESSION['2fa_codice_salvato_in_sessione'], $_SESSION['2fa_dati_utente_per_login'], $_SESSION['2fa_timestamp_scadenza']);
    } elseif (strtoupper($codice_2fa_inserito) == strtoupper($_SESSION['2fa_codice_salvato_in_sessione'])) {
        $dati_utente_da_sessione_2fa = $_SESSION['2fa_dati_utente_per_login'];
        $_SESSION['utente_loggato'] = true;
        $_SESSION['username'] = $dati_utente_da_sessione_2fa['username'];
        $_SESSION['nome'] = $dati_utente_da_sessione_2fa['nome'];
        $_SESSION['email'] = $dati_utente_da_sessione_2fa['email'];

        unset($_SESSION['2fa_codice_salvato_in_sessione'], $_SESSION['2fa_dati_utente_per_login'], $_SESSION['2fa_timestamp_scadenza']);
        header("Location: dashboard.php");
        exit;
    } else {
        $messaggio_output = "Codice di verifica errato.";
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
                        $_SESSION["email"] = $utente_trovato['email'];

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
    <link rel="stylesheet" href="index.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .message-container {
            padding: 2rem;
            background-color: white;
            border-radius: .5rem;
            box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h1 class="display-5 mb-4">
            <?php echo $mostra_form_2fa ? "Verifica Codice" : "Login"; ?>
        </h1>

        <?php if (!empty($messaggio_output)): ?>
            <div class="alert <?php
            $classe_alert = 'alert-info';
            if (str_contains(strtolower($messaggio_output), 'errore') || str_contains(strtolower($messaggio_output), 'non valide') || str_contains(strtolower($messaggio_output), 'errato') || str_contains(strtolower($messaggio_output), 'obbligatori') || str_contains(strtolower($messaggio_output), 'scadut')) {
                $classe_alert = 'alert-danger';
            } elseif (str_contains(strtolower($messaggio_output), 'abbiamo inviato') || str_contains(strtolower($messaggio_output), 'successo')) {
                $classe_alert = 'alert-success';
            }
            echo $classe_alert;
            ?>" role="alert">
                <?php echo htmlspecialchars($messaggio_output); ?>
            </div>
        <?php endif; ?>

        <?php if ($mostra_form_login): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username_html_id" name="username"
                        placeholder="Username o E-mail"
                        value="<?php echo htmlspecialchars($valore_username_ripopolamento); ?>" required>
                    <label for="username_html_id">Username/E-mail</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password_html_id" name="password" placeholder="Password"
                        required>
                    <label for="password_html_id">Password</label>
                </div>
                <button class="btn btn-primary w-100 py-2 mb-3" type="submit">Continua</button>
                <div class="text-center">
                    <small><a href="dimenticata.html" class="form-text d-block mb-1">Password dimenticata?</a></small>
                    <small><a href="crea.html" class="form-text d-block">Creare un nuovo account?</a></small>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($mostra_form_2fa): ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="codice_2fa_input_id" name="codice_2fa_input_utente"
                        placeholder="Codice" inputmode="text" pattern="[0-9a-zA-Z]{6}"
                        title="Inserisci il codice a 6 caratteri" required autofocus>
                    <label for="codice_2fa_input_id">Codice di Verifica</label>
                </div>
                <button class="btn btn-success w-100 py-2" type="submit" name="bottone_submit_codice_2fa">Verifica
                    Codice</button>
                <div class="text-center mt-3">
                    <small><a
                            href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?messaggio=Login annullato.&reset_2fa=1"
                            class="text-muted">Annulla e torna al login</a></small>
                </div>
            </form>
        <?php endif; ?>

        <p class="mt-4 mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?></p>
    </div>
</body>

</html>
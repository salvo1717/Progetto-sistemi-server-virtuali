<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$percorso = __DIR__ . '/utenti.json';
$messaggio_login = "";
$login_successo = false;
$mostra_form_login = true;
$mostra_form_2fa = false;
$valore_username_ripopolamento = "";


$messaggio_output = "";
$classe_alert = "alert-danger";

function generaCodice2FA($lunghezza = 6)
{
    $caratteri = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codice_generato = '';
    for ($i = 0; $i < $lunghezza; $i++) {
        $codice_generato .= $caratteri[rand(0, strlen($caratteri) - 1)];
    }
    return $codice_generato;
}

if (isset($_POST['bottone_submit_codice_2fa'])) {
    $mostra_form_login = false;
    $mostra_form_2fa = true;

    $codice_2fa_inserito = isset($_POST['codice_2fa_input_utente']) ? trim($_POST['codice_2fa_input_utente']) : '';

    if (empty($codice_2fa_inserito)) {
        $messaggio_output = "Per favore, inserisci il codice.";
        $classe_alert = "alert-warning";
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
        if (isset($dati_utente_da_sessione_2fa['cognome'])) {
            $_SESSION['cognome'] = $dati_utente_da_sessione_2fa['cognome'];
        }
        if (isset($dati_utente_da_sessione_2fa['data_registrazione'])) {
            $_SESSION['data_registrazione'] = $dati_utente_da_sessione_2fa['data_registrazione'];
        }

        unset($_SESSION['2fa_codice_salvato_in_sessione'], $_SESSION['2fa_dati_utente_per_login'], $_SESSION['2fa_timestamp_scadenza']);
        header("Location: area_utente.php");
        exit();

    } else {
        $messaggio_output = "Codice di verifica errato.";
        $classe_alert = "alert-danger";
    }

} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_o_email_inserito = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password_inserita = isset($_POST['password']) ? $_POST['password'] : '';
    $valore_username_ripopolamento = $username_o_email_inserito;

    if (empty($username_o_email_inserito) || empty($password_inserita)) {
        $messaggio_login = "Username/Email e Password sono obbligatori.";
    } else {
        if (file_exists($percorso) && is_readable($percorso)) {
            $contenuto_json = file_get_contents($percorso);
            $utenti_registrati = json_decode($contenuto_json, true);

            if ($utenti_registrati === null && json_last_error() !== JSON_ERROR_NONE) {
                $messaggio_login = "Errore del server: Impossibile leggere i dati utente.";
                error_log("Errore decoding JSON in accesso.php: " . json_last_error_msg());
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


                        $_SESSION['2fa_dati_utente_per_login'] = $utente_trovato;
                        $_SESSION['2fa_codice_salvato_in_sessione'] = generaCodice2FA();

                        $mostra_form_login = false;
                        $mostra_form_2fa = true;
                        $messaggio_output = "È stato inviato un codice di verifica all'indirizzo email: " . htmlspecialchars($utente_trovato['email']) . ". Inseriscilo qui sotto.";
                        $classe_alert = "alert-info";


                        $email_destinatario = $utente_trovato['email'];
                        $email_soggetto = "Il tuo codice di verifica 2FA";
                        $email_corpo = "Ciao " . htmlspecialchars($utente_trovato['nome']) . ",\n\n"
                            . "Il tuo codice di verifica è: " . $_SESSION['2fa_codice_salvato_in_sessione'] . "\n\n"
                            . "Se non hai richiesto tu questo codice, puoi ignorare questa email.";
                        $email_headers = "From: noreply@salvo17.com\r\n" .
                            "Reply-To: noreply@salvo17.com\r\n" .
                            "X-Mailer: PHP/" . phpversion();

                        if (!mail($email_destinatario, $email_soggetto, $email_corpo, $email_headers)) {
                            error_log("Invio email 2FA fallito per: " . $email_destinatario);
                            $messaggio_output .= " (Invio email fallito.)";
                        }

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

if (isset($_GET['reset_2fa']) && $_GET['reset_2fa'] == '1') {
    unset($_SESSION['2fa_codice_salvato_in_sessione'], $_SESSION['2fa_dati_utente_per_login'], $_SESSION['2fa_timestamp_scadenza']);
    $mostra_form_login = true;
    $mostra_form_2fa = false;
    $messaggio_login = isset($_GET['messaggio']) ? htmlspecialchars($_GET['messaggio']) : "Login annullato.";
    header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
    exit();
}

?>

<?php if ($mostra_form_login): ?>
    <!DOCTYPE html>
    <html lang="it">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $mostra_form_2fa ? "Verifica Codice 2FA" : "Login"; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="index.css">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Cal+Sans&display=swap');
            .title-wrapper {
                width: 100%;
                text-align: center;
                margin-bottom: 1rem;
            }
            a{
                font-size: 90%;
                font-family: "Cal Sans", sans-serif;
                color: #0d6efd;
                cursor: pointer;
            }
            #contenitore {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .form-box-container {
                padding: 2rem;
                background-color: white;
                border-radius: .5rem;
                box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
                width: 100%;
                max-width: 450px;
                margin-bottom: 1rem;
            }

            .alert {
                width: 100%;
                max-width: 450px;
            }
        </style>
    </head>

    <body>
        <div class="title-wrapper align-items-center">
            <h1 class="display-5 align-items-center">
                <?php echo $mostra_form_2fa ? "Verifica Codice 2FA" : "Login"; ?>
            </h1>
        </div>
        <div id="contenitore">
            <?php if (!empty($messaggio_output) && $mostra_form_2fa): ?>
                <div class="alert <?php echo htmlspecialchars($classe_alert); ?>" role="alert">
                    <?php echo htmlspecialchars($messaggio_output); ?>
                </div>
            <?php elseif (!empty($messaggio_login) && $mostra_form_login): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($messaggio_login); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center min-vh-100">
            <div class="container">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <div class="mb-3">
                        <label for="username_html_id" class="form-label">Username/E-mail</label>
                        <input type="text" class="form-control" id="username_html_id" name="username"
                            placeholder="Username o E-mail"
                            value="<?php echo htmlspecialchars($valore_username_ripopolamento); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password_html_id" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password_html_id" name="password"
                            placeholder="Password" required>
                    </div>
                    <button class="btn btn-primary w-100 py-2 mb-3" type="submit">Continua</button>
                    <div class="text-center">
                        <small><a href="dimenticata.php" class="form-text d-block mb-1">Password dimenticata?</a></small>
                        <small><a href="crea.html" class="form-text d-block">Creare un nuovo account?</a></small>
                    </div>
                    <p class="mt-auto mb-3 text-body-secondary text-center">&copy; <?php echo date("Y"); ?></p>
                </form>
            </div>
        </div>
        </div>
    </body>

    </html>
<?php endif; ?>

<?php if ($mostra_form_2fa): ?>

    <!DOCTYPE html>
    <html lang="it">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $mostra_form_2fa ? "Verifica Codice 2FA" : "Login"; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="index.css">
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Cal+Sans&display=swap');
            .title-wrapper {
                width: 100%;
                text-align: center;
                margin-bottom: 1rem;
            }
            a{
                font-size: 90%;
                font-family: "Cal Sans", sans-serif;
                color: #0d6efd;
                cursor: pointer;
            }
            .form-box-container {
                padding: 2rem;
                background-color: white;
                border-radius: .5rem;
                box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
                width: 100%;
                max-width: 450px;
                margin-bottom: 1rem;
            }

            #contenitore {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }

            .alert {
                width: 100%;
                max-width: 450px;
            }
        </style>
    </head>

    <body>
        <div class="title-wrapper align-items-center">
            <h1 class="display-5 align-items-center">
                <?php echo $mostra_form_2fa ? "Verifica Codice 2FA" : "Login"; ?>
            </h1>
        </div>
        <div id="contenitore">
            <?php if (!empty($messaggio_output) && $mostra_form_2fa): ?>
                <div class="alert <?php echo htmlspecialchars($classe_alert); ?>" role="alert">
                    <?php echo htmlspecialchars($messaggio_output); ?>
                </div>
            <?php elseif (!empty($messaggio_login) && $mostra_form_login): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($messaggio_login); ?>
                </div>
            <?php endif; ?>


            <div class="form-box-container <?php echo $form_box_dynamic_class; ?> d-flex align-items-center">
                <div class="container">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="mb-3">
                            <label for="codice_2fa_input_id" class="form-label">Codice di Verifica</label>
                            <input type="text" class="form-control" id="codice_2fa_input_id" name="codice_2fa_input_utente"
                                placeholder="Codice a 6 caratteri" inputmode="text" pattern="[0-9a-zA-Z]{6}"
                                title="Inserisci il codice a 6 caratteri ricevuto via email" required autofocus>
                        </div>
                        <button class="btn btn-success w-100 py-2" type="submit" name="bottone_submit_codice_2fa">Verifica
                            Codice</button>
                        <div class="text-center mt-3">
                            <small><a
                                    href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?reset_2fa=1&messaggio=Login+annullato."
                                    class="text-muted">Annulla e torna al login</a></small>
                        </div>
                        <p class="text-center mt-4 text-body-secondary">&copy; <?php echo date("Y"); ?></p>
                    </form>
                </div>
            </div>
        </div>

    </body>

    </html>
<?php endif; ?>

</body>

</html>
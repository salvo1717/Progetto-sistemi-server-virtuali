<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$percorso = __DIR__ . '/utenti.json';
$messaggio_utente = "";
$successo = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password_chiaro = isset($_POST['password']) ? $_POST['password'] : '';
    if (empty($nome) || empty($cognome) || empty($email) || empty($username) || empty($password_chiaro)) {
        $messaggio_utente = "Errore: Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messaggio_utente = "Errore: Formato email non valido.";
    } else {
        $password_hash = password_hash($password_chiaro, PASSWORD_DEFAULT);
        $nuovo_utente = [
            "nome" => $nome,
            "cognome" => $cognome,
            "email" => $email,
            "username" => $username,
            "password" => $password_hash,
            "data_registrazione" => date("Y-m-d H:i:s")
        ];
        $utenti_esistenti = [];
        if (file_exists($percorso) && filesize($percorso) > 0) {
            $contenuto_json_esistente = file_get_contents($percorso);
            $utenti_esistenti = json_decode($contenuto_json_esistente, true);
            if ($utenti_esistenti === null) {
                $utenti_esistenti = [];
            }
        }
        $utente_duplicato = false;
        foreach ($utenti_esistenti as $utente) {
            if ($utente['email'] === $email) {
                $messaggio_utente = "Errore: L'email è già registrata.";
                $utente_duplicato = true;
                break;
            }
        }
        if (!$utente_duplicato) {
            $utenti_esistenti[] = $nuovo_utente;
        }
        $json_da_scrivere = json_encode($utenti_esistenti, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($percorso, $json_da_scrivere)) {
            $messaggio_utente = "Account creato con successo!";
            $successo = true;
        } else {
            $messaggio_utente = "Errore del server: Impossibile salvare i dati. Verifica i permessi della directory 'data' e del file.";
            error_log("Errore scrittura file JSON: " . $percorso);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risultato Registrazione</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #f8f9fa; }
        .message-container { padding: 2rem; background-color: white; border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }
    </style>
</head>
<body>
    <div class="message-container text-center">
        <h1 class="<?php echo $successo ? 'text-success' : 'text-danger'; ?>">
            <?php echo htmlspecialchars($messaggio_utente); ?>
        </h1>
        <?php if ($successo): ?>
            <p>Ora puoi effettuare il login.</p>
            <a href="index.html" class="btn btn-primary mt-3">Torna al Login</a>
        <?php else: ?>
            <a href="crea.html" class="btn btn-warning mt-3">Torna alla Registrazione</a>
        <?php endif; ?>
    </div>
</body>
</html>
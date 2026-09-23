<?php
session_start();

$DB_HOST = 'localhost';
$DB_NAME = 'stockpilot';
$DB_USER = 'root';
$DB_PASS = '';
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'stockpilot.demo@gmail.com';
const SMTP_PASS = 'abcd efgh ijkl mnop';
const SMTP_FROM_NAME = 'Vectorys — StockPilot';
function sendMailSmtp(string $to, string $subject, string $bodyHtml, ?string &$error = null): bool
{
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $fromName = SMTP_FROM_NAME;

    $smtp = @fsockopen($host, $port, $errno, $errstr, 15);
    if (!$smtp) {
        $error = "Connexion SMTP impossible ($host:$port) : $errstr ($errno)";
        return false;
    }
    stream_set_timeout($smtp, 15);

    $readLine = function () use ($smtp) {
        $data = '';
        while (($str = fgets($smtp, 515)) !== false) {
            $data .= $str;
            if (isset($str[3]) && $str[3] === ' ') break; 
        }
        return $data;
    };
    $send = function (string $cmd) use ($smtp) { fwrite($smtp, $cmd . "\r\n"); };

    $readLine(); 
    $send('EHLO localhost'); $readLine();
    $send('STARTTLS'); $readLine();

    if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        $error = "Échec de la négociation TLS avec le serveur SMTP.";
        fclose($smtp);
        return false;
    }

    $send('EHLO localhost'); $readLine();
    $send('AUTH LOGIN'); $readLine();
    $send(base64_encode($user)); $readLine();
    $send(base64_encode($pass));
    $authResp = $readLine();
    if (strpos($authResp, '235') !== 0) {
        $error = "Authentification SMTP refusée. Vérifiez SMTP_USER / SMTP_PASS (mot de passe d'application Gmail) dans config.php.";
        fclose($smtp);
        return false;
    }

    $send("MAIL FROM:<$user>"); $readLine();
    $send("RCPT TO:<$to>"); $readLine();
    $send('DATA'); $readLine();

    $headers  = "From: $fromName <$user>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $safeBody = preg_replace('/^\./m', '..', $bodyHtml);

    $send($headers . "\r\n" . $safeBody . "\r\n.");
    $sendResp = $readLine();
    $send('QUIT'); $readLine();
    fclose($smtp);

    if (strpos($sendResp, '250') !== 0) {
        $error = "Le serveur SMTP a refusé l'envoi du message : " . trim($sendResp);
        return false;
    }
    return true;
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => "Connexion à la base de données impossible. Vérifiez que MySQL est démarré dans XAMPP et que la base 'stockpilot' existe (importez database.sql). Détail : " . $e->getMessage(),
    ]);
    exit;
}

const ROLE_CODE = [
    'Administrateur' => 'admin',
    'Responsable Achats' => 'achats',
    'Responsable Service' => 'service',
];

function requireAdmin(): void
{
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => "Accès réservé à l'administrateur."]);
        exit;
    }
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => "Veuillez vous connecter."]);
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => "Vous n'avez pas les droits nécessaires pour cette action."]);
        exit;
    }
}

function enregistrerAlerteStock(PDO $pdo, int $articleId, string $motif = ''): void
{
    $stmt = $pdo->prepare("SELECT nom, cat, stock, min FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $art = $stmt->fetch();
    if (!$art) return;

    $stock = (int) $art['stock'];
    $min = (int) $art['min'];
    if ($stock > $min) return; 

    $niveau = $stock <= 0 ? 'critique' : 'bas';
    $ins = $pdo->prepare(
        "INSERT INTO alertes_historique (article_id, article_nom, cat, stock, min_seuil, niveau, date_alerte, motif)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ins->execute([$articleId, $art['nom'], $art['cat'], $stock, $min, $niveau, date('d/m/Y H:i'), $motif]);
}

function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function autoValiderDemandesEnAttente(PDO $pdo, string $userNom, ?int $articleId = null): void
{
    $dateNow = date('d/m/Y H:i');

    if ($articleId !== null) {
        $pendingStmt = $pdo->prepare(
            "SELECT DISTINCT d.num, d.user_id, d.service, d.demandeur
             FROM demandes d
             JOIN demande_lignes dl ON dl.demande_num = d.num
             WHERE d.statut = 'attente' AND dl.article_id = ?
             ORDER BY d.id ASC"
        );
        $pendingStmt->execute([$articleId]);
    } else {
        $pendingStmt = $pdo->query("SELECT num, user_id, service, demandeur FROM demandes WHERE statut = 'attente' ORDER BY id ASC");
    }
    $pending = $pendingStmt->fetchAll();

    $lignesStmt = $pdo->prepare(
        "SELECT dl.article_id, dl.qte, a.nom AS art_nom, a.stock
         FROM demande_lignes dl
         JOIN articles a ON a.id = dl.article_id
         WHERE dl.demande_num = ?
         FOR UPDATE"
    );
    $updArt = $pdo->prepare("UPDATE articles SET stock = stock - ? WHERE id = ?");
    $insHist = $pdo->prepare("INSERT INTO historique (date_mvt, type, art, qte, user, motif, ref_num, ref_type, service) VALUES (?, 'sortie', ?, ?, ?, ?, ?, 'demande', ?)");
    $updDem = $pdo->prepare("UPDATE demandes SET statut = 'validee', notif_lue = 0 WHERE num = ?");

    foreach ($pending as $p) {
        $pendingNum = $p['num'];

        $lignesStmt->execute([$pendingNum]);
        $lignes = $lignesStmt->fetchAll();
        if (!$lignes) continue;

        $suffisant = true;
        foreach ($lignes as $l) {
            if ((int) $l['stock'] < (int) $l['qte']) { $suffisant = false; break; }
        }
        if (!$suffisant) continue;

        foreach ($lignes as $l) {
            $updArt->execute([$l['qte'], $l['article_id']]);
            $insHist->execute([$dateNow, $l['art_nom'], $l['qte'], $p['demandeur'], "Sortie suite validation automatique de la demande $pendingNum (stock réapprovisionné)", $pendingNum, $p['service']]);
            enregistrerAlerteStock($pdo, $l['article_id'], "Sortie suite validation automatique de la demande $pendingNum (stock réapprovisionné)");
        }
        $updDem->execute([$pendingNum]);
    }
}

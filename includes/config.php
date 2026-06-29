<?php
// ============================================
// OMEGA TECH AUTO - Configuration Principale
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'omega_auto');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Omega Tech Auto');
define('APP_VERSION', '1.0');
define('APP_URL', 'http://localhost:8080');
define('UPLOAD_DIR', __DIR__ . '/../uploads/vehicles/');
define('UPLOAD_URL', APP_URL . '/uploads/vehicles/');
define('DEFAULT_CURRENCY', 'FCFA');

// Connexion PDO singleton
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="padding:20px;color:red;font-family:sans-serif">
                <h2>Erreur de connexion à la base de données</h2>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Vérifiez que MariaDB est démarré: <code>service mariadb start</code></p>
            </div>');
        }
    }
    return $pdo;
}

function formatPrice(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' ' . DEFAULT_CURRENCY;
}

function generateRef(string $prefix): string {
    $db = getDB();
    $table = ($prefix === 'OTA') ? 'vehicles' : (($prefix === 'LOC') ? 'rentals' : 'sales');
    $col = 'reference';
    do {
        $ref = $prefix . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $st = $db->prepare("SELECT id FROM $table WHERE $col = ?");
        $st->execute([$ref]);
    } while ($st->fetch());
    return $ref;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function statusBadge(string $status): string {
    $map = [
        'disponible' => ['label' => 'Disponible', 'class' => 'badge-success'],
        'loue'       => ['label' => 'Loué',       'class' => 'badge-warning'],
        'vendu'      => ['label' => 'Vendu',       'class' => 'badge-dark'],
        'maintenance'=> ['label' => 'Maintenance', 'class' => 'badge-danger'],
        'en_cours'   => ['label' => 'En cours',    'class' => 'badge-info'],
        'termine'    => ['label' => 'Terminé',     'class' => 'badge-success'],
        'annule'     => ['label' => 'Annulé',      'class' => 'badge-danger'],
        'complete'   => ['label' => 'Complète',    'class' => 'badge-success'],
    ];
    $d = $map[$status] ?? ['label' => $status, 'class' => 'badge-secondary'];
    return '<span class="badge ' . $d['class'] . '">' . $d['label'] . '</span>';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

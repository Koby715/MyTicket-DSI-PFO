<?php
/**
 * Configuration de la connexion à la base de données
 * Utilise PDO pour une connexion sécurisée
 */

// Charger le fichier .env si disponible
$envFile = dirname(__DIR__) . '/.env';
$envVars = [];

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
}

// Paramètres de connexion (Priorité aux variables d'env, puis valeurs par défaut)
define('DB_HOST', $envVars['DB_HOST'] ?? 'localhost');
define('DB_NAME', $envVars['DB_NAME'] ?? 'tickets_bd');
define('DB_USER', $envVars['DB_USER'] ?? 'root');
define('DB_PASS', $envVars['DB_PASS'] ?? '');
define('DB_CHARSET', $envVars['DB_CHARSET'] ?? 'utf8mb4');

try {
    // Créer la connexion PDO
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        )
    );
} catch (PDOException $e) {
    // En cas d'erreur
    http_response_code(500);
    error_log("Erreur de connexion DB: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données'
    ]);
    exit;
}
?>

<?php
/**
 * email-config.php
 * Charge et expose la configuration email selon l'environnement
 */

// Charger le fichier .env
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    throw new Exception('Fichier .env non trouvé à: ' . $envFile . '. Veuillez créer le fichier .env à partir de .env.example');
}

// Charger les variables d'environnement
$envVars = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    // Ignorer les commentaires
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    
    // Parser la variable
    if (strpos($line, '=') !== false) {
        [$key, $value] = explode('=', $line, 2);
        $envVars[trim($key)] = trim($value);
    }
}

// Déterminer l'environnement
$environment = $envVars['MAIL_ENV'] ?? 'development';

// Sélectionner la configuration appropriée
if ($environment === 'production') {
    $mailConfig = [
        'host'     => $envVars['EXCHANGE_SMTP_HOST'] ?? 'webmail19.hosteam.fr',
        'port'     => intval($envVars['EXCHANGE_SMTP_PORT'] ?? 587),
        'security' => $envVars['EXCHANGE_SMTP_SECURITY'] ?? 'tls',
        'from_email' => $envVars['EXCHANGE_FROM_EMAIL'] ?? 'serviceinfo@pfo-construction.com',
        'from_name'  => $envVars['EXCHANGE_FROM_NAME'] ?? 'Service Info PFO Construction',
        'username'   => $envVars['EXCHANGE_USERNAME'] ?? 'serviceinfo@pfo-construction.com',
        'password'   => $envVars['EXCHANGE_PASSWORD'] ?? '',
    ];
} else {
    // Mode développement (Gmail)
    $mailConfig = [
        'host'     => $envVars['GMAIL_SMTP_HOST'] ?? 'smtp.gmail.com',
        'port'     => intval($envVars['GMAIL_SMTP_PORT'] ?? 587),
        'security' => $envVars['GMAIL_SMTP_SECURITY'] ?? 'tls',
        'from_email' => $envVars['GMAIL_FROM_EMAIL'] ?? 'akobeangerichard@gmail.com',
        'from_name'  => $envVars['GMAIL_FROM_NAME'] ?? 'Support Tickets PFO',
        'username'   => $envVars['GMAIL_USERNAME'] ?? 'akobeangerichard@gmail.com',
        'password'   => $envVars['GMAIL_PASSWORD'] ?? '',
    ];
}

// Ajouter les paramètres généraux
$mailConfig['app_url'] = $envVars['APP_URL'] ?? 'http://localhost/Ticket';
$mailConfig['reply_to'] = $envVars['MAIL_REPLY_TO'] ?? 'serviceinfo@pfo-construction.com';
$mailConfig['environment'] = $environment;

return $mailConfig;
?>

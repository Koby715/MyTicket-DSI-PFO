<?php
require_once 'config/db.php';
try {
    // 1. Table notification_templates
    $pdo->exec("CREATE TABLE IF NOT EXISTS notification_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_name VARCHAR(100) NOT NULL UNIQUE,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Insertion des templates par défaut si vides
    $check = $pdo->query("SELECT COUNT(*) FROM notification_templates");
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO notification_templates (event_name, subject, body) VALUES (?, ?, ?)");
        $stmt->execute(['ticket_created', 'Confirmation de création de ticket - {reference}', 'Bonjour {customer_name}, votre ticket {reference} a bien été créé.']);
        $stmt->execute(['status_changed', 'Mise à jour de votre ticket - {reference}', 'Bonjour {customer_name}, le statut de votre ticket {reference} est passé à {status}.']);
        $stmt->execute(['agent_assigned', 'Nouveau ticket assigné - {reference}', 'Un nouveau ticket {reference} vous a été assigné.']);
    }

    // 2. Table sla_settings
    $pdo->exec("CREATE TABLE IF NOT EXISTS sla_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        priority_id INT NOT NULL UNIQUE,
        response_time INT NOT NULL, -- en heures
        resolution_time INT NOT NULL, -- en heures
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // 3. Table system_settings (pour l'expiration auto)
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        description TEXT
    )");

    // Insertion des paramètres par défaut
    $pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES 
        ('auto_close_days', '7', 'Nombre de jours d\'inactivité avant fermeture automatique'),
        ('auto_close_enabled', '1', 'Activation de la fermeture automatique (1=oui, 0=non)'),
        ('auto_close_status_from', '3', 'ID du statut d\'origine (ex: En attente)'),
        ('auto_close_status_to', '5', 'ID du statut final (ex: Fermé)')");

    echo "Tables créées avec succès.";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

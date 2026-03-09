<?php
/**
 * cron-process-emails.php
 * Script en arrière-plan pour traiter la file d'attente des emails.
 * Peut être appelé par une tâche planifiée (CRON) toutes les minutes.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/notification-helper.php';

// Limiter le nombre d'emails par passage pour éviter les timeouts
$limit = 10;

try {
    // 1. Récupérer les emails en attente ou en échec (avec moins de 3 tentatives)
    $stmt = $pdo->prepare("SELECT * FROM email_queue WHERE status IN ('pending', 'failed') AND attempts < 3 ORDER BY created_at ASC LIMIT " . (int)$limit);
    $stmt->execute();
    $emails = $stmt->fetchAll();

    if (empty($emails)) {
        // Rien à traiter
        exit;
    }

    foreach ($emails as $email) {
        $id = $email['id'];
        $to = $email['recipient'];
        $cc = $email['cc_recipient'];
        $subject = $email['subject'];
        $body = $email['body'];
        $attachments = $email['attachments'] ? json_decode($email['attachments'], true) : [];
        
        // Préparer les données pour callEmailAPI qui gère maintenant le CC via le tableau data
        $payload = [
            'files' => $attachments,
            'cc' => $cc
        ];

        // Marquer comme "en cours" (optionnel, ici on incrémente juste les tentatives)
        $pdo->prepare("UPDATE email_queue SET attempts = attempts + 1 WHERE id = ?")->execute([$id]);

        // 2. Appeler l'API d'envoi
        $success = callEmailAPI($to, $subject, $body, $payload);

        if ($success) {
            // 3. Succès
            $stmt = $pdo->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW(), last_error = NULL WHERE id = ?");
            $stmt->execute([$id]);
        } else {
            // 4. Échec
            $stmt = $pdo->prepare("UPDATE email_queue SET status = 'failed', last_error = 'API Call Failed' WHERE id = ?");
            $stmt->execute([$id]);
        }
    }

} catch (Throwable $e) {
    error_log("Erreur Worker Email: " . $e->getMessage());
}
?>

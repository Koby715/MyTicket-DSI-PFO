<?php
/**
 * notification-helper.php
 * Fonctions partagées pour l'envoi de notifications par email
 */

/**
 * Envoie un email de changement de statut via l'API Outlook locale
 */
function sendStatusUpdateEmail($ticket_id, $pdo) {
    try {
        // 1. Récupérer les informations du ticket, du client et du statut
        $stmt = $pdo->prepare("
            SELECT t.reference, t.nom, t.email, t.subject, t.token, s.label as status_label
            FROM tickets t
            LEFT JOIN statuses s ON t.status_id = s.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) return false;

        $apiUrl = 'http://127.0.0.1:8000/send-mail';
        $emailConfig = require(__DIR__ . '/../config/email-config.php');
        $ticketLink = $emailConfig['app_url'] . '/DashBoard/php/liste-tickets-user.php?token=' . urlencode($ticket['token']);
        
        $statusLabel = $ticket['status_label'];
        $color = "#0066cc"; // Bleu par défaut
        $icon = "ℹ️";
        
        if (strtolower($statusLabel) === 'résolu') {
            $color = "#28a745"; // Vert
            $icon = "✅";
        } elseif (strtolower($statusLabel) === 'en cours') {
            $color = "#ffc107"; // Jaune/Orange
            $icon = "🚀";
        }

        // 2. Construction du corps HTML
        $mailBody = "<!DOCTYPE html><html><body style='font-family: Arial, sans-serif; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
            <div style='background: $color; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                <h2 style='margin:0;'>$icon Mise à jour : $statusLabel</h2>
                <p style='margin:5px 0 0 0;'>Ticket #{$ticket['reference']}</p>
            </div>
            <div style='padding: 20px;'>
                <p>Bonjour <strong>{$ticket['nom']}</strong>,</p>
                <p>Nous vous informons que votre ticket a évolué. Son nouveau statut est désormais : <strong style='color: $color;'>$statusLabel</strong>.</p>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin:0;'><strong>Objet :</strong> {$ticket['subject']}</p>
                </div>
                <p style='text-align: center; margin-top: 30px;'>
                    <a href='$ticketLink' style='background: $color; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Voir le suivi en temps réel</a>
                </p>
                <p style='font-size: 13px; color: #666; margin-top: 25px;'>Notre équipe continue de traiter votre demande. Vous serez notifié à chaque étape importante.</p>
            </div>
            <div style='font-size: 11px; color: #999; text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;'>
                Ceci est un message automatique du Service Support PFO Construction.
            </div>
        </div>
        </body></html>";

        // 3. Construction du Payload JSON
        $data = [
            'to' => $ticket['email'],
            'subject' => "Évolution de votre ticket [{$ticket['reference']}] : $statusLabel",
            'body' => $mailBody,
            'is_html' => true
        ];

        // 4. Appel cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout court pour ne pas bloquer l'UX
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode == 200);
    } catch (Exception $e) {
        error_log("Erreur notification statut: " . $e->getMessage());
        return false;
    }
}

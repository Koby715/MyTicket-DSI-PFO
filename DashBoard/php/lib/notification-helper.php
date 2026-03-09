<?php
/**
 * notification-helper.php
 * Fonctions partagées pour l'envoi de notifications par email
 */

/**
 * Appel à l'API locale (Python) pour l'envoi d'email
 */
function callEmailAPI($to, $subject, $body, $attachments = []) {
    // Utiliser la configuration pour l'URL de l'API et la clé (sécurisée via .env)
    try {
        $emailConfig = require(__DIR__ . '/../config/email-config.php');
        $apiUrl = $emailConfig['mail_api_url'] ?? 'http://127.0.0.1:8000/send-mail';
        $apiKey = $emailConfig['mail_api_key'] ?? null;

        $data = [
            'to' => $to,
            'cc' => $attachments['cc'] ?? null, // On passe le CC s'il existe dans le tableau de données
            'subject' => $subject,
            'body' => $body,
            'is_html' => true,
            'attachments' => isset($attachments['files']) ? $attachments['files'] : $attachments
        ];

        $maxAttempts = 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $headers = ['Content-Type: application/json'];
            if (!empty($apiKey)) {
                $headers[] = 'Authorization: Bearer ' . $apiKey;
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10 + ($attempt - 1) * 5);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlErrno) {
                error_log("callEmailAPI: cURL error (attempt $attempt): $curlErrno - $curlError");
            }

            if ($httpCode === 200) {
                return true;
            }

            // Log response for diagnostics
            error_log("callEmailAPI: HTTP $httpCode (attempt $attempt) response: " . substr((string)$result, 0, 2000));

            // Backoff before next attempt (simple linear backoff)
            if ($attempt < $maxAttempts) {
                sleep(1 * $attempt);
            }
        }

        return false;
    } catch (Throwable $e) {
        error_log("Erreur critique appel API Email: " . $e->getMessage());
        return false;
    }
}

/**
 * Ajoute un email à la file d'attente
 */
function enqueueEmail($to, $subject, $body, $attachments = [], $cc = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO email_queue (recipient, cc_recipient, subject, body, attachments, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $attachmentsJson = !empty($attachments) ? json_encode($attachments) : null;
        return $stmt->execute([$to, $cc, $subject, $body, $attachmentsJson]);
    } catch (Throwable $e) {
        // Erreur probable : table manquante ou problème PDO. Log et retourner false.
        error_log("Erreur ajout file attente email: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un email de changement de statut via l'API Locale
 */
function sendStatusUpdateEmail($ticket_id, $pdo) {
    try {
        // 1. Récupérer les informations du ticket, du client, de l'agent assigné et du statut
        $stmt = $pdo->prepare("
            SELECT t.reference, t.nom AS customer_name, t.email AS customer_email, t.subject, t.token, t.assigned_to, s.label as status_label, s.code as status_code
            FROM tickets t
            LEFT JOIN statuses s ON t.status_id = s.id
            WHERE t.id = ?
        ");
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) return false;

        $emailConfig = require(__DIR__ . '/../config/email-config.php');

        $ticketLink = $emailConfig['app_url'] . '/DashBoard/php/liste-tickets-user.php?token=' . urlencode($ticket['token']);

        $statusLabel = $ticket['status_label'];
        $statusCode = $ticket['status_code'] ?? '';
        $color = "#0066cc"; // Bleu par défaut
        $icon = "ℹ️";
        
        if (strtolower($statusLabel) === 'résolu' || strtolower($statusCode) === 'resolved') {
            $color = "#28a745"; // Vert
            $icon = "✅";
        } elseif (strtolower($statusLabel) === 'en cours' || strtolower($statusCode) === 'in_progress') {
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
                <p>Bonjour <strong>{$ticket['customer_name']}</strong>,</p>
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

        $mailSubject = "Évolution de votre ticket [{$ticket['reference']}] : $statusLabel";

        // Déterminer les destinataires :
        $toEmail = $ticket['customer_email'];
        $ccEmail = null;

        // S'il y a un agent assigné, le mettre en Cc de la notification
        if (!empty($ticket['assigned_to'])) {
            // Récupérer l'email de l'agent
            $stmtAgent = $pdo->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
            $stmtAgent->execute([$ticket['assigned_to']]);
            $agentEmail = $stmtAgent->fetchColumn();
            
            if ($agentEmail && filter_var($agentEmail, FILTER_VALIDATE_EMAIL)) {
                // On met l'agent en Cc s'il est différent du client (cas rare mais possible)
                if (strtolower($agentEmail) !== strtolower($toEmail)) {
                    $ccEmail = $agentEmail;
                }
            }
        }

        // 3. Ajout à la file d'attente (Asynchrone)
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("sendStatusUpdateEmail: adresse email client invalide={$toEmail}");
            return false;
        }

        // Ajout direct à la table email_queue avec le champ CC
        $queued = enqueueEmail($toEmail, $mailSubject, $mailBody, [], $ccEmail);
        if (!$queued) {
            error_log("sendStatusUpdateEmail: enqueue failed for {$toEmail}");
        }

        return true;
        
    } catch (Throwable $e) {
        error_log("Erreur notification statut: " . $e->getMessage());
        return false;
    }
}

/**
 * Rendu des templates d'email avec Bootstrap CSS inline
 * 
 * @param string $templateBody - Contenu du template depuis la BD
 * @param array $variables - Variables à remplacer {key} => value
 * @param string $appUrl - URL de l'application (non utilisée actuellement)
 * @return string - HTML complet prêt à envoyer
 */
function renderEmailTemplate($templateBody, $variables = [], $appUrl = '') {
    // 1. Remplacer les variables dynamiques
    foreach ($variables as $key => $value) {
        $placeholder = '{' . $key . '}';
        $templateBody = str_replace($placeholder, htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $templateBody);
    }
    
    // 2. Wrapper HTML avec Bootstrap CSS inline
    $htmlTemplate = <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification PFO DSI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            background-color: #f5f5f5;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #0066cc 0%, #004499 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 4px solid #004499;
        }
        .email-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .email-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        .email-body {
            padding: 30px 25px;
        }
        .email-body h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #0066cc;
        }
        .email-body p {
            margin-bottom: 15px;
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .alert-info {
            background-color: #e3f2fd;
            border-color: #0066cc;
            color: #004499;
        }
        .alert-success {
            background-color: #e8f5e9;
            border-color: #28a745;
            color: #1b5e20;
        }
        .alert-warning {
            background-color: #fff3e0;
            border-color: #ffc107;
            color: #e65100;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 20px 0;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #004499;
        }
        .btn-block {
            display: block;
            text-align: center;
            width: 100%;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px 25px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999;
        }
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 20px 0;
        }
        .info-box {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border-left: 4px solid #0066cc;
        }
        .info-box strong {
            color: #0066cc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #0066cc;
        }
        table td {
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>PFO DSI - Support Client</h1>
            <p>Gestion des tickets de support</p>
        </div>
        
        <div class="email-body">
            {TEMPLATE_BODY}
        </div>
        
        <div class="footer">
            <p><strong>Service Support PFO Construction</strong></p>
            <p>Cet email a été généré automatiquement. Veuillez ne pas répondre directement à ce message.</p>
            <p style="margin-top: 10px; color: #ccc;">© 2026 PFO DSI. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;

    // 3. Injecter le contenu du template
    $htmlTemplate = str_replace('{TEMPLATE_BODY}', $templateBody, $htmlTemplate);
    
    return $htmlTemplate;
}

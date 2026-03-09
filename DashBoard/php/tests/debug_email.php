<?php
// Script de test détaillé pour sendStatusUpdateEmail
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/notification-helper.php';

$ticket_id = intval($argv[1] ?? 31);

echo "=== Test détaillé de sendStatusUpdateEmail ===\n";
echo "Ticket ID: $ticket_id\n\n";

try {
    // Récupérer le ticket
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo "Ticket non trouvé\n";
        exit(1);
    }

    echo "Ticket info:\n";
    echo "  ID: {$ticket['id']}\n";
    echo "  Ref: {$ticket['reference']}\n";
    echo "  Status ID: {$ticket['status_id']}\n";
    echo "  Assigned to: {$ticket['assigned_to']}\n";
    echo "  Email: {$ticket['email']}\n\n";

    // Appeler sendStatusUpdateEmail
    echo "Appel sendStatusUpdateEmail()...\n";
    $result = sendStatusUpdateEmail($ticket_id, $pdo);
    
    echo "Résultat: " . ($result ? 'true' : 'false') . "\n\n";

    // Vérifier email_queue
    echo "Contenu email_queue:\n";
    $qstmt = $pdo->prepare("SELECT id, recipient, status, created_at FROM email_queue WHERE recipient = ? ORDER BY created_at DESC LIMIT 5");
    $qstmt->execute([$ticket['email']]);
    $queued = $qstmt->fetchAll();
    
    if (empty($queued)) {
        echo "  (aucune entrée pour {$ticket['email']})\n";
    } else {
        foreach ($queued as $q) {
            echo "  ID: {$q['id']}, Status: {$q['status']}, Created: {$q['created_at']}\n";
        }
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

echo "\nFin du test\n";

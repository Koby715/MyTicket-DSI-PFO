<?php
// Script de test CLI pour simuler assignation et résolution d'un ticket
// Usage: php run_email_tests.php <ticket_id>

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/notification-helper.php';

if ($argc < 2) {
    echo "Usage: php run_email_tests.php <ticket_id>\n";
    exit(1);
}

$ticket_id = intval($argv[1]);
if ($ticket_id <= 0) {
    echo "Ticket id invalide\n";
    exit(1);
}

echo "Testing ticket id: $ticket_id\n";

try {
    // 1) Simuler assignation -> set status to in_progress and assigned_to to an existing agent (user id 2 if exists)
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM statuses WHERE code = 'in_progress' LIMIT 1");
    $stmt->execute();
    $inProgressId = $stmt->fetchColumn();

    if (!$inProgressId) {
        throw new Exception('Status in_progress introuvable');
    }

    // assign to user id 2 if exists, else leave null
    $agentId = 2;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$agentId]);
    if (!$stmt->fetch()) {
        $agentId = null;
    }

    $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ?, status_id = ? WHERE id = ?");
    $stmt->execute([$agentId, $inProgressId, $ticket_id]);
    $pdo->commit();

    echo "Ticket updated: assigned_to={$agentId}, status=in_progress\n";

    // Appeler la fonction d'envoi de notification (assignation)
    $res = sendStatusUpdateEmail($ticket_id, $pdo);
    echo "sendStatusUpdateEmail (assign) returned: " . ($res ? 'true' : 'false') . "\n";

    // 2) Simuler résolution -> set status to resolved
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM statuses WHERE code = 'resolved' LIMIT 1");
    $stmt->execute();
    $resolvedId = $stmt->fetchColumn();
    if (!$resolvedId) {
        throw new Exception('Status resolved introuvable');
    }
    $stmt = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
    $stmt->execute([$resolvedId, $ticket_id]);
    $pdo->commit();

    echo "Ticket updated: status=resolved\n";

    $res2 = sendStatusUpdateEmail($ticket_id, $pdo);
    echo "sendStatusUpdateEmail (resolved) returned: " . ($res2 ? 'true' : 'false') . "\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Erreur test: " . $e->getMessage() . "\n";
    error_log("run_email_tests error: " . $e->getMessage());
    exit(1);
}

echo "Tests terminés\n";

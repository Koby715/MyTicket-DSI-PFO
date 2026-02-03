<?php
/**
 * assign-ticket.php
 * Script de traitement pour l'assignation des tickets
 */

session_start();
require_once 'config/db.php';
require_once 'lib/notification-helper.php';

header('Content-Type: application/json');

// Protection : Seuls les Admins et Superviseurs peuvent assigner
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée.']);
    exit;
}

if (!in_array($_SESSION['admin_role'], ['ADMIN', 'SUPERVISOR'])) {
    echo json_encode(['success' => false, 'message' => 'Droits insuffisants pour assigner des tickets.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;

    if ($ticket_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de ticket invalide.']);
        exit;
    }

    try {
        // Si agent_id est 0, on pourrait imaginer une désassignation (optionnel)
        // Mais ici on valide que l'agent existe si agent_id > 0
        if ($agent_id > 0) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$agent_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Agent introuvable.']);
                exit;
            }
        }

        // Mise à jour de l'assignation
        // On met aussi à jour updated_at pour montrer l'activité
        $val_agent = ($agent_id > 0) ? $agent_id : null;
        $stmt = $pdo->prepare("UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$val_agent, $ticket_id]);

        // Optionnel : Changer le statut à "En cours" si c'était "Nouveau"
        $stmtStatus = $pdo->prepare("SELECT status_id FROM tickets WHERE id = ?");
        $stmtStatus->execute([$ticket_id]);
        $current_status = $stmtStatus->fetchColumn();

        // Récupérer l'ID du statut 'new' et 'in_progress'
        $stmtNew = $pdo->prepare("SELECT id FROM statuses WHERE code = 'new' LIMIT 1");
        $stmtNew->execute();
        $idNew = $stmtNew->fetchColumn();

        $stmtProgress = $pdo->prepare("SELECT id FROM statuses WHERE code = 'in_progress' LIMIT 1");
        $stmtProgress->execute();
        $idProgress = $stmtProgress->fetchColumn();

        if ($idNew && $idProgress && $current_status == $idNew && $val_agent !== null) {
            $updateStatus = $pdo->prepare("UPDATE tickets SET status_id = ? WHERE id = ?");
            $updateStatus->execute([$idProgress, $ticket_id]);
            
            // Envoyer la notification email
            sendStatusUpdateEmail($ticket_id, $pdo);
        }

        echo json_encode(['success' => true, 'message' => 'Ticket assigné avec succès.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
}

<?php
/**
 * quick-ticket-actions.php
 * Gère les actions rapides sur les tickets (Mettre en attente, Résoudre, Supprimer)
 */

session_start();
require_once 'config/db.php';
require_once 'lib/notification-helper.php';

header('Content-Type: application/json');

// 1. Protection : Vérifier la session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'];

// 2. Vérifier les paramètres
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;

if ($ticket_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de ticket invalide.']);
    exit;
}

try {
    // Vérifier l'existence du ticket (récupère aussi l'assignation pour contrôle des droits)
    $stmt = $pdo->prepare("SELECT reference, assigned_to FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket introuvable.']);
        exit;
    }

    switch ($action) {
        case 'hold':
            // Accessible à tous les rôles connectés, MAIS un AGENT ne peut mettre en attente que ses propres tickets
            // Si AGENT, vérifier qu'il est bien assigné
            if ($admin_role === 'AGENT') {
                if (intval($ticket['assigned_to']) !== intval($admin_id)) {
                    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez mettre en attente que les tickets qui vous sont assignés.']);
                    exit;
                }
            }

            // Récupérer l'ID du statut 'En attente'
            $stmtStatus = $pdo->prepare("SELECT id FROM statuses WHERE label = 'En attente' LIMIT 1");
            $stmtStatus->execute();
            $status_id = $stmtStatus->fetchColumn();

            if (!$status_id) {
                echo json_encode(['success' => false, 'message' => 'Statut "En attente" non configuré.']);
                exit;
            }

            $update = $pdo->prepare("UPDATE tickets SET status_id = ?, updated_at = NOW() WHERE id = ?");
            $update->execute([$status_id, $ticket_id]);
            
            // Envoyer la notification email (Optionnel pour En attente, mais utile)
            sendStatusUpdateEmail($ticket_id, $pdo);
            
            echo json_encode(['success' => true, 'message' => 'Ticket #' . $ticket['reference'] . ' mis en attente.']);
            break; 

        case 'resolve':
            // Accessible à tous les rôles connectés, MAIS un AGENT ne peut résoudre que ses propres tickets
            // Si AGENT, vérifier qu'il est bien assigné
            if ($admin_role === 'AGENT') {
                if (intval($ticket['assigned_to']) !== intval($admin_id)) {
                    echo json_encode(['success' => false, 'message' => 'Vous ne pouvez résoudre que les tickets qui vous sont assignés.']);
                    exit;
                }
            }

            // Récupérer l'ID du statut 'Résolu'
            $stmtStatus = $pdo->prepare("SELECT id FROM statuses WHERE label = 'Résolu' LIMIT 1");
            $stmtStatus->execute();
            $status_id = $stmtStatus->fetchColumn();

            if (!$status_id) {
                echo json_encode(['success' => false, 'message' => 'Statut "Résolu" non configuré.']);
                exit;
            }

            $update = $pdo->prepare("UPDATE tickets SET status_id = ?, updated_at = NOW() WHERE id = ?");
            $update->execute([$status_id, $ticket_id]);

            // Envoyer la notification email
            sendStatusUpdateEmail($ticket_id, $pdo);

            echo json_encode(['success' => true, 'message' => 'Ticket #' . $ticket['reference'] . ' marqué comme résolu.']);
            break;

        case 'delete':
            // RÉSERVÉ À L'ADMIN UNIQUEMENT
            if ($admin_role !== 'ADMIN') {
                echo json_encode(['success' => false, 'message' => 'Action réservée à l\'administrateur.']);
                exit;
            }

            // Suppression (On pourrait faire une suppression logique, mais ici on fait physique car demandé)
            $pdo->beginTransaction();
            
            // Supprimer les pièces jointes d'abord (contraintes FK potentielles)
            $delAtt = $pdo->prepare("DELETE FROM attachments WHERE ticket_id = ?");
            $delAtt->execute([$ticket_id]);
            
            // Supprimer le ticket
            $delTkt = $pdo->prepare("DELETE FROM tickets WHERE id = ?");
            $delTkt->execute([$ticket_id]);
            
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Ticket #' . $ticket['reference'] . ' supprimé avec succès.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue.']);
            break;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
}

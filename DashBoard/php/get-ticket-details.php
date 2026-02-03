<?php
/**
 * get-ticket-details.php
 * Récupère les détails d'un ticket spécifique pour l'administration
 */

session_start();
require_once 'config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Protection : Seuls les admins connectés
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de ticket invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               c.name as category_name, 
               p.name as priority_name, 
               s.label as status_label,
               u.name as assigned_name
        FROM tickets t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN priorities p ON t.priority_id = p.id
        LEFT JOIN statuses s ON t.status_id = s.id
        LEFT JOIN users u ON t.assigned_to = u.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket introuvable']);
        exit;
    }

    // Récupérer les pièces jointes
    $stmt = $pdo->prepare("SELECT id, file_name, file_path, uploaded_at FROM attachments WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ticket['attachments_list'] = $attachments;

    echo json_encode(['success' => true, 'data' => $ticket]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
}

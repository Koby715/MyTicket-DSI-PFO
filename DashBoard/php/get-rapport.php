<?php
/**
 * get-rapport.php
 * Récupère le rapport de résolution d'un ticket
 */

session_start();
require_once 'config/db.php';

header('Content-Type: application/json; charset=utf-8');

// === SÉCURITÉ ===
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'];
$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de ticket invalide']);
    exit;
}

try {
    // === VÉRIFIER LE TICKET ===
    $stmt = $pdo->prepare("
        SELECT id, assigned_to
        FROM tickets
        WHERE id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket introuvable']);
        exit;
    }

    // === VÉRIFIER LES DROITS ===
    // Seuls ADMIN/SUPERVISOR et l'agent assigné peuvent voir le rapport
    if ($admin_role === 'AGENT' && intval($ticket['assigned_to']) !== intval($admin_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit;
    }

    // === RÉCUPÉRER LE RAPPORT ===
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.ticket_id,
            r.report_content,
            r.agent_id,
            u.name as agent_name,
            r.created_at,
            r.updated_at
        FROM rapports r
        LEFT JOIN users u ON r.agent_id = u.id
        WHERE r.ticket_id = ?
    ");
    $stmt->execute([$ticket_id]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rapport) {
        // Aucun rapport n'existe
        echo json_encode([
            'success' => true,
            'data' => null
        ]);
        exit;
    }

    // === RÉCUPÉRER LES PIÈCES JOINTES DU RAPPORT ===
    $stmt = $pdo->prepare("
        SELECT id, file_name, file_path, uploaded_at
        FROM attachments
        WHERE report_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$rapport['id']]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rapport['attachments'] = $attachments;

    echo json_encode([
        'success' => true,
        'data' => $rapport
    ]);

} catch (PDOException $e) {
    error_log("Erreur get-rapport.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}

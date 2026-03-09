<?php
/**
 * get-dashboard-stats.php
 * Retourne les statistiques KPI en format JSON pour mise à jour dynamique
 */

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$stats = [];

try {
    // 📂 Tickets ouverts
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id IN (SELECT id FROM statuses WHERE label IN ('Nouveau', 'En cours', 'En attente'))");
    $stats['open'] = (int)$stmt->fetchColumn();

    // 🕒 En attente utilisateur
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id IN (SELECT id FROM statuses WHERE label = 'En attente')");
    $stats['pending_user'] = (int)$stmt->fetchColumn();

    // ✅ Résolus aujourd'hui
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status_id IN (SELECT id FROM statuses WHERE label = 'Résolu') AND DATE(updated_at) = CURDATE()");
    $stats['resolved_today'] = (int)$stmt->fetchColumn();

    // 🔴 Tickets urgents
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE priority_id IN (SELECT id FROM priorities WHERE name IN ('Urgent', 'Critique', 'Haute', 'Élevée')) AND status_id NOT IN (SELECT id FROM statuses WHERE label IN ('Résolu', 'Fermé'))");
    $stats['urgent'] = (int)$stmt->fetchColumn();

    // 👤 Tickets assignés à moi
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE assigned_to = ? AND status_id NOT IN (SELECT id FROM statuses WHERE label IN ('Résolu', 'Fermé'))");
    $stmt->execute([$admin_id]);
    $stats['assigned_to_me'] = (int)$stmt->fetchColumn();

    echo json_encode(['success' => true, 'stats' => $stats]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

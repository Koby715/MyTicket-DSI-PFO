<?php
/**
 * get-priorities.php
 * Récupère les priorités de la base de données
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';

try {
    $stmt = $pdo->query("SELECT id, name FROM priorities ORDER BY level ASC");
    $priorities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'priorities' => $priorities]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

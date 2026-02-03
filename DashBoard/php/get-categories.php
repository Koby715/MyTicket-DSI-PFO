<?php
/**
 * get-categories.php
 * Récupère les catégories de la base de données
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';

try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

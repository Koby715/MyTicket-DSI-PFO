<?php
/**
 * export-tickets-excel.php
 * Génère un fichier Excel (.xls) à partir de la liste filtrée des tickets
 */

session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_id'])) {
    exit('Accès refusé');
}

// --- LOGIQUE DE FILTRAGE (Identique au dashboard) ---
$where_clauses = ["1=1"];
$params = [];

// [NOUVEAU] Priorité à la sélection manuelle via checkboxes
if (!empty($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $where_clauses[] = "t.id IN ($placeholders)";
    $params = array_map('intval', $ids);
} else {
    // Filtres habituels
    if (!empty($_GET['search'])) {
        $search = "%" . trim($_GET['search']) . "%";
        $where_clauses[] = "(t.reference LIKE ? OR t.subject LIKE ? OR t.email LIKE ? OR t.nom LIKE ? OR u.name LIKE ?)";
        $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
    }
    if (!empty($_GET['status'])) {
        $where_clauses[] = "t.status_id = ?";
        $params[] = $_GET['status'];
    }
    if (!empty($_GET['category'])) {
        $where_clauses[] = "t.category_id = ?";
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['priority'])) {
        $where_clauses[] = "t.priority_id = ?";
        $params[] = $_GET['priority'];
    }
    if (!empty($_GET['date_from'])) {
        $where_clauses[] = "DATE(t.created_at) >= ?";
        $params[] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $where_clauses[] = "DATE(t.created_at) <= ?";
        $params[] = $_GET['date_to'];
    }
}

$where_sql = implode(" AND ", $where_clauses);

try {
    $sql = "SELECT t.*, 
                   c.name as category_name, 
                   p.name as priority_name, 
                   s.label as status_label,
                   u.name as assigned_name,
                   r.report_content,
                   r.created_at as report_created_at
            FROM tickets t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN priorities p ON t.priority_id = p.id
            LEFT JOIN statuses s ON t.status_id = s.id
            LEFT JOIN users u ON t.assigned_to = u.id
            LEFT JOIN rapports r ON t.id = r.ticket_id
            WHERE $where_sql
            ORDER BY t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    exit("Erreur lors de l'export.");
}

// --- GÉNÉRATION DU FICHIER EXCEL (Via HTML Table) ---
$filename = "export_tickets_" . date('Y-m-d_His') . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-type" content="text/html;charset=utf-8" /></head>';
echo '<body>';
echo '<table border="1">';
echo '<tr>
        <th style="background-color: #20317b; color: white;">REFERENCE</th>
        <th style="background-color: #20317b; color: white;">DATE CREATION</th>
        <th style="background-color: #20317b; color: white;">DEMANDEUR</th>
        <th style="background-color: #20317b; color: white;">EMAIL</th>
        <th style="background-color: #20317b; color: white;">OBJET</th>
        <th style="background-color: #20317b; color: white;">CATEGORIE</th>
        <th style="background-color: #20317b; color: white;">PRIORITE</th>
        <th style="background-color: #20317b; color: white;">STATUT</th>
        <th style="background-color: #20317b; color: white;">ASSIGNE A</th>
        <th style="background-color: #20317b; color: white;">CONTENU RAPPORT</th>
        <th style="background-color: #20317b; color: white;">DATE RESOLUTION</th>
      </tr>';

foreach ($tickets as $t) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($t['reference']) . '</td>';
    echo '<td>' . $t['created_at'] . '</td>';
    echo '<td>' . htmlspecialchars($t['nom']) . '</td>';
    echo '<td>' . htmlspecialchars($t['email']) . '</td>';
    echo '<td>' . htmlspecialchars($t['subject']) . '</td>';
    echo '<td>' . htmlspecialchars($t['category_name'] ?? 'N/A') . '</td>';
    echo '<td>' . htmlspecialchars($t['priority_name'] ?? 'Normale') . '</td>';
    echo '<td>' . htmlspecialchars($t['status_label'] ?? 'Nouveau') . '</td>';
    echo '<td>' . htmlspecialchars($t['assigned_name'] ?? 'Non assigné') . '</td>';
    echo '<td>' . htmlspecialchars($t['report_content'] ?? '') . '</td>';
    echo '<td>' . ($t['report_created_at'] ?? '') . '</td>';
    echo '</tr>';
}

echo '</table>';
echo '</body>';
echo '</html>';

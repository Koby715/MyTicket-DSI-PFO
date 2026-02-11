<?php
/**
 * save-rapport.php
 * Gère la sauvegarde et modification des rapports de résolution de tickets
 */

session_start();
require_once 'config/db.php';
require_once 'lib/notification-helper.php';

header('Content-Type: application/json; charset=utf-8');

// === SÉCURITÉ ===
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'];
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$report_content = isset($_POST['report_content']) ? trim($_POST['report_content']) : '';

// === VALIDATIONS ===
if ($ticket_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de ticket invalide']);
    exit;
}

if (empty($report_content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le rapport est obligatoire']);
    exit;
}

if (strlen($report_content) > 5000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Le rapport dépasse 5000 caractères']);
    exit;
}

try {
    // === VÉRIFIER LE TICKET ===
    $stmt = $pdo->prepare("
        SELECT id, status_id, assigned_to, reference
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
    // Un AGENT ne peut remplir rapport que pour ses propres tickets
    // Un ADMIN/SUPERVISOR peut remplir rapport pour tous les tickets
    if ($admin_role === 'AGENT') {
        if (intval($ticket['assigned_to']) !== intval($admin_id)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez remplir un rapport que pour vos propres tickets']);
            exit;
        }
    }

    // === VÉRIFIER LE STATUT ===
    // (Modification: La vérification stricte "Résolu" est retirée pour permettre de créer le rapport AVANT de passer le ticket à "Résolu")
    /*
     $stmt = $pdo->prepare("
     SELECT label
     FROM statuses
     WHERE id = ?
     ");
     $stmt->execute([$ticket['status_id']]);
     $status = $stmt->fetch(PDO::FETCH_ASSOC);
     if (!$status || strtolower($status['label']) !== 'résolu') {
     http_response_code(403);
     echo json_encode(['success' => false, 'message' => 'Le rapport ne peut être rempli que pour un ticket résolu']);
     exit;
     }
     */

    // === VÉRIFIER SI RAPPORT EXISTE ===
    $stmt = $pdo->prepare("SELECT id FROM rapports WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    $existing_rapport = $stmt->fetch(PDO::FETCH_ASSOC);

    // === INSERT OU UPDATE ===
    $pdo->beginTransaction();

    if ($existing_rapport) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE rapports
            SET report_content = ?, agent_id = ?, updated_at = NOW()
            WHERE ticket_id = ?
        ");
        $stmt->execute([$report_content, $admin_id, $ticket_id]);
    }
    else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO rapports (ticket_id, agent_id, report_content, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$ticket_id, $admin_id, $report_content]);
    }

    // === TRAITER LES PIÈCES JOINTES (SI PRÉSENTES) ===
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $rapport_id = $existing_rapport ? $existing_rapport['id'] : $pdo->lastInsertId();

        // Validation du fichier
        $max_size = 5 * 1024 * 1024; // 5 MB
        if ($file['size'] > $max_size) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Le fichier dépasse 5 MB']);
            exit;
        }

        // Allowed extensions
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé']);
            exit;
        }

        // Créer le dossier s'il n'existe pas
        $upload_dir = realpath(__DIR__ . '/../../assets/uploads/tickets/');
        if (!$upload_dir) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Dossier d\'upload introuvable']);
            exit;
        }

        // Générer un nom de fichier sécurisé
        $unique_id = uniqid();
        $timestamp = time();
        $new_filename = "rapport_{$ticket_id}_{$timestamp}_{$unique_id}.{$file_ext}";
        $file_path = $upload_dir . '/' . $new_filename;

        // Déplacer le fichier
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
            exit;
        }

        // Insérer dans la table attachments
        $stmt = $pdo->prepare("
            INSERT INTO attachments (ticket_id, report_id, file_name, file_path, uploaded_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $ticket_id,
            $rapport_id,
            $file['name'],
            $file_path
        ]);
    }

    // === OPTIONNEL : CHANGER LE STATUT EN "RÉSOLU" ===
    // Si demandé, on passe le ticket en "Résolu" directement ici (transaction unique)
    $resolved_generated = false;
    if (isset($_POST['auto_resolve']) && $_POST['auto_resolve'] === 'true') {
        $stmtStatus = $pdo->prepare("SELECT id FROM statuses WHERE label = 'Résolu' LIMIT 1");
        $stmtStatus->execute();
        $status_id = $stmtStatus->fetchColumn();

        if ($status_id) {
            $stmt = $pdo->prepare("UPDATE tickets SET status_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status_id, $ticket_id]);
            $resolved_generated = true;
        }
    }

    $pdo->commit();

    // Envoi de l'email de notification HORS transaction pour éviter de bloquer la DB
    if ($resolved_generated) {
        sendStatusUpdateEmail($ticket_id, $pdo);
    }

    echo json_encode([
        'success' => true,
        'message' => $existing_rapport ? 'Rapport modifié avec succès' : 'Rapport créé avec succès'
    ]);

}
catch (PDOException $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    error_log("Erreur save-rapport.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}

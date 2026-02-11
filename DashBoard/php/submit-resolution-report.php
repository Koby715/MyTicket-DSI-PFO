<?php
/**
 * submit-resolution-report.php
 * Traite la soumission d'un rapport de résolution et résout le ticket
 * Permissions: Uniquement l'agent assigné au ticket
 */

session_start();
require_once 'config/db.php';
require_once 'lib/notification-helper.php';

header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expirée. Veuillez vous reconnecter.']);
    exit;
}

$agent_id = $_SESSION['admin_id'];
$agent_role = $_SESSION['admin_role'] ?? 'AGENT';

// Vérification de la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    // Récupération des données
    $ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $report_content = isset($_POST['report_content']) ? trim($_POST['report_content']) : '';

    // Validation du ticket_id
    if ($ticket_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de ticket invalide.']);
        exit;
    }

    // Validation du commentaire
    if (empty($report_content)) {
        echo json_encode(['success' => false, 'message' => 'Le commentaire du rapport est obligatoire.']);
        exit;
    }

    if (strlen($report_content) < 10) {
        echo json_encode(['success' => false, 'message' => 'Le commentaire doit contenir au moins 10 caractères.']);
        exit;
    }

    // Vérification que le ticket existe et récupération des infos
    $stmt = $pdo->prepare("SELECT id, assigned_to, status_id, reference FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        echo json_encode(['success' => false, 'message' => 'Ticket introuvable.']);
        exit;
    }

    // Vérification des permissions : Agent assigné OU (ADMIN/SUPERVISOR)
    if ($agent_role === 'AGENT' && intval($ticket['assigned_to']) !== intval($agent_id)) {
        echo json_encode(['success' => false, 'message' => 'Accès refusé. Seul l\'agent assigné peut résoudre ce ticket.']);
        exit;
    }


    // Vérification que le ticket n'est pas déjà résolu ou fermé
    $stmt = $pdo->prepare("SELECT code FROM statuses WHERE id = ?");
    $stmt->execute([$ticket['status_id']]);
    $current_status = $stmt->fetchColumn();

    if (in_array($current_status, ['resolved', 'closed'])) {
        echo json_encode(['success' => false, 'message' => 'Ce ticket est déjà résolu ou fermé.']);
        exit;
    }

    // Vérification qu'un rapport n'existe pas déjà pour ce ticket
    $stmt = $pdo->prepare("SELECT id FROM rapports WHERE ticket_id = ?");
    $stmt->execute([$ticket_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Un rapport existe déjà pour ce ticket.']);
        exit;
    }

    // Gestion du fichier (optionnel)
    $uploaded_file_path = null;
    $uploaded_file_name = null;

    if (isset($_FILES['report_attachment']) && $_FILES['report_attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['report_attachment'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validation du type de fichier
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Type de fichier non autorisé. Formats acceptés : PDF, JPG, PNG.']);
            exit;
        }

        // Validation de la taille (5 Mo max)
        $max_size = 5 * 1024 * 1024; // 5 Mo en octets
        if ($file_size > $max_size) {
            echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux. Taille maximale : 5 Mo.']);
            exit;
        }

        // Création du dossier si nécessaire
        $upload_dir = __DIR__ . '/../../assets/uploads/reports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Génération d'un nom de fichier unique
        $unique_name = 'report_' . $ticket_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
        $upload_path = $upload_dir . $unique_name;

        // Upload du fichier
        if (!move_uploaded_file($file_tmp, $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier.']);
            exit;
        }

        $uploaded_file_path = $upload_path;
        $uploaded_file_name = $file_name;
    }

    // Début de la transaction
    $pdo->beginTransaction();

    try {
        // 1. Insertion du rapport dans la table rapports
        $stmt = $pdo->prepare("INSERT INTO rapports (ticket_id, agent_id, report_content, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$ticket_id, $agent_id, $report_content]);
        $report_id = $pdo->lastInsertId();

        // 2. Si un fichier a été uploadé, l'ajouter à la table attachments
        if ($uploaded_file_path && $uploaded_file_name) {
            $stmt = $pdo->prepare("INSERT INTO attachments (ticket_id, report_id, file_name, file_path, uploaded_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$ticket_id, $report_id, $uploaded_file_name, $uploaded_file_path]);
        }

        // 3. Mise à jour du statut du ticket à "Résolu"
        $stmt = $pdo->prepare("UPDATE tickets SET status_id = (SELECT id FROM statuses WHERE code = 'resolved'), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$ticket_id]);

        // Commit de la transaction
        $pdo->commit();

        // Envoyer la notification email de changement de statut
        sendStatusUpdateEmail($ticket_id, $pdo);

        echo json_encode([
            'success' => true,
            'message' => 'Rapport soumis et ticket #' . $ticket['reference'] . ' résolu avec succès !'
        ]);

    }
    catch (Exception $e) {
        // Rollback en cas d'erreur
        $pdo->rollBack();

        // Supprimer le fichier uploadé si la transaction échoue
        if ($uploaded_file_path && file_exists($uploaded_file_path)) {
            unlink($uploaded_file_path);
        }

        error_log("Erreur transaction rapport: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement du rapport.']);
    }

}
catch (PDOException $e) {
    error_log("Erreur PDO submit-resolution-report: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
}
catch (Exception $e) {
    error_log("Erreur submit-resolution-report: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue.']);
}

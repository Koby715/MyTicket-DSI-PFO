<?php
/**
 * update-ticket.php
 * Gère la mise à jour des tickets
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';

// Initialiser la réponse
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// Récupérer les données
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$priority_id = isset($_POST['priority_id']) ? intval($_POST['priority_id']) : 0;

// Validation
if ($ticket_id <= 0) {
    $response['message'] = 'ID du ticket invalide';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if (empty($subject)) {
    $response['message'] = 'L\'objet du ticket est requis';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if (empty($description)) {
    $response['message'] = 'La description du ticket est requise';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    // Vérifier que le ticket existe et que son statut est "Nouveau"
    $stmt = $pdo->prepare("
        SELECT t.id, s.label 
        FROM tickets t
        LEFT JOIN statuses s ON t.status_id = s.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        $response['message'] = 'Ticket introuvable';
        http_response_code(404);
        echo json_encode($response);
        exit;
    }
    
    if (strtolower($ticket['label']) !== 'nouveau') {
        $response['message'] = 'Seuls les tickets avec le statut "Nouveau" peuvent être modifiés';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }
    
    // Vérifier que la catégorie existe
    if ($category_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'La catégorie sélectionnée n\'existe pas';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'Veuillez sélectionner une catégorie';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Vérifier que la priorité existe
    if ($priority_id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM priorities WHERE id = ?");
        $stmt->execute([$priority_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'La priorité sélectionnée n\'existe pas';
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'Veuillez sélectionner une priorité';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Mettre à jour le ticket
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET subject = ?, 
            description = ?, 
            category_id = ?, 
            priority_id = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $subject,
        $description,
        $category_id,
        $priority_id,
        $ticket_id
    ]);
    
    // Traiter les pièces jointes si présentes
    $uploadedFiles = [];
    if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $uploadDir = __DIR__ . '/../../assets/uploads/tickets/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }
        
        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $tmp_name = $_FILES['attachments']['tmp_name'][$i];
            $original_name = basename($_FILES['attachments']['name'][$i]);
            $file_size = $_FILES['attachments']['size'][$i];
            
            // Vérifier la taille (5 Mo max)
            if ($file_size > 5 * 1024 * 1024) {
                continue; // Ignorer les fichiers trop gros
            }
            
            // Générer un nom de fichier sécurisé
            $ext = pathinfo($original_name, PATHINFO_EXTENSION);
            $safe_name = sprintf("ticket_%d_%d_%s.%s", $ticket_id, time(), uniqid(), $ext);
            $file_path = $uploadDir . $safe_name;
            
            // Déplacer le fichier
            if (move_uploaded_file($tmp_name, $file_path)) {
                // Insérer dans la table attachments
                $stmt = $pdo->prepare("
                    INSERT INTO attachments 
                    (ticket_id, file_name, file_path, uploaded_at)
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $ticket_id,
                    $original_name,
                    $file_path
                ]);
                
                $uploadedFiles[] = [
                    'original' => $original_name,
                    'stored' => $safe_name,
                    'size' => $file_size
                ];
            }
        }
    }
    
    $pdo->commit();
    
    $response['success'] = true;
    $response['message'] = 'Ticket modifié avec succès';
    $response['data'] = [
        'ticket_id' => $ticket_id,
        'attachments_count' => count($uploadedFiles)
    ];
    
    http_response_code(200);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $response['message'] = 'Erreur lors de la modification du ticket: ' . $e->getMessage();
    http_response_code(500);
    error_log("Erreur update ticket: " . $e->getMessage());
}

echo json_encode($response);
?>

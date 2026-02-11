<?php
/**
 * traitement-ticket.php
 * Gère la soumission et la validation du formulaire de création de ticket
 * Point d'entrée unique pour tous les traitements de tickets
 */

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';
require_once 'config/email-config.php';
require_once 'lib/SMTPEmailSender.php';
require_once 'lib/notification-helper.php';

// ============= FONCTION D'ENVOI D'EMAIL =============

// ============= NOUVELLE FONCTION D'ENVOI VIA API OUTLOOK (PYTHON) =============

function sendTicketEmailViaLocalAPI($nom, $email, $reference, $subject, $ticket_id, $token, $uploadedFiles = []) {
    try {
        global $pdo;
        $apiUrl = 'http://127.0.0.1:8000/send-mail';
        $emailConfig = require('config/email-config.php');
        $ticketLink = $emailConfig['app_url'] . '/DashBoard/php/liste-tickets-user.php?token=' . urlencode($token);
        
        // 1. Récupérer le template 'ticket_created' depuis la BD
        $stmt = $pdo->prepare("SELECT body FROM notification_templates WHERE event_name = ? AND is_enabled = 1 LIMIT 1");
        $stmt->execute(['ticket_created']);
        $templateData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $templateBody = $templateData['body'] ?? '';
        
        // 2. Préparation des variables pour le template
        $variables = [
            'customer_name' => $nom,
            'reference' => $reference,
            'subject' => $subject,
            'link' => $ticketLink,
            'created_date' => date('d/m/Y à H:i'),
            'sla_time' => 3 // Par défaut 3 jours
        ];
        
        // 3. Rendu du template avec Bootstrap
        $mailBody = renderEmailTemplate($templateBody, $variables, $emailConfig['app_url']);
        
        // 4. Préparation des pièces jointes (Chemins absolus requis pour Outlook)
        $absoluteAttachments = [];
        if (!empty($uploadedFiles)) {
            $uploadBaseDir = __DIR__ . '/../../assets/uploads/tickets/';
            
            foreach ($uploadedFiles as $file) {
                $fullPath = realpath($uploadBaseDir . $file['stored']);
                if ($fullPath && file_exists($fullPath)) {
                    $absoluteAttachments[] = $fullPath;
                }
            }
        }

        // 5. Construction du Payload JSON
        $data = [
            'to' => $email,
            'subject' => "Ticket Crée [$reference] : $subject",
            'body' => $mailBody,
            'is_html' => true,
            'attachments' => $absoluteAttachments
        ];

        // 6. Appel cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 7. Gestion de la réponse
        if ($httpCode == 200) {
            return true;
        } else {
            error_log("Erreur API Outlook ($httpCode) : " . $result);
            return false;
        }
    } catch (Exception $e) {
        error_log("Erreur envoi email: " . $e->getMessage());
        return false;
    }
}

// ============= ANCIENNE FONCTION D'ENVOI D'EMAIL (Legacy) =============

function sendTicketEmail($nom, $email, $reference, $subject, $ticket_id, $token) {
    try {
        global $pdo;
        $emailConfig = require('config/email-config.php');
        $sender = new SMTPEmailSender($emailConfig);
        
        // Générer le lien de suivi du ticket
        $ticketLink = $emailConfig['app_url'] . '/DashBoard/php/liste-tickets-user.php?token=' . urlencode($token);
        
        // 1. Récupérer le template 'ticket_created' depuis la BD
        $stmt = $pdo->prepare("SELECT body FROM notification_templates WHERE event_name = ? AND is_enabled = 1 LIMIT 1");
        $stmt->execute(['ticket_created']);
        $templateData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $templateBody = $templateData['body'] ?? '';
        
        // 2. Préparation des variables pour le template
        $variables = [
            'customer_name' => $nom,
            'reference' => $reference,
            'subject' => $subject,
            'link' => $ticketLink,
            'created_date' => date('d/m/Y à H:i'),
            'sla_time' => 3
        ];
        
        // 3. Rendu du template avec Bootstrap
        $mailBody = renderEmailTemplate($templateBody, $variables, $emailConfig['app_url']);
        
        $mailSubject = "Confirmation de création de ticket - $reference";
        
        // Envoyer l'email
        $success = $sender->sendHTML($email, $nom, $mailSubject, $mailBody);
        
        if (!$success) {
            error_log("Email send error: " . $sender->getError());
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Erreur envoi email: " . $e->getMessage());
        return false;
    }
}

// Initialiser la réponse
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// ============= ÉTAPE 1 : VALIDATION DES DONNÉES =============

// Récupérer et nettoyer les données
$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
$priority_id = isset($_POST['priority_id']) ? intval($_POST['priority_id']) : 0;
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validation du nom
if (empty($nom)) {
    $response['errors'][] = 'Le nom et prénom sont requis';
} elseif (strlen($nom) > 100) {
    $response['errors'][] = 'Le nom ne doit pas dépasser 100 caractères';
}

// Validation de l'email
if (empty($email)) {
    $response['errors'][] = 'L\'adresse email est requise';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors'][] = 'L\'adresse email n\'est pas valide';
} elseif (strlen($email) > 150) {
    $response['errors'][] = 'L\'email ne doit pas dépasser 150 caractères';
}

// Validation de la catégorie
if ($category_id <= 0) {
    $response['errors'][] = 'Veuillez sélectionner une catégorie';
}

// Validation de la priorité
if ($priority_id <= 0) {
    $response['errors'][] = 'Veuillez sélectionner une priorité';
}

// Validation du sujet
if (empty($subject)) {
    $response['errors'][] = 'L\'objet du ticket est requis';
} elseif (strlen($subject) > 255) {
    $response['errors'][] = 'L\'objet ne doit pas dépasser 255 caractères';
}

// Validation de la description
if (empty($description)) {
    $response['errors'][] = 'La description du ticket est requise';
}

// S'il y a des erreurs de validation
if (!empty($response['errors'])) {
    $response['message'] = 'Erreurs de validation détectées';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// ============= ÉTAPE 2 : VÉRIFIER LA COHÉRENCE BD =============

try {
    // Vérifier que la catégorie existe
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Catégorie invalide';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Vérifier que la priorité existe
    $stmt = $pdo->prepare("SELECT id FROM priorities WHERE id = ?");
    $stmt->execute([$priority_id]);
    if (!$stmt->fetch()) {
        $response['message'] = 'Priorité invalide';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Récupérer le status "Nouveau" 
    $stmt = $pdo->prepare("SELECT id FROM statuses WHERE code = 'new' LIMIT 1");
    $stmt->execute();
    $statusRow = $stmt->fetch();
    if (!$statusRow) {
        // Si pas de status trouvé, prendre le premier status non fermé
        $stmt = $pdo->query("SELECT id FROM statuses WHERE is_closed = 0 ORDER BY id ASC LIMIT 1");
        $statusRow = $stmt->fetch();
        if (!$statusRow) {
            throw new Exception('Aucun statut disponible dans la base de données');
        }
    }
    $status_id = $statusRow['id'];

} catch (PDOException $e) {
    $response['message'] = 'Erreur lors de la vérification des données';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// ============= ÉTAPE 3 : GÉNÉRER LES DONNÉES UNIQUES =============

// Générer le numéro de référence (TKT-2026-XXXXX)
$annee = date('Y');
try {
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(reference, 11) AS UNSIGNED)) as max_num FROM tickets WHERE reference LIKE 'TKT-$annee-%'");
    $result = $stmt->fetch();
    $nextNum = ($result['max_num'] ?? 0) + 1;
    $reference = sprintf("TKT-%s-%05d", $annee, $nextNum);
} catch (PDOException $e) {
    $reference = 'TKT-' . $annee . '-' . uniqid();
}

// Générer le token unique (SHA256)
$token = hash('sha256', $email . time() . uniqid() . mt_rand());

// ============= ÉTAPE 4 : INSÉRER LE TICKET =============

try {
    $pdo->beginTransaction();

    // Insérer le ticket
    $stmt = $pdo->prepare("
        INSERT INTO tickets 
        (reference, nom, email, subject, description, category_id, priority_id, status_id, token, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $reference,
        $nom,
        $email,
        $subject,
        $description,
        $category_id,
        $priority_id,
        $status_id,
        $token
    ]);

    $ticket_id = $pdo->lastInsertId();

    // ============= ÉTAPE 5 : TRAITER LES PIÈCES JOINTES =============

    $uploadedFiles = [];
    
    if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        // Créer le dossier de destination s'il n'existe pas
        $uploadDir = __DIR__ . '/../../assets/uploads/tickets/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
            // Sauter les fichiers sans erreur
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp_name = $_FILES['attachments']['tmp_name'][$i];
            $original_name = basename($_FILES['attachments']['name'][$i]);
            $file_size = $_FILES['attachments']['size'][$i];

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

    // Valider la transaction
    $pdo->commit();

    // ============= ÉTAPE 6 : ENVOYER L'EMAIL =============

    // ============= ÉTAPE 6 : ENVOYER L'EMAIL =============

    // [MODIF] Utilisation de l'API Outlook au lieu du SMTP
    // $emailSent = sendTicketEmail($nom, $email, $reference, $subject, $ticket_id, $token);
    
    $emailSent = sendTicketEmailViaLocalAPI($nom, $email, $reference, $subject, $ticket_id, $token, $uploadedFiles);

    // ============= ÉTAPE 7 : RÉPONSE DE SUCCÈS =============

    $response['success'] = true;
    $response['message'] = 'Ticket créé avec succès';
    $response['data'] = [
        'ticket_id' => $ticket_id,
        'reference' => $reference,
        'token' => $token,
        'attachments_count' => count($uploadedFiles),
        'redirect' => "confirmation-ticket.php?reference=" . urlencode($reference)
    ];

    http_response_code(201);

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $response['message'] = 'Erreur lors de la création du ticket: ' . $e->getMessage();
    http_response_code(500);
}

// Retourner la réponse JSON
echo json_encode($response);
?>

<?php
/**
 * update-templates.php
 * Met à jour les templates d'email existants dans la base de données
 */

require_once 'config/db.php';

// Les nouveaux templates
$templates = [
    'ticket_created' => "<h2>✅ Ticket créé avec succès</h2>
<p>Bonjour <strong>{customer_name}</strong>,</p>
<p>Nous confirmons la réception de votre demande. Votre ticket a bien été enregistré dans notre système et sera traité par notre équipe dans les meilleurs délais.</p>

<div class=\"alert alert-info\">
    <strong>🆕 Nouveau ticket créé</strong><br>
    <strong>Ticket :</strong> {reference}
</div>

<div class=\"info-box\">
    <strong>Objet :</strong> {subject}<br>
    <strong>Date de création :</strong> {created_date}<br>
    <strong>Statut :</strong> Nouveau
</div>

<p>Vous recevrez des mises à jour régulières sur l'avancement de votre demande. Notre équipe vous contactera si des informations supplémentaires sont nécessaires.</p>

<div style=\"text-align: center;\">
    <a href=\"{link}\" style=\"display: inline-block; padding: 12px 25px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;\">Voir les détails</a>
</div>

<div class=\"divider\"></div>

<table>
    <tr style=\"background: #f0f0f0;\">
        <td><strong>Statut actuel :</strong></td>
        <td style=\"text-align: right; color: #0066cc; font-weight: bold;\">Nouveau</td>
    </tr>
</table>"
];

try {
    // Mettre à jour le template ticket_created
    $stmt = $pdo->prepare("UPDATE notification_templates SET body = ? WHERE event_name = ?");
    $result = $stmt->execute([$templates['ticket_created'], 'ticket_created']);
    
    if ($result) {
        echo "✅ Template 'ticket_created' mis à jour avec succès!";
    } else {
        echo "❌ Erreur lors de la mise à jour du template.";
    }
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}

?>

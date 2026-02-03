<?php
/**
 * EmailSender.php
 * Classe pour envoyer des emails via SMTP
 * Alternative légère à PHPMailer
 */

class EmailSender {
    private $host;
    private $port;
    private $security;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
    private $replyTo;
    private $error = '';

    public function __construct($config) {
        $this->host      = $config['host'] ?? 'localhost';
        $this->port      = $config['port'] ?? 587;
        $this->security  = $config['security'] ?? 'tls';
        $this->username  = $config['username'] ?? '';
        $this->password  = $config['password'] ?? '';
        $this->fromEmail = $config['from_email'] ?? '';
        $this->fromName  = $config['from_name'] ?? 'Système de Tickets';
        $this->replyTo   = $config['reply_to'] ?? $this->fromEmail;
    }

    /**
     * Envoyer un email HTML
     */
    public function sendHTML($toEmail, $toName, $subject, $htmlBody) {
        // Pour la version de développement, utiliser la fonction mail() PHP
        // En production avec Exchange, on utiliserait une vraie connexion SMTP
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->formatEmail($this->fromName, $this->fromEmail),
            'Reply-To: ' . $this->replyTo,
            'X-Mailer: PHP/' . phpversion()
        ];

        $headers = implode("\r\n", $headers);

        // Tenter d'envoyer via mail() pour les tests
        // En production, il faudrait une vraie connexion SMTP
        $success = @mail(
            $toEmail,
            $subject,
            $htmlBody,
            $headers
        );

        if (!$success) {
            $this->error = 'Impossible d\'envoyer l\'email. Vérifiez la configuration SMTP.';
            return false;
        }

        return true;
    }

    /**
     * Formatter une adresse email avec nom
     */
    private function formatEmail($name, $email) {
        if (!empty($name)) {
            return "\"$name\" <$email>";
        }
        return $email;
    }

    /**
     * Obtenir le dernier message d'erreur
     */
    public function getError() {
        return $this->error;
    }
}
?>

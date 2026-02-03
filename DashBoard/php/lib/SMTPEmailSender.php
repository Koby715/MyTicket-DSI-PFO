<?php
/**
 * Simple SMTP Email Sender
 * Envoie des emails via SMTP sans dépendances externes
 */

class SMTPEmailSender {
    private $host;
    private $port;
    private $security;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
    private $replyTo;
    private $error = '';
    private $socket = null;

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
     * Envoyer un email HTML via SMTP
     */
    public function sendHTML($toEmail, $toName, $subject, $htmlBody) {
        try {
            // Connexion au serveur SMTP
            $this->connect();
            
            // Authentification
            $this->authenticate();
            
            // Envoyer l'email
            $this->sendMessage($toEmail, $toName, $subject, $htmlBody);
            
            // Fermer la connexion
            $this->disconnect();
            
            return true;
            
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            $this->disconnect();
            return false;
        }
    }

    /**
     * Connexion au serveur SMTP
     */
    private function connect() {
        // Créer une socket
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 30);
        
        if (!$this->socket) {
            throw new Exception("Impossible de se connecter à $this->host:$this->port - $errstr ($errno)");
        }

        // Lire la réponse du serveur
        $response = $this->readResponse();
        if (strpos($response, '220') === false) {
            throw new Exception("Erreur de connexion SMTP: $response");
        }

        // Envoyer EHLO
        $this->sendCommand("EHLO " . gethostname());
        
        // Commencer TLS si nécessaire
        if ($this->security === 'tls') {
            $this->sendCommand("STARTTLS");
            
            // Activer le chiffrement TLS avec vérification de certificat désactivée
            // (Nécessaire pour certains serveurs Exchange/Hosteam)
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT, $context)) {
                throw new Exception("Impossible d'activer TLS");
            }
            
            // Envoyer EHLO à nouveau après TLS
            $this->sendCommand("EHLO " . gethostname());
        }
    }

    /**
     * Authentification SMTP
     */
    private function authenticate() {
        if (empty($this->username) || empty($this->password)) {
            return; // Pas d'authentification requise
        }

        // Envoyer AUTH LOGIN
        $this->sendCommand("AUTH LOGIN");
        
        // Envoyer le nom d'utilisateur en base64
        $this->sendCommand(base64_encode($this->username));
        
        // Envoyer le mot de passe en base64
        $this->sendCommand(base64_encode($this->password));
    }

    /**
     * Envoyer le message
     */
    private function sendMessage($toEmail, $toName, $subject, $htmlBody) {
        // From
        $this->sendCommand("MAIL FROM: <{$this->fromEmail}>");
        
        // To
        $this->sendCommand("RCPT TO: <{$toEmail}>");
        
        // Data
        $this->sendCommand("DATA");
        
        // Construire l'email
        $headers = $this->buildHeaders($toEmail, $toName, $subject);
        $message = $headers . "\r\n\r\n" . $htmlBody;
        
        // Envoyer le contenu
        fwrite($this->socket, $message . "\r\n.\r\n");
        $response = $this->readResponse();
        
        if (strpos($response, '250') === false) {
            throw new Exception("Erreur lors de l'envoi du message: $response");
        }
    }

    /**
     * Construire les headers de l'email
     */
    private function buildHeaders($toEmail, $toName, $subject) {
        $headers = [];
        $headers[] = 'From: ' . $this->formatEmail($this->fromName, $this->fromEmail);
        $headers[] = 'To: ' . $this->formatEmail($toName, $toEmail);
        $headers[] = 'Reply-To: ' . $this->replyTo;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'X-Mailer: SMTP Email Sender/1.0';
        
        return implode("\r\n", $headers);
    }

    /**
     * Formatter une adresse email
     */
    private function formatEmail($name, $email) {
        if (!empty($name)) {
            return "\"$name\" <$email>";
        }
        return $email;
    }

    /**
     * Envoyer une commande SMTP
     */
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
        $response = $this->readResponse();
        
        if (!in_array(substr($response, 0, 3), ['220', '235', '250', '334', '354'])) {
            throw new Exception("Erreur SMTP: $response");
        }
        
        return $response;
    }

    /**
     * Lire une réponse du serveur SMTP
     */
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    }

    /**
     * Déconnecter du serveur SMTP
     */
    private function disconnect() {
        if ($this->socket) {
            @fwrite($this->socket, "QUIT\r\n");
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * Obtenir le message d'erreur
     */
    public function getError() {
        return $this->error;
    }
}
?>

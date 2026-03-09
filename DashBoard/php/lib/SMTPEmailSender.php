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
     * Envoyer un email HTML avec pièces jointes via SMTP
     */
    public function sendHTML($toEmail, $toName, $subject, $htmlBody, $attachments = []) {
        try {
            // Connexion au serveur SMTP
            $this->connect();
            
            // Authentification
            $this->authenticate();
            
            // Préparer le message
            if (empty($attachments)) {
                $this->sendMessage($toEmail, $toName, $subject, $htmlBody);
            } else {
                $this->sendMessageWithAttachments($toEmail, $toName, $subject, $htmlBody, $attachments);
            }
            
            // Fermer la connexion
            $this->disconnect();
            
            return true;
            
        } catch (Throwable $e) {
            error_log("SMTP Send Error: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }

    /**
     * Connexion au serveur SMTP
     */
    private function connect() {
        // Créer l'adresse de connexion
        $remote_addr = "tcp://{$this->host}:{$this->port}";
        
        // Créer le contexte pour ignorer la vérification SSL (souvent requis pour Exchange interne/Hosteam)
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_ANY_CLIENT
            ]
        ]);

        // Connexion avec timeout de 10s
        $this->socket = @stream_socket_client($remote_addr, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
        
        if (!$this->socket || !is_resource($this->socket)) {
            throw new Exception("Impossible de se connecter à $this->host:$this->port - $errstr ($errno)");
        }

        // Lire la réponse du serveur (Bienvenue 220)
        $response = $this->readResponse();
        if (strpos($response, '220') === false) {
            throw new Exception("Erreur de connexion SMTP (Serveur non prêt): $response");
        }

        // Envoyer EHLO
        $this->sendCommand("EHLO " . gethostname());
        
        // Commencer TLS si nécessaire
        if ($this->security === 'tls') {
            $this->sendCommand("STARTTLS");
            
            // Vérifier encore la validité du socket avant d'activer le crypto
            if (!is_resource($this->socket)) {
                throw new Exception("Le socket a été fermé par le serveur avant l'activation du TLS");
            }

            // Activer le chiffrement TLS
            // On essaie d'abord avec ANY (le plus flexible)
            $success = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_ANY_CLIENT);
            
            if (!$success) {
                // Tentative désespérée avec les méthodes spécifiques si ANY échoue
                $success = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSV1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSV1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSV1_0_CLIENT);
            }

            if (!$success) {
                throw new Exception("Échec de la négociation TLS (Vérifiez si le serveur supporte TLS sur le port $this->port)");
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
     * Envoyer le message simple (sans pièces jointes)
     */
    private function sendMessage($toEmail, $toName, $subject, $htmlBody) {
        $this->sendCommand("MAIL FROM: <{$this->fromEmail}>");
        $this->sendCommand("RCPT TO: <{$toEmail}>");
        $this->sendCommand("DATA");
        
        $headers = [];
        $headers[] = 'From: ' . $this->formatEmail($this->fromName, $this->fromEmail);
        $headers[] = 'To: ' . $this->formatEmail($toName, $toEmail);
        $headers[] = 'Reply-To: ' . $this->replyTo;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'X-Mailer: SMTP Email Sender/1.0';
        
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody;
        
        fwrite($this->socket, $message . "\r\n.\r\n");
        $response = $this->readResponse();
        
        if (strpos($response, '250') === false) {
            throw new Exception("Erreur lors de l'envoi du message: $response");
        }
    }

    /**
     * Envoyer le message avec pièces jointes
     */
    private function sendMessageWithAttachments($toEmail, $toName, $subject, $htmlBody, $attachments) {
        $this->sendCommand("MAIL FROM: <{$this->fromEmail}>");
        $this->sendCommand("RCPT TO: <{$toEmail}>");
        $this->sendCommand("DATA");
        
        $boundary = md5(time());
        
        $headers = [];
        $headers[] = 'From: ' . $this->formatEmail($this->fromName, $this->fromEmail);
        $headers[] = 'To: ' . $this->formatEmail($toName, $toEmail);
        $headers[] = 'Reply-To: ' . $this->replyTo;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-Type: multipart/mixed; boundary=\"$boundary\"";
        $headers[] = 'X-Mailer: SMTP Email Sender/1.0';
        
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Corps HTML
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        
        // Pièces jointes
        foreach ($attachments as $file) {
            $filePath = is_array($file) ? ($file['path'] ?? '') : $file;
            $fileName = is_array($file) ? ($file['name'] ?? basename($filePath)) : basename($filePath);
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $content = chunk_split(base64_encode($content));
                
                $message .= "--$boundary\r\n";
                $message .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
                $message .= "Content-Description: $fileName\r\n";
                $message .= "Content-Disposition: attachment; filename=\"$fileName\"; size=" . filesize($filePath) . ";\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $message .= $content . "\r\n\r\n";
            }
        }
        
        $message .= "--$boundary--";
        
        fwrite($this->socket, $message . "\r\n.\r\n");
        $response = $this->readResponse();
        
        if (strpos($response, '250') === false) {
            throw new Exception("Erreur lors de l'envoi du message: $response");
        }
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
        while ($line = @fgets($this->socket, 512)) {
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

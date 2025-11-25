<?php

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';


// Make sure you have the Composer autoloader included at the top of your main script!
// require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming your Config class is either in the same file or auto-loaded via Composer
// (If not, you'd need to include the Config.php file here)

class MailSender
{
    private $config;

    /**
     * The MailSender constructor requires an instance of your Config class
     * to access environment variables like SMTP credentials.
     * * @param Config $config An instance of your Config class.
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Sends an email using PHPMailer and SMTP settings from the .env file.
     * * @param string $toEmail The recipient's email address.
     * @param string $subject The subject line of the email.
     * @param string $body The message body (can be HTML).
     * @param string $altBody The plain text version of the message (optional).
     * @return bool True on success, false on failure.
     */
    public function sendEmail(string $toEmail, string $subject, string $body, string $altBody = ''): bool
    {
        // 1. Instantiate PHPMailer
        // Use 'true' to enable exceptions for error handling
        $mail = new PHPMailer(true);

        try {
            // 2. Server Configuration (Fetched from .env via Config)
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST');       // e.g., smtp.gmail.com
            $mail->SMTPAuth = true;                      // Enable SMTP authentication
            $mail->Username = getenv('MAIL_USERNAME');   // e.g., your_email@gmail.com
            $mail->Password = getenv('MAIL_PASSWORD');   // e.g., your_app_password
            $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?? PHPMailer::ENCRYPTION_STARTTLS; // e.g., 'tls' or 'ssl'
            $mail->Port = getenv('MAIL_PORT');       // e.g., 587 for TLS/STARTTLS, 465 for SSL

            // Optional: Debugging
            // $mail->SMTPDebug = 2; // Set to 2 for verbose debug output 

            // 3. Sender Information
            $fromEmail = getenv('MAIL_FROM_ADDRESS');
            $fromName = getenv('MAIL_FROM_NAME') ?? 'System Sender';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail); // Add the recipient

            // 4. Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            //$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
            //$mail->Debugoutput = 'json';

            // Set AltBody only if not provided, to strip HTML from $body
            if (empty($altBody)) {
                $mail->AltBody = strip_tags($body);
            } else {
                $mail->AltBody = $altBody;
            }

            $mail->send();
            return true;

        } catch (Exception $e) {
            // Log the error instead of dying
            error_log("Mail sending failed for $toEmail. Error: {$mail->ErrorInfo}");
            // Optionally, echo for debugging on a dev environment
            // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }
    }
}

$config = new Config();
$mailer = new MailSender($config);
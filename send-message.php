<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__.'/vendor/autoload.php';

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize input
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);

    // PHPMailer setup
    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'brosakh097@gmail.com';
        $mail->Password = '@Morkr@ksa24';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Sender and recipient settings
        $mail->setFrom('no-reply@yourdomain.com', 'Exam Portal');
        $mail->addAddress('brosakh097@gmail.com'); // Your Gmail
        $mail->addReplyTo($email, $name);

        // Email content
        $mail->isHTML(false);
        $mail->Subject = "New Contact Message: $subject";
        $mail->Body    = "You have received a new message from your website contact form.\n\n"
                        . "Here are the details:\n\n"
                        . "Name: $name\n"
                        . "Email: $email\n"
                        . "Subject: $subject\n"
                        . "Message:\n$message";

        // Send email
        if ($mail->send()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'error' => $mail->ErrorInfo]);
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $mail->ErrorInfo]);
    }
}

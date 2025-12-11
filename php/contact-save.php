<?php

declare(strict_types=1);

// Carpeta de destino
$baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'messages';
if (!is_dir($baseDir)) {
    @mkdir($baseDir, 0775, true);
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    header('Location: ../contact-us.html?status=error');
    exit;
}

$timestamp = date('Y-m-d_H-i-s');
$safeSubject = preg_replace('/[^A-Za-z0-9_-]/', '_', substr($subject, 0, 50));
$filename = $baseDir . DIRECTORY_SEPARATOR . $timestamp . '_' . $safeSubject . '.txt';

$content = "Nombre: {$name}\nEmail: {$email}\nAsunto: {$subject}\nMensaje:\n{$message}\n";

if (@file_put_contents($filename, $content) === false) {
    header('Location: ../contact-us.html?status=error');
    exit;
}

header('Location: ../contact-us.html?status=success');
exit;



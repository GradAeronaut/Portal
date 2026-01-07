<?php
/**
 * Portal Email Helpers
 * Функции для отправки email через SMTP
 */

/**
 * Отправка email через SMTP
 * 
 * @param string $to Email получателя
 * @param string $subject Тема письма
 * @param string $body Тело письма (HTML или текст)
 * @param bool $isHtml Является ли тело письма HTML
 * @return bool true в случае успеха, false при ошибке
 */
function send_email_smtp(string $to, string $subject, string $body, bool $isHtml = false): bool {
    // Загружаем конфигурацию SMTP
    $smtpConfigFile = __DIR__ . '/../config/smtp.php';
    if (!file_exists($smtpConfigFile)) {
        error_log("SMTP config file not found: {$smtpConfigFile}");
        return false;
    }
    
    $smtpConfig = require $smtpConfigFile;
    
    // Проверяем наличие всех необходимых параметров
    $required = ['host', 'port', 'username', 'password', 'from_email', 'from_name'];
    foreach ($required as $key) {
        if (!isset($smtpConfig[$key]) || empty($smtpConfig[$key])) {
            error_log("SMTP config missing required key: {$key}");
            return false;
        }
    }
    
    $host = $smtpConfig['host'];
    $port = (int) $smtpConfig['port'];
    $username = $smtpConfig['username'];
    $password = $smtpConfig['password'];
    $fromEmail = $smtpConfig['from_email'];
    $fromName = $smtpConfig['from_name'];
    $encryption = $smtpConfig['encryption'] ?? 'tls';
    
    // Определяем схему подключения
    $scheme = ($encryption === 'ssl') ? 'ssl://' : '';
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    // Подключаемся к SMTP серверу
    $socket = @stream_socket_client(
        "{$scheme}{$host}:{$port}",
        $errno,
        $errstr,
        30,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if (!$socket) {
        error_log("SMTP connection failed: {$errstr} ({$errno})");
        return false;
    }
    
    // Функция для чтения ответа SMTP
    $readResponse = function($expectedCode = null) use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if ($expectedCode !== null && $code !== $expectedCode) {
            error_log("SMTP unexpected response: {$response} (expected {$expectedCode})");
            return false;
        }
        return ['code' => $code, 'message' => $response];
    };
    
    // Читаем приветствие сервера
    $response = $readResponse(220);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    $hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Если используется STARTTLS
    if ($encryption === 'tls' && $port === 587) {
        // Отправляем EHLO
        fputs($socket, "EHLO {$hostname}\r\n");
        $response = $readResponse();
        if ($response === false || $response['code'] !== 250) {
            error_log("EHLO failed");
            fclose($socket);
            return false;
        }
        
        // Отправляем STARTTLS
        fputs($socket, "STARTTLS\r\n");
        $response = $readResponse(220);
        if ($response === false) {
            fclose($socket);
            return false;
        }
        
        // Включаем шифрование
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log("Failed to enable TLS encryption");
            fclose($socket);
            return false;
        }
        
        // Повторяем EHLO после STARTTLS
        fputs($socket, "EHLO {$hostname}\r\n");
        $response = $readResponse(250);
        if ($response === false) {
            fclose($socket);
            return false;
        }
    } else {
        // Для SSL сразу отправляем EHLO
        fputs($socket, "EHLO {$hostname}\r\n");
        $response = $readResponse(250);
        if ($response === false) {
            fclose($socket);
            return false;
        }
    }
    
    // Аутентификация
    fputs($socket, "AUTH LOGIN\r\n");
    $response = $readResponse(334);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($username) . "\r\n");
    $response = $readResponse(334);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($password) . "\r\n");
    $response = $readResponse(235);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    // Отправляем MAIL FROM
    fputs($socket, "MAIL FROM: <{$fromEmail}>\r\n");
    $response = $readResponse(250);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    // Отправляем RCPT TO
    fputs($socket, "RCPT TO: <{$to}>\r\n");
    $response = $readResponse(250);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    // Отправляем DATA
    fputs($socket, "DATA\r\n");
    $response = $readResponse(354);
    if ($response === false) {
        fclose($socket);
        return false;
    }
    
    // Формируем заголовки письма
    $headers = [
        "From: {$fromName} <{$fromEmail}>",
        "To: <{$to}>",
        "Subject: {$subject}",
        "MIME-Version: 1.0",
        "Content-Type: " . ($isHtml ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8"),
        "Date: " . date('r')
    ];
    
    // Логирование заголовков для отладки
    error_log('VERIFY_MAIL: Headers: ' . implode(" | ", $headers));
    error_log('VERIFY_MAIL: From = ' . $fromName . ' <' . $fromEmail . '>');
    
    // Отправляем заголовки и тело письма
    fputs($socket, implode("\r\n", $headers) . "\r\n\r\n");
    fputs($socket, $body . "\r\n");
    fputs($socket, ".\r\n");
    
    $response = $readResponse(250);
    if ($response === false) {
        error_log('VERIFY_MAIL: SMTP DATA command failed');
        fclose($socket);
        return false;
    }
    
    // Логирование успешной отправки DATA
    error_log('VERIFY_MAIL: SMTP DATA accepted: ' . trim($response['message'] ?? ''));
    
    // Закрываем соединение
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}

/**
 * Загрузка и обработка шаблона email
 * 
 * @param string $templateName Имя шаблона (без расширения)
 * @param array $variables Массив переменных для подстановки
 * @param bool $preferHtml Предпочитать HTML версию, если доступна
 * @return array ['body' => string, 'is_html' => bool] или false при ошибке
 */
function load_email_template(string $templateName, array $variables = [], bool $preferHtml = true): array|false {
    $templateDir = __DIR__ . '/../auth/email';
    
    // Пробуем загрузить HTML версию, если предпочтительна
    if ($preferHtml) {
        $htmlPath = "{$templateDir}/{$templateName}.html";
        if (file_exists($htmlPath)) {
            $content = file_get_contents($htmlPath);
            if ($content !== false) {
                return [
                    'body' => replace_template_variables($content, $variables),
                    'is_html' => true
                ];
            }
        }
    }
    
    // Загружаем текстовую версию
    $txtPath = "{$templateDir}/{$templateName}.txt";
    if (!file_exists($txtPath)) {
        error_log("Email template not found: {$txtPath}");
        return false;
    }
    
    $content = file_get_contents($txtPath);
    if ($content === false) {
        error_log("Failed to read email template: {$txtPath}");
        return false;
    }
    
    return [
        'body' => replace_template_variables($content, $variables),
        'is_html' => false
    ];
}

/**
 * Замена переменных в шаблоне
 * 
 * @param string $template Шаблон с переменными вида {{VAR_NAME}}
 * @param array $variables Массив ['VAR_NAME' => 'value']
 * @return string Обработанный шаблон
 */
function replace_template_variables(string $template, array $variables): string {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

/**
 * Отправка письма подтверждения email
 * 
 * @param string $email Email получателя
 * @param string $verificationToken Токен верификации
 * @return bool true в случае успеха, false при ошибке
 */
function send_verification_email(string $email, string $verificationToken): bool {
    // Логирование вызова функции
    error_log('VERIFY_MAIL: send_verification_email called for '.$email);
    
    // Формируем ссылку верификации
    $verifyLink = 'https://gradaeronaut.com/app/verify.php?token=' . urlencode($verificationToken);
    
    // Загружаем шаблон
    $template = load_email_template('verify_email', [
        'VERIFY_LINK' => $verifyLink
    ], true); // Предпочитаем HTML версию
    
    if ($template === false) {
        error_log("Failed to load verification email template");
        return false;
    }
    
    // Отправляем письмо
    $subject = 'Sinbad Portal - Account confirmation required';
    $ok = send_email_smtp($email, $subject, $template['body'], $template['is_html']);
    
    // Логирование результата отправки
    error_log('VERIFY_MAIL: send result = '.($ok ? 'OK' : 'FAIL'));
    
    return $ok;
}


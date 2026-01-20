<?php
// services/LoggerService.php

class LoggerService
{
    private $logFile;

    public function __construct($logFile = 'app.log') {
        $this->logFile = $logFile;
    }

    public function log(string $message, string $level = 'INFO'): void
    {
        $formattedMessage = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $level, $message);
        error_log($formattedMessage, 3, $this->logFile);
    }

    public function info(string $message): void
    {
        $this->log($message, 'INFO');
    }
}

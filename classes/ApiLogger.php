<?php

class ApiLogger
{
  private static $logFile;

  public static function init()
  {
    self::$logFile = _PS_MODULE_DIR_ . 'myapi/logs/api_debug.log';

    // Crear directorio logs si no existe
    $logDir = dirname(self::$logFile);
    if (!is_dir($logDir)) {
      mkdir($logDir, 0755, true);
    }
  }

  public static function log($message, $data = null)
  {
    self::init();

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";

    if ($data !== null) {
      $logEntry .= " | Data: " . print_r($data, true);
    }

    $logEntry .= "\n";

    file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
  }

  public static function logError($message, Exception $e = null)
  {
    $logMessage = "ERROR: $message";

    if ($e) {
      $logMessage .= " | Exception: " . $e->getMessage();
      $logMessage .= " | File: " . $e->getFile() . ":" . $e->getLine();
      $logMessage .= " | Trace: " . $e->getTraceAsString();
    }

    self::log($logMessage);
  }
}

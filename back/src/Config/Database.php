<?php
declare(strict_types=1);

namespace App\Config;

use PDO;

class Database
{
  private static ?PDO $instance = null;

  /**
   * @return PDO
   */
  public static function getConnection(): PDO
  {
    if (!self::$instance) {
      $host = "pgsql_desafio";
      $db = "applicationphp";
      $user = "root";
      $pw = "root";

      self::$instance = new PDO("pgsql:host=$host;dbname=$db", $user, $pw);
      self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return self::$instance;
  }
}

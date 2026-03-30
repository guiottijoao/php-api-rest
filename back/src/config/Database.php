<?php
class Database
{
  private static $instance = null;

  public static function getConnection()
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

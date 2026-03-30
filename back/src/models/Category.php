<?php
class Category {
  private $conn;

  public function __construct($db) {
    $this->conn = $db;
  }

  public function list() {
    $stmt = $this->conn->prepare("SELECT * FROM categories");
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
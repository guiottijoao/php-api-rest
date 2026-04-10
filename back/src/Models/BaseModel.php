<?php

namespace App\Model;

use PDO;

abstract class BaseModel
{
  protected $db;
  protected $table;

  public function __construct(PDO $db)
  {
    $this->db = $db;
  }

  public function findById($id)
  {
    $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  public function list()
  {
    $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY code ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function softDelete($id)
  {
    $status = $this->table == 'orders' ? 'closed' : 'inactive';
    $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE code = :code");
    $stmt->execute([':code' => $id, ':status' => $status]);
  }

  public function delete($id) {
    $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE code = :id");
    $stmt->execute([':id' => $id]);
  }

  public function generateBusinessCode()
  {
    $status = $this->table === 'orders' ? 'open' : 'active';
    $table = $this-> table === 'order_item' ? 'orders' : $this->table;
    $stmt = $this->db->prepare("SELECT COALESCE(MAX(business_code) + 1, 1) FROM {$table} WHERE status = :status");
    $stmt->execute([":status" => $status]);
    return $stmt->fetchColumn();
  }

  public function sanitize(string $string)
  {
    return htmlspecialchars(preg_replace('/\s+/', ' ', strip_tags(trim($string))));
  }

  public function nameExists(string $name)
  {
    $trimmedName = trim($name);
    $normalizedName = str_replace(' ', '', $trimmedName);

    $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active' AND LOWER(REPLACE(name, ' ', '')) = LOWER(:normalizedName)");
    $stmt->execute([':normalizedName' => $normalizedName]);
    return $stmt->fetchColumn() > 0;
  }
}

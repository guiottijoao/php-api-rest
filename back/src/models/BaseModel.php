<?php

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
    $stmt = $this->db->query("SELECT * FROM {$this->table}");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function delete($id)
  {
    $stmt = $this->db->prepare("UPDATE {$this->table} SET status = 'inactive' WHERE code = :code");
    $stmt->execute([':code' => $id]);
  }

  public function generateBusinessCode()
  {
    $stmt = $this->db->prepare("SELECT COALESCE(MAX(business_code) + 1, 1) FROM {$this->table} WHERE status = 'active'");
    $stmt->execute();
    return $stmt->fetchColumn();
  }
}

<?php
namespace Core;

use PDO;
use Core\DB;

class Model
{
    protected $table;

    public static function all()
    {
        $instance = new static;
        $stmt = DB::get()->prepare("SELECT * FROM {$instance->table} WHERE company_id = ?");
        $stmt->execute([$_SESSION['company_id'] ?? 0]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $instance = new static;
        $stmt = DB::get()->prepare("SELECT * FROM {$instance->table} WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $_SESSION['company_id'] ?? 0]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
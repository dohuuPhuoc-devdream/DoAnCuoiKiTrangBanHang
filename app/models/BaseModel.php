<?php
// filepath: app/models/BaseModel.php

abstract class BaseModel
{
    protected $conn;
    protected $table;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    protected function findById($id, $columns = '*') {
        try {
            $stmt = $this->conn->prepare("SELECT {$columns} FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in findById: " . $e->getMessage());
            return null;
        }
    }

    protected function create(array $data) {
        try {
            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));
            
            $stmt = $this->conn->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            $stmt->execute();
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in create: " . $e->getMessage());
            return false;
        }
    }

    protected function update($id, array $data) {
        try {
            $setClause = implode(', ', array_map(function($key) {
                return "{$key} = :{$key}";
            }, array_keys($data)));
            
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET {$setClause} WHERE id = :id");
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in update: " . $e->getMessage());
            return false;
        }
    }

    protected function delete($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Database error in delete: " . $e->getMessage());
            return false;
        }
    }
}
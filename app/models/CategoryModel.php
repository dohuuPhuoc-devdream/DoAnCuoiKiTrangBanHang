<?php

class CategoryModel
{
    private $conn;

    /**
     * Constructor with dependency injection.
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAllCategories()
    {
        $stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY categoryId DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCategoriesForAdmin()
    {
        // If you want a different query for admin, otherwise just call getAllCategories()
        return $this->getAllCategories();
    }

    public function getCategoryById($categoryId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM categories WHERE categoryId = :categoryId");
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addCategory($categoryName)
    {
        $stmt = $this->conn->prepare("INSERT INTO categories (categoryName) VALUES (:categoryName)");
        $stmt->bindParam(':categoryName', $categoryName, PDO::PARAM_STR);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    public function updateCategory($categoryId, $categoryName)
    {
        $stmt = $this->conn->prepare("UPDATE categories SET categoryName = :categoryName WHERE categoryId = :categoryId");
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(':categoryName', $categoryName, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function deleteCategory($categoryId)
    {
        $stmt = $this->conn->prepare("DELETE FROM categories WHERE categoryId = :categoryId");
        $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
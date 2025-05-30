<?php

class OrderItemModel
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getOrderItemsByOrderId($orderId)
    {
        $stmt = $this->conn->prepare("SELECT orderItems.*, products.*, 
                                                (SELECT link 
                                                    FROM productImages 
                                                    WHERE productImages.productId = products.productId 
                                                    LIMIT 1) AS link
                                            FROM orderItems
                                            INNER JOIN products ON orderItems.productId = products.productId
                                            WHERE orderId = :orderId");
        $stmt->bindParam(":orderId", $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $orderItems;
    }

    public function addOrderItem($orderId, $productId, $quantity, $totalPrice)
    {
        $stmt = $this->conn->prepare('INSERT INTO orderItems (orderId, productId, quantity, totalPrice) VALUES (:orderId, :productId, :quantity, :totalPrice)');
        $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':totalPrice', $totalPrice, PDO::PARAM_STR);
        $stmt->execute();

        return $this->conn->lastInsertId();
    }
}
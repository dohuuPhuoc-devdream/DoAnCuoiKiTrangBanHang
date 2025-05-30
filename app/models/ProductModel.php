<?php
require_once 'app/models/BaseModel.php';
require_once 'app/models/ModelInterface.php';
require_once 'app/models/ProductImageModel.php';

class ProductModel extends BaseModel implements ModelInterface
{
    private $productImageModel;

    public function __construct(PDO $conn, ProductImageModel $productImageModel)
    {
        parent::__construct($conn);
        $this->productImageModel = $productImageModel;
    }

    // Polymorphic method from ModelInterface
    public function getAll()
    {
        return $this->getAllProducts();
    }

    public function getProductById($productId)
    {
        try {
            if (!$productId || !is_numeric($productId)) {
                error_log("Invalid productId provided to getProductById: " . $productId);
                return null;
            }

            $stmt = $this->conn->prepare("SELECT * FROM products
                                INNER JOIN categories ON categories.categoryId = products.categoryId 
                                WHERE productId = :productId");
            $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $product['images'] = $this->productImageModel->getAllProductImageByProductId($productId);
            }

            return $product;
        } catch (PDOException $e) {
            error_log("Database error in getProductById: " . $e->getMessage());
            return null;
        }
    }

    public function getProductDetailById($productId)
    {
        $stmt = $this->conn->prepare("SELECT *, views + 1 AS updatedViews
                                            FROM products
                                            INNER JOIN categories ON categories.categoryId = products.categoryId 
                                            WHERE productId = :productId
                                            FOR UPDATE;
                                        ");
        $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        $product['images'] = $this->productImageModel->getAllProductImageByProductId($productId);

        // Update views
        $updateStmt = $this->conn->prepare("UPDATE products SET views = views + 1 WHERE productId = :productId");
        $updateStmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $updateStmt->execute();

        return $product;
    }

    public function getAllProducts($params = [])
    {
        try {
            $defaults = [
                'order' => 'desc',
                'order_by' => 'createdAt',
                'search' => '',
                'categoryId' => null,
                'price_start' => null,
                'price_end' => null,
                'page' => 1,
                'limit' => 9,
            ];

            $params = array_merge($defaults, $params);

            // Validate and sanitize input parameters
            $order = strtoupper($params['order']) === 'DESC' ? 'DESC' : 'ASC';
            $allowed_order_fields = ['createdAt', 'price', 'productName', 'views'];
            $order_by = in_array($params['order_by'], $allowed_order_fields) ? $params['order_by'] : 'createdAt';

            $search = trim($params['search']);
            $categoryId = filter_var($params['categoryId'], FILTER_VALIDATE_INT);
            $price_start = filter_var($params['price_start'], FILTER_VALIDATE_INT);
            $price_end = filter_var($params['price_end'], FILTER_VALIDATE_INT);
            $page = max(1, filter_var($params['page'], FILTER_VALIDATE_INT, ['options' => ['default' => 1]]));
            $limit = max(1, min(50, filter_var($params['limit'], FILTER_VALIDATE_INT, ['options' => ['default' => 9]])));
            $offset = ($page - 1) * $limit;

            $query = "SELECT * FROM products INNER JOIN categories ON products.categoryId = categories.categoryId";
            $conditions = [];
            $bindings = [];

            if ($categoryId !== false && $categoryId > 0) {
                $conditions[] = "products.categoryId = :categoryId";
                $bindings[':categoryId'] = $categoryId;
            }
            if (!empty($search)) {
                $conditions[] = "products.productName LIKE :search";
                $bindings[':search'] = '%' . $search . '%';
            }
            if ($price_start !== false && $price_start >= 0) {
                $conditions[] = "products.price >= :price_start";
                $bindings[':price_start'] = $price_start;
            }
            if ($price_end !== false && $price_end > 0) {
                $conditions[] = "products.price <= :price_end";
                $bindings[':price_end'] = $price_end;
            }

            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }

            $query .= " ORDER BY products.$order_by $order, productId DESC";
            $query .= " LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($query);
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($products) {
                foreach ($products as $key => $product) {
                    $products[$key]['images'] = $this->productImageModel->getAllProductImageByProductId($product['productId']);
                }
            }

            return $products;
        } catch (PDOException $e) {
            error_log("Database error in getAllProducts: " . $e->getMessage());
            return [];
        }
    }

    public function getLatestProducts($limit = 4)
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare("SELECT * FROM products ORDER BY createdAt DESC LIMIT $limit");
        $stmt->execute();
        $latestProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($latestProducts as $key => $product) {
            $latestProducts[$key]['images'] = $this->productImageModel->getAllProductImageByProductId($product['productId']);
        }

        return $latestProducts;
    }

    public function getPopularProducts($limit = 4)
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare("SELECT * FROM products ORDER BY views DESC LIMIT $limit");
        $stmt->execute();
        $popularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($popularProducts as $key => $product) {
            $popularProducts[$key]['images'] = $this->productImageModel->getAllProductImageByProductId($product['productId']);
        }

        return $popularProducts;
    }

    public function getRelativeProducts($productId, $categoryId, $limit = 6)
    {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE productId <> :productId AND categoryId = :categoryId ORDER BY views DESC LIMIT :limit");
        $stmt->bindParam(":productId", $productId, PDO::PARAM_INT);
        $stmt->bindParam(":categoryId", $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        $relativeProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($relativeProducts as $key => $product) {
            $relativeProducts[$key]['images'] = $this->productImageModel->getAllProductImageByProductId($product['productId']);
        }
        return $relativeProducts;
    }

    public function getBestSellerProducts($limit = 4)
    {
        $limit = (int) $limit;
        $stmt = $this->conn->prepare("SELECT p.*, SUM(oi.quantity) AS totalSold
                                                FROM products p
                                                INNER JOIN orderItems oi ON p.productId = oi.productId
                                                GROUP BY p.productId
                                                ORDER BY totalSold DESC
                                                LIMIT $limit");
        $stmt->execute();
        $bestSellerProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bestSellerProducts as $key => $product) {
            $bestSellerProducts[$key]['images'] = $this->productImageModel->getAllProductImageByProductId($product['productId']);
        }
        return $bestSellerProducts;
    }

    public function addProduct($productName, $productDesc, $price, $categoryId, $stock, $images)
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("INSERT INTO products (productName, productDesc, price, categoryId, stock) VALUES (:productName, :productDesc, :price, :categoryId, :stock)");

            $stmt->bindParam(':productName', $productName, PDO::PARAM_STR);
            $stmt->bindParam(':productDesc', $productDesc, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
            $stmt->execute();

            $productId = $this->conn->lastInsertId();

            if ($productId) {
                if (!empty($images)) {
                    foreach ($images as $image) {
                        $result = $this->productImageModel->addProductImage($productId, $image);
                        if (!$result) {
                            throw new Exception('Failed to add image');
                        }
                    }
                }
            }
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updateProduct($productId, $productName, $productDesc, $price, $categoryId, $stock, $images)
    {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("UPDATE products SET productName = :productName, productDesc = :productDesc, price = :price, categoryId = :categoryId, stock = :stock WHERE productId = :productId");

            $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':productName', $productName, PDO::PARAM_STR);
            $stmt->bindParam(':productDesc', $productDesc, PDO::PARAM_STR);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR);
            $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
            $stmt->execute();

            $stmtCart = $this->conn->prepare("UPDATE carts 
                                      SET totalPrice = quantity * :price 
                                      WHERE productId = :productId");
            $stmtCart->bindParam(':price', $price, PDO::PARAM_STR);
            $stmtCart->bindParam(':productId', $productId, PDO::PARAM_INT);
            $stmtCart->execute();

            if ($productId) {
                if (!empty($images)) {
                    foreach ($images as $image) {
                        $result = $this->productImageModel->addProductImage($productId, $image);
                        if (!$result) {
                            throw new Exception('Failed to add image');
                        }
                    }
                }
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            echo '' . $e->getMessage();
            return false;
        }
    }

    public function updateProductStock($productId, $quantityReduce)
    {
        $stmt = $this->conn->prepare("UPDATE products SET stock = stock - :quantityReduce WHERE productId = :productId");

        $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':quantityReduce', $quantityReduce, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function deleteProduct($productId)
    {
        $stmt = $this->conn->prepare("DELETE FROM products WHERE productId = :productId");
        $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
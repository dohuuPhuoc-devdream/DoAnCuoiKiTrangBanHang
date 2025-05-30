<?php
require_once 'app/models/BaseModel.php';
require_once 'app/models/User.php';

class UserModel extends BaseModel
{
    protected $table = 'users';

    /**
     * Constructor with dependency injection.
     * @param PDO $conn
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserById($userId)
    {
        try {
            $userData = $this->findById($userId);
            if (!$userData) {
                return null;
            }
            return new User($userData);
        } catch (Exception $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }

    public function getUserDetailByUserId($userId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, userName, email, phone, city, district, ward, street
                FROM {$this->table}
                WHERE id = :userId
            ");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug log
            error_log("getUserDetailByUserId - Raw data: " . print_r($userData, true));
            
            if (!$userData) {
                error_log("No user found for ID: " . $userId);
                return null;
            }
            
            $user = new User($userData);
            error_log("getUserDetailByUserId - User object: " . print_r($user->toArray(), true));
            
            return $user;
        } catch (Exception $e) {
            error_log("Error in getUserDetailByUserId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    public function getUserDetailByAdmin($userId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT users.*, COUNT(orders.orderId) AS totalOrders, 
                    IFNULL(SUM(orders.orderTotal + orders.shippingCost), 0) AS totalSpent
                FROM users
                LEFT JOIN orders ON users.id = orders.userId
                WHERE id = :userId
            ");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug log
            error_log("getUserDetailByUserId - Raw data: " . print_r($userData, true));
            
            if (!$userData) {
                error_log("No user found for ID: " . $userId);
                return null;
            }
            
            return $userData;
        } catch (Exception $e) {
            error_log("Error in getUserDetailByUserId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }
    public function registerUser($email, $password)
    {
        $stmt = $this->conn->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    public function getAllUsers()
    {
        $stmt = $this->conn->prepare(
            "SELECT users.*, COUNT(orders.orderId) AS totalOrders, 
                    IFNULL(SUM(orders.orderTotal + orders.shippingCost), 0) AS totalSpent
             FROM users
             LEFT JOIN orders ON users.id = orders.userId
             WHERE isAdmin = 0
             GROUP BY users.id
             ORDER BY users.id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addUser($email, $password, $userName)
    {
        $stmt = $this->conn->prepare("INSERT INTO users (userName, email, password) VALUES (:userName, :email, :password)");
        $stmt->bindParam(':userName', $userName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->execute();
        return $this->conn->lastInsertId();
    }

    public function updateUserContact($userId, array $contactData)
    {
        try {
            $user = $this->getUserById($userId);
            if (!$user) {
                return false;
            }

            // Update user object with new contact data
            if (isset($contactData['userName'])) $user->setUserName($contactData['userName']);
            if (isset($contactData['phone'])) $user->setPhone($contactData['phone']);
            if (isset($contactData['city'])) $user->setCity($contactData['city']);
            if (isset($contactData['district'])) $user->setDistrict($contactData['district']);
            if (isset($contactData['ward'])) $user->setWard($contactData['ward']);
            if (isset($contactData['street'])) $user->setStreet($contactData['street']);

            // Update in database
            return $this->update($userId, $user->toArray());
        } catch (Exception $e) {
            error_log("Error in updateUserContact: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($userId, $userName, $phone, $city, $district, $ward, $street, $image)
    {
        try {
            $stmt = $this->conn->prepare(
                "UPDATE users SET userName = :userName, phone = :phone, city = :city, district = :district, ward = :ward, street = :street, image = :image WHERE id = :userId"
            );
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':userName', $userName, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':city', $city, PDO::PARAM_STR);
            $stmt->bindParam(':district', $district, PDO::PARAM_STR);
            $stmt->bindParam(':ward', $ward, PDO::PARAM_STR);
            $stmt->bindParam(':street', $street, PDO::PARAM_STR);
            $stmt->bindParam(':image', $image, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            echo '' . $e->getMessage();
            return null;
        }
    }

    public function updatePassword($userId, $password)
    {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET password = :password WHERE id = :userId");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            echo '' . $e->getMessage();
            return false;
        }
    }

    public function deleteUser($userId)
    {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :userId");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }
}
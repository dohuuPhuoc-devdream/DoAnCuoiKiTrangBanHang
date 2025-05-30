<?php
require_once 'app/models/UserModel.php';
require_once 'app/models/OrderModel.php';

class UserController
{
    private $userModel;
    private $orderModel;

    /**
     * Constructor with dependency injection.
     * @param UserModel $userModel
     * @param OrderModel $orderModel
     */
    public function __construct(UserModel $userModel, OrderModel $orderModel)
    {
        $this->userModel = $userModel;
        $this->orderModel = $orderModel;
    }

    public function userAdmin()
    {
        $users = $this->userModel->getAllUsers();
        $page = 'users';
        $view = 'app/views/admin/users/users.php';
        require_once 'app/views/admin/adminLayout.php';
    }

    public function detailUser()
    {
        if (isset($_GET['userId'])) {
            $userId = $_GET['userId'];
            $user = $this->userModel->getUserDetailByAdmin($userId);
            $view = 'app/views/admin/users/detailUser.php';
            require_once 'app/views/admin/adminLayout.php';
        } else {
            header('Location: notfound');
        }
    }

    public function addUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $view = 'app/views/admin/users/addUser.php';
            $action = 'add';
            require_once 'app/views/admin/adminLayout.php';
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $userName = $_POST['userName'];

            $existUser = $this->userModel->getUserByEmail($email);
            if ($existUser) {
                setErrorMessage('Email đã tồn tại');
                header('Location: add-user');
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $result = $this->userModel->addUser($email, $hashedPassword, $userName);
                if ($result) {
                    setSuccessMessage('Thêm người dùng thành công');
                    header('Location: users');
                } else {
                    setErrorMessage('Thêm người dùng thất bại');
                    header('Location: add-user');
                }
            }
        }
    }

    public function updateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if (isset($_GET['userId']) && ctype_digit($_GET['userId'])) {
                $user = $this->userModel->getUserById($_GET['userId']);
                error_log("user data: " . print_r($user, true));
                $view = 'app/views/admin/users/addUser.php';
                $action = 'update';
                require_once 'app/views/admin/adminLayout.php';
            } else {
                header('Location: notfound');
            }
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['userId'])) {
            $userId = $_GET['userId'];
            $userName = $_POST['userName'];
            $phone = $_POST['phone'];
            $city = $_POST['city'];
            $district = $_POST['district'];
            $ward = $_POST['ward'];
            $street = $_POST['street'];

            $imagePath = '';
            if (!empty($_FILES['image']['name'])) {
                $uploadDir = 'uploads/users/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $tmpName = $_FILES['image']['tmp_name'];
                $uniqueName = uniqid() . '-' . basename($_FILES['image']['name']);
                $destination = $uploadDir . $uniqueName;
                if (move_uploaded_file($tmpName, $destination)) {
                    $imagePath = $destination;
                    $user = $this->userModel->getUserById($userId);
                    if (!empty($user['image']) && file_exists($user['image'])) {
                        unlink($user['image']);
                    }
                }
            }

            $result = $this->userModel->updateUser($userId, $userName, $phone, $city, $district, $ward, $street, $imagePath);

            if (is_int($result)) {
                setSuccessMessage('Cập nhật người dùng thành công');
                header('Location: users');
            } else {
                setErrorMessage('Cập nhật không thành công');
            }
        } else {
            header('Location: notfound');
        }
    }

    public function deleteUser()
    {
        if (isset($_GET["userId"])) {
            $userId = $_GET["userId"];
            $result = $this->userModel->deleteUser($userId);

            if ($result > 0) {
                setSuccessMessage('Xóa người dùng thành công');
            } else {
                setErrorMessage('Xóa người dùng thất bại');
            }
            header("Location: users");
        } else {
            header('Location: notfound');
        }
    }

    public function profile()
    {
        try {
            initSession();
            
            // Nếu chưa đăng nhập, chuyển hướng đến trang login
            if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
                header('Location: ' . BASE_PATH . '/login');
                exit();
            }
            
            $userId = $_SESSION['userId'];
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                setErrorMessage('Không tìm thấy thông tin người dùng');
                header('Location: ' . BASE_PATH . '/login');
                exit();
            }

            $orders = $this->orderModel->getAllOrdersByUserId($userId);
            $page = 'me';
            $view = 'app/views/user/me/profile.php';
            require_once 'app/views/layout.php';
        } catch (Exception $e) {
            error_log("Error in profile: " . $e->getMessage());
            setErrorMessage('Có lỗi xảy ra khi tải thông tin người dùng');
            header('Location: ' . BASE_PATH . '/login');
            exit();
        }
    }

    public function updateProfile()
    {
        try {
            initSession();
            requireLogin();
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $user = $this->userModel->getUserById($_SESSION['userId']);
                if (!$user) {
                    setErrorMessage('Không tìm thấy thông tin người dùng');
                    header('Location: ' . BASE_PATH . '/me');
                    exit();
                }
                $view = 'app/views/user/me/updateProfile.php';
                require_once 'app/views/layout.php';
            }
            // POST
            elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = $_SESSION['userId'];
                $user = $this->userModel->getUserById($userId);
                if (!$user) {
                    setErrorMessage('Không tìm thấy thông tin người dùng');
                    header('Location: ' . BASE_PATH . '/me');
                    exit();
                }

                // Cập nhật thông tin user
                $user->setUserName($_POST['userName']);
                $user->setPhone($_POST['phone']);
                $user->setCity($_POST['city']);
                $user->setDistrict($_POST['district']);
                $user->setWard($_POST['ward']);
                $user->setStreet($_POST['street']);

                // Xử lý upload ảnh
                $imagePath = $user->getImage();
                if (!empty($_FILES['image']['name'])) {
                    $uploadDir = 'uploads/users/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $tmpName = $_FILES['image']['tmp_name'];
                    $uniqueName = uniqid() . '-' . basename($_FILES['image']['name']);
                    $destination = $uploadDir . $uniqueName;
                    if (move_uploaded_file($tmpName, $destination)) {
                        // Xóa ảnh cũ nếu có
                        $oldImage = $user->getImage();
                        if (!empty($oldImage) && file_exists($oldImage)) {
                            unlink($oldImage);
                        }
                        $user->setImage($destination);
                    }
                }

                // Cập nhật vào database
                $result = $this->userModel->updateUserContact($userId, $user->toArray());

                if ($result) {
                    setSuccessMessage('Cập nhật tài khoản thành công');
                    header('Location: ' . BASE_PATH . '/me');
                } else {
                    setErrorMessage('Cập nhật không thành công');
                    header('Location: ' . BASE_PATH . '/update-profile');
                }
            }
        } catch (Exception $e) {
            error_log("Error in updateProfile: " . $e->getMessage());
            setErrorMessage('Có lỗi xảy ra khi cập nhật thông tin');
            header('Location: ' . BASE_PATH . '/me');
            exit();
        }
    }

    public function updateContactApi()
    {
        header('Content-Type: application/json');
        try {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            $userId = $_SESSION['userId'];
            $userName = $data['userName'] ?? '';
            $phone = $data['phone'] ?? '';
            $city = $data['city'] ?? '';
            $district = $data['district'] ?? '';
            $ward = $data['ward'] ?? '';
            $street = $data['street'] ?? '';

            $result = $this->userModel->updateUserContact($userId, $userName, $phone, $city, $district, $ward, $street);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cập nhật thành công',
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Không có gì cập nhật',
                    'unchange' => true,
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function updatePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $view = 'app/views/user/me/updatePassword.php';
            require_once 'app/views/layout.php';
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['userId'];
            $oldPassword = $_POST['oldPassword'];
            $newPassword = $_POST['newPassword'];

            $user = $this->userModel->getUserById($userId);
            if (!password_verify($oldPassword, $user['password'])) {
                setErrorMessage('Mật khẩu không chính xác');
                header('Location: update-password');
                exit();
            } else {
                $password = password_hash($newPassword, PASSWORD_DEFAULT);
                $result = $this->userModel->updatePassword($userId, $password);
                if ($result) {
                    setSuccessMessage('Cập nhật mật khẩu thành công');
                    header('Location: me');
                } else {
                    setErrorMessage('Cập nhật thất bại');
                    header('Location: update-password');
                }
            }
        }
    }
}
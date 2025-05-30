<?php
require_once 'app/models/UserModel.php';
require_once 'app/utils/constants.php';
require_once 'app/utils/flashMessage.php';

class AuthController
{
    private $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function login()
    {
        initSession();
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $view = 'app/views/user/login.php';
            require_once 'app/views/layout.php';
        }
        //POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                setErrorMessage('Vui lòng điền đầy đủ thông tin');
                header('Location: login');
                exit;
            }

            $existUser = $this->userModel->getUserByEmail($email);
            
            // Debug login attempt
            var_dump([
                'login_attempt' => [
                    'email' => $email,
                    'user_exists' => !empty($existUser),
                    'session_id' => session_id()
                ]
            ]);

            if (!$existUser) {
                setErrorMessage('Tài khoản chưa tồn tại');
                header('Location: login');
                exit;
            }

            $userPassword = $existUser['password'];
            if (password_verify($password, $userPassword)) {
                // Đảm bảo session được khởi tạo và lưu thông tin đăng nhập
                $_SESSION['auth'] = true;
                $_SESSION['userId'] = $existUser['id'];
                session_write_close(); // Đảm bảo session được lưu

                // Debug session after login
                var_dump([
                    'session_after_login' => [
                        'session_id' => session_id(),
                        'auth' => $_SESSION['auth'] ?? null,
                        'userId' => $_SESSION['userId'] ?? null
                    ]
                ]);

                setSuccessMessage('Đăng nhập thành công');
                header('Location: ' . BASE_PATH . '/');
                exit;
            } else {
                setErrorMessage('Mật khẩu không đúng');
                header('Location: login');
                exit;
            }
        }
    }

    public function logout()
    {
        initSession();
        
        // Debug session before logout
        var_dump([
            'session_before_logout' => [
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]
        ]);

        // Xóa toàn bộ session
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        // Debug after session destruction
        var_dump([
            'session_after_logout' => [
                'session_exists' => session_status() === PHP_SESSION_ACTIVE,
                'session_id' => session_id()
            ]
        ]);

        header('Location: login');
        exit();
    }

    public function signup()
    {
        //GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $view = 'app/views/user/signup.php';
            require_once 'app/views/layout.php';
        }
        //POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $existUser = $this->userModel->getUserByEmail($email);
            if ($existUser) {
                setErrorMessage('Tài khoản đã tồn tại');
                header('location: signup');
                exit;
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $result = $this->userModel->registerUser($email, $hashedPassword);
                setSuccessMessage('Đăng ký thành công');
                header('Location: login');
                exit;
            }
        }
    }

    public function loginAdmin()
    {
        //GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $disabledSidebar = true;
            $view = 'app/views/admin/login.php';
            require_once 'app/views/admin/adminLayout.php';
        }
        //POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $existUser = $this->userModel->getUserByEmail($email);
            if (!$existUser || !$existUser['isAdmin']) {
                setErrorMessage('Tài khoản chưa tồn tại');
                header('location: login');
                exit;
            } else {
                $userPassword = $existUser['password'];
                if (password_verify($password, $userPassword)) {
                    $_SESSION['authAdmin'] = true;
                    setSuccessMessage('Đăng nhập thành công');
                    header('Location: ' . BASE_PATH . '/admin');
                    exit;
                } else {
                    setErrorMessage('Mật khẩu không đúng');
                    header('location: login');
                    exit;
                }
            }
        }
    }

    public function logoutAdmin()
    {
        unset($_SESSION['authAdmin']);
        header('Location: login');
    }
}
<?php
// Hiển thị tất cả lỗi PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Configuration (phải đặt trước session_start)
ini_set('session.save_handler', 'files');
ini_set('session.save_path', 'C:/xampp/tmp');
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

session_start();

// Thêm ngay sau session_start();
error_log("Session when accessing homepage: " . print_r($_SESSION, true));

require_once "app/config/config.php";
require_once "app/utils/constants.php";
require_once "app/utils/format.php";
require_once "app/config/DatabaseConnection.php";
require_once "app/utils/sessionHelper.php";

$db = new DatabaseConnection();
$pdo = $db->getConnection();

require_once "app/models/ProductImageModel.php";
$productImageModel = new ProductImageModel($pdo);

require_once "app/models/ProductModel.php";
$productModel = new ProductModel($pdo, $productImageModel);

require_once "app/models/CartModel.php";
$cartModel = new CartModel($pdo, $productImageModel);

require_once "app/models/UserModel.php";
$userModel = new UserModel($pdo);

require_once "app/models/CategoryModel.php";
$categoryModel = new CategoryModel($pdo);

require_once "app/models/OrderItemModel.php";
$orderItemModel = new OrderItemModel($pdo);

require_once "app/models/OrderModel.php";
$orderModel = new OrderModel($pdo, $orderItemModel);

require_once "app/controllers/CartController.php";
$cartController = new CartController($cartModel, $productModel);

require_once "app/controllers/CategoryController.php";
$categoryController = new CategoryController($categoryModel);

require_once "app/controllers/ProductController.php";
$productController = new ProductController($productModel, $categoryModel, $productImageModel);

require_once "app/controllers/UserController.php";
$userController = new UserController($userModel, $orderModel);

require_once "app/controllers/GeneralController.php";
$generalController = new GeneralController($productModel, $categoryModel);

require_once "app/controllers/AuthController.php";
$authController = new AuthController($userModel);

require_once "app/controllers/OrderController.php"; // hoặc đúng đường dẫn tới file

$orderController = new OrderController($orderModel, $cartModel, $userModel); // Tạo object từ controller


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace(BASE_PATH, '', $uri);
// // Debug output
// echo "<pre>";
// echo "DEBUG - BASE_PATH: " . BASE_PATH . "\n";
// echo "DEBUG - REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
// echo "DEBUG - PARSED URI: " . $uri . "\n";
// echo "</pre>";
initSession();

function isAuthenticationAdmin() //check if user is login or not
{
    return $_SESSION['authAdmin'] === true;
}

function isAuthentication()
{
    return isset($_SESSION['auth']) && $_SESSION['auth'] === true && isset($_SESSION['userId']);
}

if (in_array($uri, PROTECTED_ROUTES)) { //if uri in protectedRoutes => check login
    //route admin and not authentication for admin
    if (strpos($uri, '/admin') === 0 && !isAuthenticationAdmin()) {
        header("Location: " . BASE_PATH . "/admin/login");
        exit();
    } else if (strpos($uri, '/admin') !== 0 && !isAuthentication()) {
        header("Location: " . BASE_PATH . "/login");
        exit();
    }
} elseif (in_array($uri, API_PROTECTED_ROUTES)) {
    if (strpos($uri, '/admin') === 0 && !isAuthenticationAdmin()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'require login',
        ]);
        exit();
    } else if (!isAuthentication()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'require login',
        ]);
        exit();
    }
}

switch ($uri) {
    //admin page
    case '/admin':
        $generalController->dashboard();
        break;

    //admin login
    case '/admin/login':
        $authController->loginAdmin();
        break;
    case '/admin/logout':
        $authController->logoutAdmin();
        break;
    case '/admin/update-password':
        echo $uri;
        break;


    //admin manage product
    case '/admin/products':
        $productController->productAdmin();
        break;
    case '/admin/add-product':
        $productController->addProduct();
        break;
    case '/admin/update-product':
        $productController->updateProduct();
        break;
    case '/admin/delete-product':
        $productController->deleteProduct();
        break;

    //admin manage categories
    case '/admin/categories':
        $categoryController->categoryAdmin();
        break;
    case '/admin/add-category':
        $categoryController->addCategory();
        break;
    case '/admin/update-category':
        $categoryController->updateCategory();
        break;
    case '/admin/delete-category':
        $categoryController->deleteCategory();
        break;

    //admin manage orders
    case '/admin/orders':
        $orderController->orderAdmin();
        break;
    case '/admin/detail-order':
        $orderController->detailOrder();
        break;
    case '/admin/update-order-status':
        $orderController->updateOrderStatus();
        break;

    //admin manage users
    case '/admin/users':
        $userController->userAdmin();
        break;
    case '/admin/detail-user':
        $userController->detailUser();
        break;
    case '/admin/add-user':
        $userController->addUser();
        break;
    case '/admin/update-user':
        $userController->updateUser();
        break;
    case '/admin/delete-user':
        $userController->deleteUser();
        break;

    //user page
    case '/login':
        $authController->login();
        break;
    case '/signup':
        $authController->signup();
        break;
    case '/logout':
        $authController->logout();
        break;


    case '/shop':
        $productController->shop();
        break;
    case '/api/shop':
        $productController->shopApi();
        break;
    case '/detail':
        $productController->detail();
        break;

    case '/me':
        error_log("Session when accessing /me: " . print_r($_SESSION, true));
        $userController->profile();
        break;
    case '/update-profile':
        $userController->updateProfile();
        break;
    case '/update-password':
        $userController->updatePassword();
        break;
    case '/update-image':
        $userController->profile();
        break;
    case '/api/users/update-contact':
        $userController->updateContactApi();
        break;

    case '/order-detail':
        $orderController->orderDetail();
        break;
    case '/checkout':
        $orderController->checkout();
        break;
    case '/checkout-delivery':
        $orderController->checkoutDelivery();
        break;
    case '/checkout-payment':
        $orderController->checkoutPayment();
        break;
    case '/make-order':
        $orderController->makeOrder();
        break;

        case '/carts':
        $cartController->userCart();
        break;
    case '/increase-cart':
        $cartController->increaseCart();
        break;
    case '/decrease-cart':
        $cartController->decreaseCart();
        break;
    case '/delete-cart':
        $cartController->deleteFromCart();
        break;
    case '/api/carts/count':
        $cartController->getCartQuantityApi();
        break;
    case '/api/carts/add':
        $cartController->addToCartApi();
        break;


    case '/about':
        $generalController->about();
        break;
    case '/blogs':
        $generalController->blogs();
        break;
    case '/blog-detail':
        $generalController->blogDetail();
        break;
    case '/contact':
        $generalController->contact();
        break;
    case '/policy':
        $generalController->policy();
        break;
    case '/':
        $generalController->home();
        break;

    default:
        echo '404 Page not found';
}
?>
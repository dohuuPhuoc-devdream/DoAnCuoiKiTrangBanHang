<?php
require_once 'app/utils/sessionHelper.php';
require_once 'app/models/OrderModel.php';
require_once 'app/models/CartModel.php';
require_once 'app/models/UserModel.php';
require_once 'app/models/User.php';

class OrderController
{
    private $orderModel;
    private $cartModel;
    public $userModel;

    /**
     * Constructor with dependency injection.
     * @param OrderModel $orderModel
     * @param CartModel $cartModel
     * @param UserModel $userModel
     */
    public function __construct(OrderModel $orderModel, CartModel $cartModel, UserModel $userModel)
    {
        $this->orderModel = $orderModel;
        $this->cartModel = $cartModel;
        $this->userModel = $userModel;
    }

    public function orderAdmin()
    {
        $orders = $this->orderModel->getAllOrders();
        $page = 'orders';
        $view = 'app/views/admin/orders/orders.php';
        require_once 'app/views/admin/adminLayout.php';
    }

    public function detailOrder()
    {
        if (isset($_GET['orderId'])) {
            $orderId = $_GET['orderId'];
            $order = $this->orderModel->getOrderById($orderId);
            $orderItems = $this->orderModel->getOrderItemsByOrderId($orderId);
            $view = 'app/views/admin/orders/detailOrder.php';
            require_once 'app/views/admin/adminLayout.php';
        } else {
            header('Location: notfound');
        }
    }

    public function updateOrderStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orderId'], $_POST['status'])) {
            $orderId = $_POST['orderId'];
            $status = $_POST['status'];
            $result = $this->orderModel->updateOrderStatus($orderId, $status);

            if ($result) {
                setSuccessMessage('Cập nhật trạng thái đơn hàng thành công');
            } else {
                setErrorMessage('Cập nhật trạng thái đơn hàng thất bại');
            }
            header('Location: admin/orders');
        } else {
            header('Location: notfound');
        }
    }

    public function orderDetail()
    {
        try {
            // Khởi tạo session và kiểm tra đăng nhập
            initSession();
            requireLogin();
            
            $userId = getUserId();
            if (!$userId) {
                header('Location: ' . BASE_PATH . '/login');
                exit();
            }

            if (!isset($_GET['orderId'])) {
                header('Location: notfound');
                exit();
            }

            $orderId = $_GET['orderId'];
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                header('Location: notfound');
                exit();
            }

            // Lấy thông tin user
            $user = $this->userModel->getUserDetailByUserId($userId);
            if (!$user) {
                $user = [
                    'email' => $_SESSION['email'] ?? '',
                    'userName' => $order['userName'] ?? '',
                    'phone' => $order['phone'] ?? ''
                ];
            }

            // Lấy thông tin chi tiết đơn hàng
            $orderItems = $this->orderModel->getOrderItemsByOrderId($orderId);
            
            // Gộp thông tin items vào order
            $order['items'] = $orderItems;
            
            // Set các biến cần thiết cho view
            $page = 'order-detail';
            $view = 'app/views/user/orders/orderDetail.php';
            
            // Kiểm tra file view tồn tại
            if (!file_exists($view)) {
                error_log('View file not found: ' . $view);
                setErrorMessage('Không tìm thấy trang chi tiết đơn hàng');
                header('Location: ' . BASE_PATH . '/orders');
                exit();
            }
            
            // Debug log
            error_log('Debug - Order data: ' . print_r($order, true));
            error_log('Debug - User data: ' . print_r($user, true));
            error_log('Debug - Order items: ' . print_r($orderItems, true));
            
            require_once 'app/views/layout.php';
        } catch (Exception $e) {
            error_log('Error in orderDetail: ' . $e->getMessage());
            setErrorMessage('Có lỗi xảy ra khi xem chi tiết đơn hàng');
            header('Location: ' . BASE_PATH . '/orders');
            exit();
        }
    }

    public function checkout()
    {
        try {
            // 1. Khởi tạo session và kiểm tra đăng nhập
            initSession();
            requireLogin();
            
            $userId = getUserId();
            error_log("Checkout - User ID from session: " . $userId);
            
            if (!$userId) {
                error_log("Checkout - No user ID found in session");
                header('Location: ' . BASE_PATH . '/login');
                exit();
            }
            
            // 2. Lấy thông tin giỏ hàng
            $carts = $this->cartModel->getUserCarts($userId);
            error_log("Checkout - Cart data: " . print_r($carts, true));
            
            if (empty($carts)) {
                error_log("Checkout - Empty cart");
                header('Location: ' . BASE_PATH . '/shop');
                exit();
            }
            
            // 3. Tính tổng tiền đơn hàng
            $orderTotal = $this->cartModel->getCartTotal($userId);
            error_log("Checkout - Order total: " . $orderTotal);
            
            // 4. Debug session
            error_log("Checkout - Session data: " . print_r($_SESSION, true));
            
            // 5. Set view và hiển thị
            $view = 'app/views/user/orders/checkout.php';
            $page = 'checkout';
            
            require_once 'app/views/layout.php';
            
        } catch (Exception $e) {
            error_log("Error in checkout: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            setErrorMessage('Có lỗi xảy ra khi xử lý đơn hàng');
            header('Location: ' . BASE_PATH . '/shop');
            exit();
        }
    }

    public function checkoutDelivery()
    {
        try {
            initSession();
            requireLogin();

            $userId = getUserId();
            if (!$userId) {
                header('Location: ' . BASE_PATH . '/login');
                exit();
            }

            // Nếu là POST request, lưu thông tin giao hàng vào session
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $_SESSION['delivery_info'] = [
                    'userName' => $_POST['userName'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'city' => $_POST['city'] ?? '',
                    'district' => $_POST['district'] ?? '',
                    'ward' => $_POST['ward'] ?? '',
                    'street' => $_POST['street'] ?? '',
                    'note' => $_POST['note'] ?? ''
                ];
            }

            // Lấy thông tin giỏ hàng
            $carts = $this->cartModel->getUserCarts($userId);
            if (empty($carts)) {
                header('Location: ' . BASE_PATH . '/shop');
                exit();
            }

            // Lấy thông tin user
            $user = $this->userModel->getUserDetailByUserId($userId);
            if (!$user) {
                setErrorMessage('Không tìm thấy thông tin người dùng');
                header('Location: ' . BASE_PATH . '/checkout');
                exit();
            }

            // Tính tổng tiền đơn hàng
            $orderTotal = $this->cartModel->getCartTotal($userId);

            // Tính phí vận chuyển
            $shippingCost = $this->calculateShippingCost($orderTotal);

            // Chuẩn bị dữ liệu cho view
            $viewData = [
                'user' => $user->toArray(),
                'carts' => $carts,
                'orderTotal' => $orderTotal,
                'shippingCost' => $shippingCost,
                'page' => 'checkout-delivery',
                'deliveryInfo' => $_SESSION['delivery_info'] ?? null
            ];

            // Extract data để sử dụng trong view
            extract($viewData);
            
            $view = 'app/views/user/orders/deliveryCheckout.php';
            require_once 'app/views/layout.php';

        } catch (Exception $e) {
            error_log('Error in checkoutDelivery: ' . $e->getMessage());
            setErrorMessage('Có lỗi xảy ra khi xử lý thông tin giao hàng');
            header('Location: ' . BASE_PATH . '/checkout');
            exit();
        }
    }

    private function calculateShippingCost($orderTotal) {
        if ($orderTotal >= 3000000) return 0;
        if ($orderTotal >= 2000000) return 10000;
        if ($orderTotal >= 1000000) return 20000;
        return 30000;
    }

    public function checkoutPayment()
    {
        try {
            initSession();
            requireLogin();

            $userId = getUserId();
            if (!$userId) {
                header('Location: ' . BASE_PATH . '/login');
                exit();
            }

            // Lấy thông tin user
            $user = $this->userModel->getUserDetailByUserId($userId);
            if (!$user) {
                setErrorMessage('Không tìm thấy thông tin người dùng');
                header('Location: ' . BASE_PATH . '/checkout');
                exit();
            }

            // Lấy thông tin giỏ hàng
            $carts = $this->cartModel->getUserCarts($userId);
            if (empty($carts)) {
                header('Location: ' . BASE_PATH . '/shop');
                exit();
            }

            // Tính tổng tiền đơn hàng
            $orderTotal = $this->cartModel->getCartTotal($userId);

            // Tính phí vận chuyển
            $shippingCost = 30000; // Giá trị mặc định
            if ($orderTotal < 1000000) {
                $shippingCost = 30000;
            } else if ($orderTotal < 2000000) {
                $shippingCost = 20000;
            } else if ($orderTotal < 3000000) {
                $shippingCost = 10000;
            } else {
                $shippingCost = 0; // Miễn phí ship cho đơn >= 3 triệu
            }

            // Chuẩn bị dữ liệu cho view
            $viewData = [
                'user' => $user,
                'carts' => $carts,
                'orderTotal' => $orderTotal,
                'shippingCost' => $shippingCost,
                'page' => 'checkout-payment'
            ];

            // Extract data để sử dụng trong view
            extract($viewData);
            
            $view = 'app/views/user/orders/paymentCheckout.php';
            require_once 'app/views/layout.php';

        } catch (Exception $e) {
            error_log('Error in checkoutPayment: ' . $e->getMessage());
            setErrorMessage('Có lỗi xảy ra khi xử lý thanh toán');
            header('Location: ' . BASE_PATH . '/checkout-delivery');
            exit();
        }
    }

    public function makeOrder()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                initSession();
                
                // Lấy userId trực tiếp từ session, không dùng getUserId()
                $userId = $_SESSION['userId'] ?? null;

                // Lấy thông tin giao hàng từ session
                $deliveryInfo = $_SESSION['delivery_info'] ?? null;
                if (!$deliveryInfo) {
                    throw new Exception('Delivery information not found');
                }

                // Lấy thông tin từ form
                $paymentMethod = $_POST['paymentMethod'] ?? 'COD';
                
                // Lấy giỏ hàng
                $carts = $this->cartModel->getUserCarts($userId);
                if (empty($carts)) {
                    throw new Exception('Cart is empty');
                }

                // Tính tổng tiền và phí ship
                $orderTotal = $this->cartModel->getCartTotal($userId);
                $shippingCost = $this->calculateShippingCost($orderTotal);

                // Tạo đơn hàng với thông tin từ delivery_info
                $orderId = $this->orderModel->createOrder(
                    $userId,                         // userId
                    $deliveryInfo['userName'],      // userName
                    'pending',                      // status
                    $orderTotal,                    // orderTotal
                    $shippingCost,                  // shippingCost
                    $deliveryInfo['city'],          // city
                    $deliveryInfo['district'],      // district
                    $deliveryInfo['ward'],          // ward
                    $deliveryInfo['street'],        // street
                    $deliveryInfo['phone'],         // phone
                    $deliveryInfo['note'] ?? '',    // note
                    $paymentMethod,                 // paymentMethod
                    $carts                          // products
                );

                if ($orderId) {
                    // Xóa giỏ hàng
                    $this->cartModel->clearCartByUserId($userId);
                    
                    // Thông báo thành công và chuyển hướng
                    setSuccessMessage('Đặt hàng thành công');
                    header('Location: ' . BASE_PATH . '/me');
                    exit();
                } else {
                    throw new Exception('Failed to create order');
                }
            } else {
                header('Location: ' . BASE_PATH . '/notfound');
                exit();
            }
        } catch (Exception $e) {
            error_log('Error in makeOrder: ' . $e->getMessage());
            setErrorMessage('Đặt hàng thất bại: ' . $e->getMessage());
            header('Location: ' . BASE_PATH . '/checkout');
            exit();
        }
    }
}
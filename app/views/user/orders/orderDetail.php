<link rel="stylesheet" href="<?php echo BASE_PATH . '/app/public/css/style3.css' ?>">

<?php
// Kiểm tra và gán giá trị mặc định cho các biến
$order = is_array($order) ? $order : [];
$orderItems = $order['items'] ?? [];

// Xử lý trạng thái đơn hàng
$status = '';
$class = '';
switch ($order['status'] ?? '') {
    case 'ordered':
        $class = 'pending';
        $status = 'Đã đặt hàng';
        break;
    case 'processing':
        $class = 'pending';
        $status = 'Đang xử lý';
        break;
    case 'delivering':
        $class = 'pending';
        $status = 'Đang vận chuyển';
        break;
    case 'completed':
        $class = 'completed';
        $status = 'Đã hoàn tất';
        break;
    default:
        $class = '';
        $status = 'Chưa xác định';
}

// Format thông tin thanh toán
$paymentMethod = $order['paymentMethod'] ?? 'COD';
$paymentMethodText = $paymentMethod === 'VNPAY' ? 'Thanh toán qua VNPAY' : 'Thanh toán khi nhận hàng';
?>

<div class="order-container">
    <!-- Order Header -->
    <div class="order-header">
        <h2>Đơn hàng #<?php echo htmlspecialchars($order['orderId'] ?? ''); ?></h2>
        <span class="status <?php echo $class ?>"><?php echo $status ?></span>
    </div>

    <!-- Billing and Shipping Details -->
    <div class="details-container">
        <div class="details-section">
            <h3>Chi tiết đơn hàng</h3>
            <p><strong><?php echo htmlspecialchars($order['userName'] ?? ''); ?></strong></p>
            <p>
                <?php 
                $address = array_filter([
                    $order['street'] ?? '',
                    $order['ward'] ?? '',
                    $order['district'] ?? '',
                    $order['city'] ?? ''
                ]);
                echo htmlspecialchars(implode(', ', $address));
                ?>
            </p>
            <p>Email: <a href="mailto:<?php echo htmlspecialchars($order['email'] ?? ''); ?>"><?php echo htmlspecialchars($order['email'] ?? ''); ?></a></p>
            <p>Phone: <a href="tel:<?php echo htmlspecialchars($order['phone'] ?? ''); ?>"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></a></p>
            <p>Phương thức thanh toán<br><?php echo htmlspecialchars($paymentMethodText); ?></p>
        </div>

        <div class="details-section">
            <h3>Thông tin vận chuyển</h3>
            <p><strong><?php echo htmlspecialchars($order['userName'] ?? ''); ?></strong></p>
            <p>
                <?php echo htmlspecialchars(implode(', ', $address)); ?>
            </p>
            <p><strong>Chi phí vận chuyển</strong><br><?php echo formatCurrencyVND($order['shippingCost'] ?? 0); ?></p>
        </div>
    </div>

    <!-- Product Section -->
    <div class="product-section">
        <h3>Sản phẩm</h3>
        <?php if (!empty($orderItems)): ?>
            <?php foreach ($orderItems as $item): ?>
                <div class="product-item">
                    <div class="product-name">
                        <?php echo htmlspecialchars($item['productName'] ?? ''); ?>
                    </div>
                    <div class="product-quantity">x<?php echo (int)($item['quantity'] ?? 0); ?></div>
                    <div class="product-total"><?php echo formatCurrencyVND($item['totalPrice'] ?? 0); ?></div>
                </div>
            <?php endforeach; ?>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <span><?php echo formatCurrencyVND($order['orderTotal'] ?? 0); ?></span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển:</span>
                    <span><?php echo formatCurrencyVND($order['shippingCost'] ?? 0); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Tổng cộng:</span>
                    <span><?php echo formatCurrencyVND($order['orderTotal'] + $order['shippingCost'] ?? 0); ?></span>
                </div>
            </div>
        <?php else: ?>
            <p class="no-items">Không có sản phẩm nào trong đơn hàng</p>
        <?php endif; ?>
    </div>

    <!-- Buttons -->
    <div class="button-section">
        <button class="btn btn-secondary" onclick="history.back()">Quay về</button>
    </div>
</div>

<style>
.order-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-header h2 {
    margin: 0;
    color: #333;
}

.status {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
}

.status.pending {
    background: #fff3cd;
    color: #856404;
}

.status.completed {
    background: #d4edda;
    color: #155724;
}

.details-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.details-section {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.details-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #333;
}

.details-section p {
    margin: 10px 0;
    color: #666;
}

.details-section a {
    color: #007bff;
    text-decoration: none;
}

.product-section {
    margin-top: 30px;
}

.product-section h3 {
    margin-bottom: 20px;
    color: #333;
}

.product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.product-name {
    flex: 2;
    font-weight: 500;
}

.product-quantity {
    flex: 1;
    text-align: center;
    color: #666;
}

.product-total {
    flex: 1;
    text-align: right;
    font-weight: 500;
    color: #333;
}

.order-summary {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
    color: #666;
}

.summary-row.total {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #eee;
    font-weight: bold;
    color: #333;
}

.button-section {
    margin-top: 30px;
    text-align: right;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.no-items {
    text-align: center;
    color: #666;
    padding: 20px;
}
</style>
<!-- 
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#btn-return').on('click', function () {
            window.location = 'orders';
        });
    });
</script> -->
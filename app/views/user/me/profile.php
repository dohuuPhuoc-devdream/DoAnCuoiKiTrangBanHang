<?php
// Đảm bảo $user là một instance của User class
if (!($user instanceof User)) {
    $user = new User((array)$user);
}
?>
<link rel="stylesheet" href="<?php echo BASE_PATH . '/app/public/css/style3.css' ?>">

<div class="customer-container">
    <div class="customer-management">
        <!-- Left Section: Customer Info -->
        <div class="customer-info">
            <img src="<?php echo htmlspecialchars($user->getImage() ?? 'app/public/images/default-avatar.png'); ?>" alt="Avatar">
            <h2><?php echo htmlspecialchars($user->getUserName()); ?></h2>
            <p><?php echo htmlspecialchars($user->getEmail()); ?></p>
            <a href="update-profile" class="edit-button">Cập nhật thông tin</a>
            <a href="update-password" class="edit-button">Thay đổi mật khẩu</a>
            <a href="logout" class="edit-button">Đăng xuất</a>
        </div>

        <!-- Right Section: Order Info -->
        <div class="order-info">
            <h3>Thông Tin Đơn Hàng</h3>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Đơn Hàng</th>
                        <th>Ngày Đặt</th>
                        <th>Trạng Thái</th>
                        <th>Tổng Giá</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr style="cursor: pointer;" class="order-tr" data-order-id="<?php echo htmlspecialchars($order['orderId']); ?>">
                                <td>#<?php echo htmlspecialchars($order['orderId']); ?></td>
                                <td><?php echo htmlspecialchars($order['createdAt']); ?></td>
                                
                                    <?php

                            switch ($order['status']) {
                                case 'ordered':
                                    $class = 'pending';
                                    $status = 'Đã đặt hàng';
                                    break;
                                case 'pending':
                                    $class = 'pending';
                                    $status = 'Đang xử lý';
                                    break;
                                case 'processing':
                                    $class = 'pending';
                                    $status = 'Đang vận chuyển';
                                    break;
                                case 'completed':
                                    $class = 'completed';
                                    $status = 'Đã hoàn tất';
                                    break;
                                default:
                                    $class = '';
                                    $status = '';

                            }

                            ?>
                                <td><span class="status <?php echo $class ?>"><?php echo $status ?></span></td>
                                <td><?php echo formatCurrencyVND($order['orderTotal'] + $order['shippingCost']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Chưa có đơn hàng nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.order-tr').on('click', function () {
            const orderId = $(this).data('order-id');
            window.location = `order-detail?orderId=${orderId}`;
        });
    });
</script>
<?php
// Đảm bảo các biến tồn tại và có giá trị mặc định
$carts = $carts ?? [];
$orderTotal = $orderTotal ?? 0;
$user = $user ?? null;

// Debug thông tin
error_log('Debug in view - Order total in view: ' . $orderTotal);
error_log('Debug in view - Session: ' . print_r($_SESSION, true));
?>
<main class="checkout-page">
    <div class="checkout-container">
        <!-- Cột trái: Thông tin giao hàng -->
        <div class="delivery-details">
            <h2>Thông Tin Giao Hàng</h2>
            <form method="POST" action="checkout-delivery" id="checkoutForm">
                <input type="hidden" name="userId" value="<?php echo isset($_SESSION['userId']) ? htmlspecialchars($_SESSION['userId']) : ''; ?>">
                
                <label for="userName">TÊN <span>*</span></label>
                <input type="text" id="userName" name="userName" placeholder="Nhập tên" required>

                <label for="email">EMAIL <span>*</span></label>
                <input type="email" id="email" name="email" placeholder="Nhập email" required>
                <span id="emailError" class="error-message"></span>

                <label for="phone">SỐ ĐIỆN THOẠI <span>*</span></label>
                <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại"
                    value="" required>

                <label for="city">TỈNH/THÀNH PHỐ <span>*</span></label>
                <input type="text" id="city" name="city" placeholder="Nhập tỉnh/thành phố của bạn"
                    value="" required>

                <label for="district">QUẬN/HUYỆN <span>*</span></label>
                <input type="text" id="district" name="district" placeholder="Nhập quận/huyện của bạn"
                    value="" required>

                <label for="ward">PHƯỜNG/XÃ<span>*</span></label>
                <input type="text" id="ward" name="ward" placeholder="Nhập phường/xã của bạn"
                    value="" required>

                <label for="street">SỐ NHÀ, ĐƯỜNG <span>*</span></label>
                <input type="text" id="street" name="street" placeholder="Nhập số nhà, tên đường của bạn"
                    value="" required>

                <p class="mandatory-note">*Lưu liên hệ cho lần thanh toán sau</p>
                <button type="button" id="save-contact-button">Lưu liên hệ</button>

                <label for="note">GHI CHÚ CHO ĐƠN HÀNG (KHÔNG BẮT BUỘC)</label>
                <textarea id="note" name="note" placeholder="Nhập ghi chú..."></textarea>

                <p class="mandatory-note">(*) Các trường này là bắt buộc</p>

                <div class="buttons">
                    <button type="button" class="cancel-button" id="btn-cancel">Hủy</button>
                    <button type="submit" class="continue-button">Tiếp tục</button>
                </div>
            </form>
        </div>

        <!-- Cột phải: Tóm tắt đơn hàng -->
        <div class="order-summary">
            <h2>Tóm Tắt Đơn Hàng</h2>
            <?php if (!empty($carts)): ?>
                <?php foreach ($carts as $cart): ?>
                    <?php if (isset($cart['images'][0]['link']) && isset($cart['productName'])): ?>
                    <div class="product-item">
                        <img src="<?php echo htmlspecialchars($cart['images'][0]['link']); ?>" alt="Sản phẩm" class="product-image">
                        <div class="product-details">
                            <p><strong><?php echo htmlspecialchars($cart['productName']); ?></strong></p>
                            <p>Phân loại: <?php echo htmlspecialchars($cart['categoryName'] ?? 'Không có'); ?></p>
                            <p>Giá: <?php echo isset($cart['price']) ? formatCurrencyVND($cart['price']) : '0 ₫'; ?></p>
                            <p>Số lượng: <?php echo (int)($cart['quantity'] ?? 0); ?></p>
                            <p class="product-price"><?php echo isset($cart['totalPrice']) ? formatCurrencyVND($cart['totalPrice']) : '0 ₫'; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="discount-code">
                    <input type="text" placeholder="Nhập mã giảm giá">
                    <button type="button">Áp dụng</button>
                </div>

                <div class="pricing">
                    <p>Giá tạm tính: <span><?php echo formatCurrencyVND($orderTotal); ?></span></p>
                    <p>Phí vận chuyển: <span>Hiển thị ở bước tiếp theo</span></p>
                    <p class="total">TỔNG TIỀN: <strong><?php echo formatCurrencyVND($orderTotal); ?></strong></p>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <p>Giỏ hàng trống</p>
                    <a href="<?php echo BASE_PATH . '/shop'; ?>" class="btn-shopping">Tiếp tục mua sắm</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    .error-message {
        color: #ff0000;
        font-size: 14px;
        margin-top: 5px;
        display: block;
    }
</style>

<script>
    $(document).ready(function () {
        $('#btn-cancel').on('click', function (e) {
            e.preventDefault();
            window.location = 'carts';
        });

        $('#save-contact-button').on('click', function (e) {
            e.preventDefault();
            const userData = {
                userName: $('#userName').val().trim(),
                phone: $('#phone').val().trim(),
                city: $('#city').val().trim(),
                district: $('#district').val().trim(),
                ward: $('#ward').val().trim(),
                street: $('#street').val().trim()
            };

            // Kiểm tra dữ liệu trước khi gửi
            if (!userData.userName || !userData.phone || !userData.city || 
                !userData.district || !userData.ward || !userData.street) {
                showToast('Vui lòng điền đầy đủ thông tin', 'error');
                return;
            }

            $.ajax({
                url: `api/users/update-contact`,
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(userData),
                success: function (response) {
                    if (response.success && !response.unchange) {
                        showToast('Lưu thông tin thành công');
                        // Refresh trang sau khi lưu thành công
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else if (response.unchange) {
                        showToast('Thông tin không thay đổi');
                    } else {
                        console.error('Error:', response);
                        showToast('Lưu thông tin thất bại', 'error');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    showToast('Lỗi khi lưu thông tin: ' + error, 'error');
                }
            });
        });

        // Email validation function
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        // Real-time email validation
        $('#email').on('input', function() {
            const email = $(this).val();
            const errorElement = $('#emailError');
            
            if (email && !validateEmail(email)) {
                errorElement.text('Email không hợp lệ');
                $(this).addClass('invalid');
            } else {
                errorElement.text('');
                $(this).removeClass('invalid');
            }
        });

        // Form submission validation
        $('#checkoutForm').on('submit', function(e) {
            const email = $('#email').val();
            if (!validateEmail(email)) {
                e.preventDefault();
                $('#emailError').text('Vui lòng nhập email hợp lệ');
                $('#email').focus();
                return false;
            }
            return true;
        });
    });
</script>
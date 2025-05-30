<main class="shop-page">
    <!-- Sidebar -->
    <aside class="sidebar">
        <h2>Bộ lọc sản phẩm</h2>

        <!-- Thanh tìm kiếm nhỏ gọn -->
        <label for="search">Tìm kiếm:</label>
        <input type="text" id="search" placeholder="Tìm kiếm sản phẩm...">

        <label for="category">Danh mục:</label>
        <select id="category">
            <option value="">Tất cả</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['categoryId'] ?>"><?php echo $category['categoryName'] ?></option>
            <?php endforeach; ?>
        </select>

        <label for="price">Lọc theo giá:</label>
        <input type="range" id="price" min="200000" max="3000000" value="900000" step="200000">
        <span id="price-range"></span>
    </aside>

    <!-- Product List -->
    <section id="product1" class="section-p1 shop-product">
        <h2>Cửa hàng</h2>
        <div id="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Đang tải sản phẩm...</p>
        </div>
        <div id="error-message" style="display: none;" class="error-container">
            <p>Có lỗi xảy ra khi tải sản phẩm. Vui lòng thử lại.</p>
            <button onclick="loadProducts()">Thử lại</button>
        </div>
        <div class="pro-container" id="product-list"></div>
    </section>
</main>

<style>
.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-container {
    text-align: center;
    padding: 20px;
    margin: 20px 0;
    background-color: #fff3f3;
    border: 1px solid #ffcdd2;
    border-radius: 4px;
}

.error-container button {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 10px;
}

.error-container button:hover {
    background-color: #2980b9;
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        let enablePriceRange = false;
        let isLoading = false;

        // Function to show/hide loading state
        function setLoading(loading) {
            isLoading = loading;
            if (loading) {
                $('#loading').show();
                $('#product-list').hide();
                $('#error-message').hide();
            } else {
                $('#loading').hide();
                $('#product-list').show();
            }
        }

        // Function to show error message
        function showError(message) {
            $('#error-message').show();
            $('#product-list').hide();
            $('#loading').hide();
            console.error('Shop error:', message);
        }

        // Function to safely get image URL
        function getProductImage(product) {
            if (product.images && product.images.length > 0 && product.images[0].link) {
                return product.images[0].link;
            }
            return 'path/to/default/image.jpg'; // Replace with your default image path
        }

        // Function to safely get product name
        function getProductName(product) {
            return product.productName ? product.productName : 'Sản phẩm không có tên';
        }

        // Function to load products
        function loadProducts() {
            if (isLoading) return;

            const price = parseInt($('#price').val(), 10);
            const filters = {
                search: $('#search').val(),
                categoryId: $('#category').val(),
            };

            if (enablePriceRange) {
                filters.price_start = price - 200000;
                filters.price_end = price + 200000;
            }

            // Build query parameters
            const queryParams = $.param(filters);

            setLoading(true);

            // Make AJAX request
            $.ajax({
                url: `api/shop?${queryParams}`,
                method: 'GET',
                dataType: 'json',
                timeout: 10000, // 10 second timeout
                success: function (response) {
                    if (response.success && Array.isArray(response.data)) {
                        // Clear existing products
                        $('#product-list').empty();
                        
                        if (response.data.length === 0) {
                            $('#product-list').html('<p class="no-products">Không tìm thấy sản phẩm nào</p>');
                            return;
                        }

                        // Render products
                        response.data.forEach(product => {
                            try {
                                const productHtml = `
                                    <div class="pro">
                                        <a href="detail?productId=${product.productId}">
                                            <img src="${getProductImage(product)}"
                                                alt="${getProductName(product)}">
                                        </a>
                                        <div class="des">
                                            <a href="detail?productId=${product.productId}">
                                                <h4>${getProductName(product)}</h4>
                                                <h5>${formatCurrency(product.price || 0)}</h5>
                                            </a>
                                            <div class="star">
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <button class="cart add-to-cart" data-product-id="${product.productId}">
                                                <i class="fa-solid fa-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                `;
                                $('#product-list').append(productHtml);
                            } catch (err) {
                                console.error('Error rendering product:', err);
                            }
                        });
                    } else {
                        showError(response.message || 'Không thể tải sản phẩm');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('API Error:', error);
                    showError(`Lỗi khi tải sản phẩm: ${error}`);
                },
                complete: function () {
                    setLoading(false);
                }
            });
        }

        // Get params from URL
        const params = new URLSearchParams(window.location.search);
        const categoryId = params.get('categoryId');
        if (categoryId) {
            $('#category').val(categoryId);
            const url = new URL(window.location.href);
            url.searchParams.delete('categoryId');
            window.history.replaceState(null, '', url);
        }

        // Format currency function
        function formatCurrency(value) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
        }

        // Update price range
        function updatePriceRange(value) {
            value = parseInt(value, 10);
            const startPrice = value - 200000;
            const endPrice = value + 200000;

            $('#price-range').text(`${formatCurrency(startPrice)} - ${formatCurrency(endPrice)}`);
            enablePriceRange = true;
            loadProducts();
        }

        // Initial load
        loadProducts();

        // Event handlers
        $('#price').on('input', function() {
            updatePriceRange(parseInt($(this).val(), 10));
        });

        $('#category').on('change', loadProducts);

        let searchTimeout;
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadProducts, 300);
        });

        // Handle add to cart
        $(document).on('click', '.add-to-cart', function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            
            $.ajax({
                url: `api/carts/add?productId=${productId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        showToast('Đã thêm vào giỏ hàng');
                    } else {
                        showToast('Thêm vào giỏ hàng thất bại', 'error');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        showToast('Vui lòng đăng nhập để thực hiện', 'error');
                    } else {
                        showToast('Lỗi khi thêm vào giỏ hàng', 'error');
                    }
                }
            });
        });
    });
</script>
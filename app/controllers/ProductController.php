<?php
require_once 'app/models/ProductModel.php';
require_once 'app/models/CategoryModel.php';
require_once 'app/models/ProductImageModel.php';
require_once 'app/utils/constants.php';
require_once 'app/utils/flashMessage.php';

class ProductController
{
    private $productModel;
    private $categoryModel;
    private $productImageModel;

    /**
     * Constructor with dependency injection.
     * @param ProductModel $productModel
     * @param CategoryModel $categoryModel
     * @param ProductImageModel $productImageModel
     */
    public function __construct(ProductModel $productModel, CategoryModel $categoryModel, ProductImageModel $productImageModel)
    {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
        $this->productImageModel = $productImageModel;
    }

    public function productAdmin()
    {
        $products = $this->productModel->getAllProducts();
        $page = 'products';
        $view = 'app/views/admin/products/products.php';
        require_once 'app/views/admin/adminLayout.php';
    }

    public function addProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $categories = $this->categoryModel->getAllCategories();
            $view = 'app/views/admin/products/addProduct.php';
            $action = 'add';
            require_once 'app/views/admin/adminLayout.php';
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productName = $_POST['productName'];
            $productDesc = $_POST['productDesc'];
            $price = $_POST['price'];
            $categoryId = $_POST['categoryId'];
            $stock = $_POST['stock'];

            $uploadedImages = [];
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = 'uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                foreach ($_FILES['images']['name'] as $key => $imageName) {
                    $tmpName = $_FILES['images']['tmp_name'][$key];
                    $uniqueName = uniqid() . '-' . basename($imageName);
                    $destination = $uploadDir . $uniqueName;
                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadedImages[] = $destination;
                    }
                }
            }

            $result = $this->productModel->addProduct($productName, $productDesc, $price, $categoryId, $stock, $uploadedImages);

            if ($result) {
                setSuccessMessage('Thêm sản phẩm thành công');
                header('Location: products');
            } else {
                setErrorMessage('Thêm sản phẩm thất bại');
                header('Location: add-product');
            }
        } else {
            header('Location: notfound');
        }
    }

    public function updateProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['productId']) && ctype_digit($_GET['productId'])) {
            $categories = $this->categoryModel->getAllCategories();
            $product = $this->productModel->getProductById($_GET['productId']);
            $view = 'app/views/admin/products/addProduct.php';
            $action = 'update';
            require_once 'app/views/admin/adminLayout.php';
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['productId']) && ctype_digit($_GET['productId'])) {
            $productId = $_GET['productId'];
            $productName = $_POST['productName'];
            $productDesc = $_POST['productDesc'];
            $price = $_POST['price'];
            $categoryId = $_POST['categoryId'];
            $stock = $_POST['stock'];

            // Handle deleted images
            $deletedImages = isset($_POST['deletedImages']) ? explode(',', $_POST['deletedImages']) : [];
            foreach ($deletedImages as $deletedImageId) {
                $image = $this->productImageModel->getProductImageById($deletedImageId);
                $imagePath = $image['link'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $this->productImageModel->deleteProductImageById($deletedImageId);
            }

            // Handle new images updated
            $uploadedImages = [];
            if (!empty($_FILES['images']['name'][0])) {
                $uploadDir = 'uploads/products/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                foreach ($_FILES['images']['name'] as $key => $imageName) {
                    $tmpName = $_FILES['images']['tmp_name'][$key];
                    $uniqueName = uniqid() . '-' . basename($imageName);
                    $destination = $uploadDir . $uniqueName;
                    if (move_uploaded_file($tmpName, $destination)) {
                        $uploadedImages[] = $destination;
                    }
                }
            }

            $result = $this->productModel->updateProduct($productId, $productName, $productDesc, $price, $categoryId, $stock, $uploadedImages);

            if ($result) {
                setSuccessMessage('Cập nhật sản phẩm thành công');
                header('Location: products');
            } else {
                setErrorMessage('Cập nhật không thành công');
                header('Location: update-user');
            }
        } else {
            header('Location: notfound');
        }
    }

    public function deleteProduct()
    {
        if (isset($_GET["productId"])) {
            $productId = $_GET["productId"];
            $result = $this->productModel->deleteProduct($productId);

            if ($result > 0) {
                setSuccessMessage('Xóa sản phẩm thành công');
            } else {
                setErrorMessage('Xóa sản phẩm thất bại');
            }
            header("Location: products");
        } else {
            header('Location: notfound');
        }
    }

    public function shop()
    {
        $categories = $this->categoryModel->getAllCategories();
        $page = 'shop';
        $view = 'app/views/user/products/shop.php';
        require_once 'app/views/layout.php';
    }
    public function shopApi()
    {
        header('Content-Type: application/json');
        try {
            // Validate and sanitize input parameters
            $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
            $limit = filter_var($_GET['limit'] ?? 9, FILTER_VALIDATE_INT, ['options' => ['default' => 9, 'min_range' => 1, 'max_range' => 50]]);
            
            $order = strtoupper($_GET['order'] ?? 'ASC');
            if (!in_array($order, ['ASC', 'DESC'])) {
                $order = 'ASC';
            }
            
            $order_by = strtolower($_GET['order_by'] ?? 'createdAt');
            $allowed_order_fields = ['createdAt', 'price', 'productName', 'views'];
            if (!in_array($order_by, $allowed_order_fields)) {
                $order_by = 'createdAt';
            }
            
            $search = trim($_GET['search'] ?? '');
            $categoryId = filter_var($_GET['categoryId'] ?? null, FILTER_VALIDATE_INT);
            $price_start = filter_var($_GET['price_start'] ?? null, FILTER_VALIDATE_INT);
            $price_end = filter_var($_GET['price_end'] ?? null, FILTER_VALIDATE_INT);

            // Log request parameters for debugging
            error_log("Shop API Request - Parameters: " . json_encode([
                'page' => $page,
                'limit' => $limit,
                'order' => $order,
                'order_by' => $order_by,
                'search' => $search,
                'categoryId' => $categoryId,
                'price_start' => $price_start,
                'price_end' => $price_end
            ]));

            $products = $this->productModel->getAllProducts([
                'page' => $page,
                'limit' => $limit,
                'order' => $order,
                'order_by' => $order_by,
                'search' => $search,
                'categoryId' => $categoryId,
                'price_start' => $price_start,
                'price_end' => $price_end,
            ]);

            if ($products === null) {
                throw new Exception('Error fetching products from database');
            }

            // Format product data for response
            $formattedProducts = array_map(function($product) {
                return [
                    'productId' => (int)$product['productId'],
                    'productName' => htmlspecialchars($product['productName']),
                    'productDesc' => htmlspecialchars($product['productDesc']),
                    'price' => (float)$product['price'],
                    'categoryId' => (int)$product['categoryId'],
                    'categoryName' => htmlspecialchars($product['categoryName']),
                    'stock' => (int)$product['stock'],
                    'views' => (int)$product['views'],
                    'images' => array_map(function($image) {
                        return [
                            'imageId' => (int)$image['imageId'],
                            'link' => htmlspecialchars($image['link'])
                        ];
                    }, $product['images'] ?? [])
                ];
            }, $products);

            echo json_encode([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => count($products)
                ]
            ]);

        } catch (Exception $e) {
            error_log("Shop API Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tải sản phẩm',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function detail()
    {
        if (isset($_GET['productId']) && ctype_digit($_GET['productId'])) {
            $productId = $_GET['productId'];
            $referrer = $_SERVER['HTTP_REFERER'] ?? null;

            if ($referrer && strpos($referrer, 'detail') !== false) {
                $product = $this->productModel->getProductById($productId);
            } else {
                $product = $this->productModel->getProductDetailById($productId);
            }

            $relativeProducts = $this->productModel->getRelativeProducts($productId, $product['categoryId']);

            $view = 'app/views/user/products/detail.php';
            require_once 'app/views/layout.php';
        } else {
            header('location: notfound');
        }
    }
}
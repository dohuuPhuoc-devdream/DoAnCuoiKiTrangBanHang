<?php
require_once 'app/models/CategoryModel.php';

class CategoryController
{
    private $categoryModel;

    /**
     * Constructor with dependency injection.
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel)
    {
        $this->categoryModel = $categoryModel;
    }

    public function categoryAdmin()
    {
        $categories = $this->categoryModel->getAllCategories();
        $page = 'categories';
        $view = 'app/views/admin/categories/categories.php';
        require_once 'app/views/admin/adminLayout.php';
    }

    public function addCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $view = 'app/views/admin/categories/addCategory.php';
            $action = 'add';
            require_once 'app/views/admin/adminLayout.php';
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoryName = $_POST['categoryName'];
            $result = $this->categoryModel->addCategory($categoryName);

            if ($result) {
                setSuccessMessage('Thêm danh mục thành công');
                header('Location: categories');
            } else {
                setErrorMessage('Thêm danh mục thất bại');
                header('Location: add-category');
            }
        }
    }

    public function updateCategory()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['categoryId']) && ctype_digit($_GET['categoryId'])) {
            $category = $this->categoryModel->getCategoryById($_GET['categoryId']);
            $view = 'app/views/admin/categories/addCategory.php';
            $action = 'update';
            require_once 'app/views/admin/adminLayout.php';
        }
        // POST
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['categoryId'])) {
            $categoryId = $_GET['categoryId'];
            $categoryName = $_POST['categoryName'];
            $result = $this->categoryModel->updateCategory($categoryId, $categoryName);

            if ($result) {
                setSuccessMessage('Cập nhật danh mục thành công');
                header('Location: categories');
            } else {
                setErrorMessage('Cập nhật không thành công');
                header('Location: update-category');
            }
        }
    }

    public function deleteCategory()
    {
        if (isset($_GET["categoryId"])) {
            $categoryId = $_GET["categoryId"];
            $result = $this->categoryModel->deleteCategory($categoryId);

            if ($result > 0) {
                setSuccessMessage('Xóa danh mục thành công');
            } else {
                setErrorMessage('Xóa danh mục thất bại');
            }
            header("Location: categories");
        } else {
            header('Location: notfound');
        }
    }
}
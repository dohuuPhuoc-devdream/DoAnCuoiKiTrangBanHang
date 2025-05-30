<?php
require_once 'app/models/ProductModel.php';
require_once 'app/models/CategoryModel.php';
require_once 'app/utils/validate.php';

class GeneralController
{
    private $productModel;
    private $categoryModel;

    /**
     * Constructor with dependency injection.
     * @param ProductModel $productModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(ProductModel $productModel, CategoryModel $categoryModel)
    {
        $this->productModel = $productModel;
        $this->categoryModel = $categoryModel;
    }

    public function dashboard()
    {
        header('Location: admin/users');
    }

    public function home()
    {
        $latestProducts = $this->productModel->getLatestProducts();
        $popularProducts = $this->productModel->getPopularProducts();
        $bestSellerProducts = $this->productModel->getBestSellerProducts();
        $categories = $this->categoryModel->getAllCategories();

        $page = 'home';
        $view = 'app/views/user/home.php';
        require_once 'app/views/layout.php';
    }

    public function about()
    {
        $page = 'about';
        $view = 'app/views/user/general/about.php';
        require_once 'app/views/layout.php';
    }

    public function contact()
    {
        $page = 'contact';
        $view = 'app/views/user/general/contact.php';
        require_once 'app/views/layout.php';
    }

    public function blogs()
    {
        $page = 'blogs';
        $view = 'app/views/user/general/blog.php';
        require_once 'app/views/layout.php';
    }

    public function blogDetail()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && validateParamIds($_GET['blogId'])) {
            $blogId = $_GET['blogId'];
            $view = "app/views/user/blogs/blog$blogId.php";
            require_once "app/views/layout.php";
        } else {
            header('Location: notfound');
        }
    }

    public function policy()
    {
        $view = 'app/views/user/general/policy.php';
        require_once 'app/views/layout.php';
    }
}
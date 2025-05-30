<?php

function initSession() {
    // Không cần làm gì vì session đã được khởi tạo trong index.php
    return;
}

function isLoggedIn() {
    initSession();
    return isset($_SESSION['auth']) && $_SESSION['auth'] === true && isset($_SESSION['userId']);
}

function getUserId() {
    initSession();
    return isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        setErrorMessage('Vui lòng đăng nhập để tiếp tục');
        header('Location: ' . BASE_PATH . '/login');
        exit();
    }
} 
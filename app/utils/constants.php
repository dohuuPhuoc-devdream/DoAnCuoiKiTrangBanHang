<?php
const PROTECTED_ROUTES = [
    //admin routes
    '/admin',
    '/admin/products',
    '/admin/add-product',
    '/admin/update-product',
    '/admin/delete-product',
    '/admin/categories',
    '/admin/add-category',
    '/admin/update-category',
    '/admin/delete-category',
    '/admin/orders',
    '/admin/detail-order',
    '/admin/update-order-status',
    '/admin/users',
    '/admin/add-user',
    '/admin/update-user',
    '/admin/delete-user',
    //user routes
    '/me',
    '/update-profile',
    '/update-password',
    '/update-image',
    '/orders',
    '/order-detail',
    '/checkout',
    '/checkout-delivery',
    '/checkout-payment',
    '/make-order',
    '/carts',
    '/delete-cart',
    '/increase-cart',
    '/decrease-cart',

];

const API_PROTECTED_ROUTES = [
    '/api/carts/add',
    '/api/users/update-contact',

];
?>
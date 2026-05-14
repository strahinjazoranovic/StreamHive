<?php

require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/../models/category.php';

class categoryController extends Controller
{
    public function index()
    {
        $categoryModel = new category();
        $categories = $categoryModel->getAllCategories();

        $this->json($categories);
    }
}
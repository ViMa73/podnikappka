<?php
namespace Core;

class Controller
{
    protected function view($view, $data = [], $layout = true)
{
    extract($data);

    if ($layout) {
        require __DIR__ . '/../Views/layout.php';
    } else {
        require __DIR__ . "/../Views/$view.php";
    }
}

    protected function redirect($path)
    {
        header("Location: " . $path);
        exit;
    }
}
<?php
namespace Controllers;
use Core\Controller;
class OpenSourceController extends Controller
{
    public function license(): void { $view='opensource/license'; $title='Licence'; require __DIR__ . '/../Views/layout_guest.php'; }
    public function disclaimer(): void { $view='opensource/disclaimer'; $title='Disclaimer'; require __DIR__ . '/../Views/layout_guest.php'; }
}

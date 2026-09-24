<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;
use Core\CSRF;

class AuthController extends Controller
{
    public function loginForm()
    {
      //$this->view('login', ['title' => 'Přihlášení'], false);
      $view = 'login';
      $title = 'Přihlášení';
      require __DIR__ . '/../Views/layout_guest.php';
    }

    public function login()
    {
        if (Auth::login($_POST['email'], $_POST['password'])) {
            return $this->redirect('/dashboard');
        }

        //$_SESSION['flash_error'] = 'Neplatné přihlašovací údaje.';
        return $this->redirect('/');
    }

    public function logout()
    {
        // pokud session neběží, zkus ji otevřít
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // smaž session data
        $_SESSION = [];

        // smaž session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // znič session jen pokud je aktivní
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }

        $_SESSION['flash_success'] = "Odhlášeno.";
        return $this->redirect('/');
    }
}

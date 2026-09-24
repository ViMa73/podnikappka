<?php

namespace Controllers;

use Core\Auth;
use Core\CSRF;
use Core\WhatsNew;

class WhatsNewController
{
    private function redirect(string $to): void
    {
        header("Location: {$to}");
        exit;
    }

    private function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/');
        }
    }

    public function markSeen(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            exit;
        }

        CSRF::validate();

        WhatsNew::markCurrentVersionAsSeen();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
        ]);
        exit;
    }
}

<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Core\DB;

class AdminController extends Controller
{
    private function requireAdmin(): void
    {
        if (!Auth::check()) {
            header('Location: /');
            exit;
        }

        if (!Auth::isSuperAdmin()) {
            http_response_code(403);
            echo 'Přístup odepřen.';
            exit;
        }
    }

    private function buildTrendData(int $current, int $past): array
    {
        $diff = $current - $past;

        if ($diff > 0) {
            $direction = 'up';
            $label = '+' . $diff;
        } elseif ($diff < 0) {
            $direction = 'down';
            $label = (string)$diff;
        } else {
            $direction = 'same';
            $label = '0';
        }

        return [
            'past' => $past,
            'diff' => $diff,
            'direction' => $direction,
            'label' => $label,
        ];
    }

    public function index(): void
    {
        $this->requireAdmin();

        $db = DB::get();

        $companiesCount = (int)$db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        $usersCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $pendingBillingRequestsCount = (int)$db->query("
            SELECT COUNT(*)
            FROM billing_requests
            WHERE status = 'pending'
        ")->fetchColumn();

        $companies30DaysAgo = (int)$db->query("
            SELECT COUNT(*)
            FROM companies
            WHERE created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetchColumn();

        $companies1YearAgo = (int)$db->query("
            SELECT COUNT(*)
            FROM companies
            WHERE created_at <= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ")->fetchColumn();

        $users30DaysAgo = (int)$db->query("
            SELECT COUNT(*)
            FROM users
            WHERE created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetchColumn();

        $users1YearAgo = (int)$db->query("
            SELECT COUNT(*)
            FROM users
            WHERE created_at <= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ")->fetchColumn();

        $companiesTrend30 = $this->buildTrendData($companiesCount, $companies30DaysAgo);
        $companiesTrendYear = $this->buildTrendData($companiesCount, $companies1YearAgo);

        $usersTrend30 = $this->buildTrendData($usersCount, $users30DaysAgo);
        $usersTrendYear = $this->buildTrendData($usersCount, $users1YearAgo);

        $view = 'admin/index';
        $title = 'Administrace';

        require __DIR__ . '/../Views/layout.php';
    }
}

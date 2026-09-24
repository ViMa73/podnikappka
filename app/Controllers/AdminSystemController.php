<?php

namespace Controllers;

use Core\AppRelease;
use Core\Auth;
use Core\CSRF;
use Core\MigrationRunner;
use Core\Edition;
use Core\GitHubUpdater;

class AdminSystemController
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

    private function requireSystemAccess(): void
    {
        $this->requireAuth();

        $allowed = Edition::isSelfHosted() ? Auth::isOwner() : Auth::isSuperAdmin();
        if (!$allowed) {
            http_response_code(403);
            echo 'Přístup odepřen.';
            exit;
        }
    }

    public function index(): void
    {
        $this->requireSystemAccess();

        $runner = new MigrationRunner();

        $currentVersion = AppRelease::current();
        $releases = AppRelease::all();
        $pendingMigrations = $runner->getPendingMigrations();
        $migrationHistory = $runner->getHistory(50);

        $updater = new GitHubUpdater();
        try {
            $updateInfo = $updater->check();
            $updateError = null;
        } catch (\Throwable $e) {
            $updateInfo = ['repository' => $updater->getRepository()];
            $updateError = $e->getMessage();
        }

        $view = 'admin/system/index';
        $title = 'Systém / Aktualizace';

        require __DIR__ . '/../Views/layout.php';
    }

    public function installUpdate(): void
    {
        $this->requireSystemAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('/admin/system');
        CSRF::validate();
        @set_time_limit(180);
        try {
            $result = (new GitHubUpdater())->installLatest();
            $_SESSION['flash_success'] = 'PodnikAppka byla aktualizována na verzi ' . $result['version'] . '. Před změnou byla vytvořena záloha souborů. Pokud jsou čekající databázové migrace, spusťte je níže.';
        } catch (\Throwable $e) { $_SESSION['flash_error'] = 'Aktualizace se nepodařila: ' . $e->getMessage(); }
        $this->redirect('/admin/system');
    }

    public function runMigrations(): void
    {
        $this->requireSystemAccess();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/system');
        }

        CSRF::validate();

        $runner = new MigrationRunner();
        $userId = Auth::user();

        $results = $runner->runPending($userId);

        if (empty($results)) {
            $_SESSION['flash_success'] = 'Žádné čekající migrace.';
            $this->redirect('/admin/system');
        }

        $failed = array_filter($results, fn(array $row) => $row['status'] === 'failed');

        if (!empty($failed)) {
            $first = reset($failed);
            $_SESSION['flash_error'] = 'Migrace selhala: ' . ($first['name'] ?? '') . ' — ' . ($first['message'] ?? 'Neznámá chyba');
            $this->redirect('/admin/system');
        }

        $_SESSION['flash_success'] = 'Migrace byly úspěšně spuštěny.';
        $this->redirect('/admin/system');
    }
}

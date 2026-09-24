<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Core\CSRF;
use Core\DB;

class AdminBillingController extends Controller
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

    public function requests(): void
    {
        $this->requireAdmin();

        $db = DB::get();

        $stmt = $db->query("
            SELECT
                br.*,
                c.name AS company_name,
                c.ico AS company_ico,
                u.first_name,
                u.last_name,
                u.email
            FROM billing_requests br
            JOIN companies c ON c.id = br.company_id
            JOIN users u ON u.id = br.user_id
            WHERE br.status = 'pending'
            ORDER BY br.created_at ASC, br.id ASC
        ");
        $billingRequests = $stmt->fetchAll() ?: [];

        $view = 'admin/billing/requests';
        $title = 'Čekající platby';
        require __DIR__ . '/../Views/layout.php';
    }

    public function approveRequest(): void
    {
        $this->requireAdmin();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            $this->redirect('/admin/billing/requests');
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            $_SESSION['flash_error'] = 'Neplatná žádost.';
            $this->redirect('/admin/billing/requests');
        }

        $db = DB::get();

        $stmt = $db->prepare("
            SELECT *
            FROM billing_requests
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            $_SESSION['flash_error'] = 'Žádost nebyla nalezena.';
            $this->redirect('/admin/billing/requests');
        }

        if (($request['status'] ?? '') !== 'pending') {
            $_SESSION['flash_error'] = 'Tato žádost už není čekající.';
            $this->redirect('/admin/billing/requests');
        }

        $companyId = (int)$request['company_id'];
        $periodYears = max(1, (int)($request['period_years'] ?? 1));
        $targetPlan = trim((string)($request['target_plan'] ?? 'paid'));
        $type = trim((string)($request['type'] ?? 'upgrade'));

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                SELECT *
                FROM subscriptions
                WHERE company_id = ?
                  AND status = 'active'
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([$companyId]);
            $currentSubscription = $stmt->fetch() ?: null;

            $now = new \DateTimeImmutable('now');
            $newEnd = null;

            if ($type === 'renewal' && $currentSubscription && !empty($currentSubscription['ended_at'])) {
                $currentEnd = new \DateTimeImmutable($currentSubscription['ended_at']);

                if ($currentEnd > $now) {
                    $newEnd = $currentEnd->modify('+' . $periodYears . ' year');
                } else {
                    $newEnd = $now->modify('+' . $periodYears . ' year');
                }

                $stmt = $db->prepare("
                    UPDATE subscriptions
                    SET ended_at = ?, plan = ?, status = 'active'
                    WHERE id = ?
                ");
                $stmt->execute([
                    $newEnd->format('Y-m-d H:i:s'),
                    $targetPlan,
                    (int)$currentSubscription['id']
                ]);
            } else {
                if ($currentSubscription) {
                    $stmt = $db->prepare("
                        UPDATE subscriptions
                        SET status = 'ended',
                            ended_at = COALESCE(ended_at, NOW())
                        WHERE id = ?
                    ");
                    $stmt->execute([(int)$currentSubscription['id']]);
                }

                $newEnd = $now->modify('+' . $periodYears . ' year');

                $stmt = $db->prepare("
                    INSERT INTO subscriptions (
                        company_id,
                        plan,
                        status,
                        started_at,
                        ended_at
                    ) VALUES (?, ?, 'active', ?, ?)
                ");
                $stmt->execute([
                    $companyId,
                    $targetPlan,
                    $now->format('Y-m-d H:i:s'),
                    $newEnd->format('Y-m-d H:i:s'),
                ]);
            }

            $stmt = $db->prepare("
                UPDATE billing_requests
                SET status = 'approved',
                    paid_at = NOW(),
                    approved_at = NOW(),
                    approved_by = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)Auth::user(),
                $requestId
            ]);

            $db->commit();

            $_SESSION['flash_success'] = 'Platba byla schválena a předplatné bylo aktivováno.';
        } catch (\Throwable $e) {
            $db->rollBack();
            $_SESSION['flash_error'] = 'Nepodařilo se schválit platbu: ' . $e->getMessage();
        }

        $this->redirect('/admin/billing/requests');
    }

    public function settings(): void
    {
        $this->requireAdmin();

        $db = DB::get();

        $stmt = $db->query("
            SELECT *
            FROM billing_settings
            WHERE is_active = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $billingSettings = $stmt->fetch() ?: null;

        $view = 'admin/billing/settings';
        $title = 'Platební údaje';
        require __DIR__ . '/../Views/layout.php';
    }

    public function saveSettings(): void
    {
        $this->requireAdmin();

        if (!CSRF::check($_POST['_csrf'] ?? '')) {
            $_SESSION['flash_error'] = 'Neplatný formulář (CSRF).';
            $this->redirect('/admin/billing/settings');
        }

        $receiverName = trim((string)($_POST['receiver_name'] ?? ''));
        $bankAccount = trim((string)($_POST['bank_account'] ?? ''));
        $bankCode = trim((string)($_POST['bank_code'] ?? ''));
        $yearlyPrice = (float)($_POST['yearly_price'] ?? 0);
        $currency = trim((string)($_POST['currency'] ?? 'CZK'));
        $paymentNote = trim((string)($_POST['payment_note'] ?? ''));

        if ($receiverName === '' || $bankAccount === '' || $bankCode === '' || $yearlyPrice <= 0 || $currency === '') {
            $_SESSION['flash_error'] = 'Vyplň všechna povinná pole správně.';
            $this->redirect('/admin/billing/settings');
        }

        $db = DB::get();

        $stmt = $db->query("
            SELECT id
            FROM billing_settings
            WHERE is_active = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $existingId = (int)($stmt->fetchColumn() ?: 0);

        if ($existingId > 0) {
            $stmt = $db->prepare("
                UPDATE billing_settings
                SET receiver_name = ?,
                    bank_account = ?,
                    bank_code = ?,
                    yearly_price = ?,
                    currency = ?,
                    payment_note = ?,
                    is_active = 1
                WHERE id = ?
            ");
            $stmt->execute([
                $receiverName,
                $bankAccount,
                $bankCode,
                $yearlyPrice,
                $currency,
                $paymentNote !== '' ? $paymentNote : null,
                $existingId
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO billing_settings (
                    receiver_name,
                    bank_account,
                    bank_code,
                    yearly_price,
                    currency,
                    payment_note,
                    is_active
                ) VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $receiverName,
                $bankAccount,
                $bankCode,
                $yearlyPrice,
                $currency,
                $paymentNote !== '' ? $paymentNote : null,
            ]);
        }

        $_SESSION['flash_success'] = 'Platební údaje byly uloženy.';
        $this->redirect('/admin/billing/settings');
    }
}

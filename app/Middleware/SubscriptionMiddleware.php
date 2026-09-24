<?php
namespace Middleware;

use Core\DB;
use Core\Auth;

class SubscriptionMiddleware
{
    public function handle()
    {
        $stmt = DB::get()->prepare("
            SELECT * FROM subscriptions
            WHERE company_id = ? AND status = 'active'
            AND (ended_at IS NULL OR ended_at > NOW())
        ");
        $stmt->execute([Auth::companyId()]);
        $sub = $stmt->fetch();

        if (!$sub) {
            // free režim povolen – jen omezené funkce
            return;
        }
    }
}
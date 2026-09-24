<?php
namespace Core;

class Subscription
{
    public static function active(?int $companyId = null): ?array
    {
        $companyId = $companyId ?: (int)Auth::companyId();
        if ($companyId <= 0) {
            return null;
        }

        $stmt = DB::get()->prepare("
            SELECT *
            FROM subscriptions
            WHERE company_id = ?
              AND status = 'active'
              AND (ended_at IS NULL OR ended_at > NOW())
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$companyId]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function activePlan(?int $companyId = null): string
    {
        $subscription = self::active($companyId);

        if (!$subscription) {
            return 'free';
        }

        $plan = trim((string)($subscription['plan'] ?? 'free'));
        return $plan !== '' ? $plan : 'free';
    }

    public static function allowsFeature(string $featureKey, ?int $companyId = null): bool
    {
        $plan = self::activePlan($companyId);

        $planFeatures = [
            'free' => [
                'services',
            ],
            'paid' => [
                'services',
                'attendance',
                'tasks',
                'temperatures',
                'vacations',
                'economic_indicators',
            ],
        ];

        return in_array($featureKey, $planFeatures[$plan] ?? [], true);
    }
}

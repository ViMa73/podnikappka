<?php
namespace Core;

class Feature
{
    public static function enabled($featureName): bool
    {
        $companyId = (int)Auth::companyId();
        $featureName = trim((string)$featureName);

        if ($companyId <= 0 || $featureName === '') {
            return false;
        }

        // Firemní zapnutí/vypnutí modulu
        $stmt = DB::get()->prepare("
            SELECT cf.enabled
            FROM company_features cf
            JOIN features f ON f.id = cf.feature_id
            WHERE cf.company_id = ? AND f.name = ?
            LIMIT 1
        ");
        $stmt->execute([$companyId, $featureName]);
        $row = $stmt->fetch();

        return $row && (int)$row['enabled'] === 1;
    }

    public static function setForCompany(int $companyId, string $featureName, bool $enabled, ?string $value = null): void
    {
        $featureName = trim($featureName);

        if ($companyId <= 0 || $featureName === '') {
            throw new \InvalidArgumentException('Neplatné companyId nebo featureName.');
        }

        // najdi feature_id
        $stmt = DB::get()->prepare("
            SELECT id
            FROM features
            WHERE name = ?
            LIMIT 1
        ");
        $stmt->execute([$featureName]);
        $f = $stmt->fetch();

        if (!$f) {
            throw new \Exception("Feature '$featureName' neexistuje v tabulce features.");
        }

        $featureId = (int)$f['id'];

        // upsert do company_features
        $stmt = DB::get()->prepare("
            INSERT INTO company_features (company_id, feature_id, enabled, value)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                enabled = VALUES(enabled),
                value = VALUES(value)
        ");
        $stmt->execute([
            $companyId,
            $featureId,
            $enabled ? 1 : 0,
            $value
        ]);
    }
}

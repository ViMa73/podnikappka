<?php
namespace Core;

use RuntimeException;
use ZipArchive;

class GitHubUpdater
{
    private string $root;

    public function __construct()
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function getRepository(): string
    {
        return 'ViMa73/podnikappka';
    }

    public function check(): array
    {
        $repo = $this->getRepository();
        $url = 'https://api.github.com/repos/' . $repo . '/releases/latest';
        $release = $this->requestJson($url);
        $tag = ltrim((string)($release['tag_name'] ?? ''), "vV \t\n\r\0\x0B");
        if ($tag === '') throw new RuntimeException('GitHub Release neobsahuje platný tag verze.');
        $current = AppRelease::current()['version'] ?? '0.0.0';
        $asset = $this->selectZipAsset($release['assets'] ?? []);
        return [
            'repository' => $repo,
            'current' => $current,
            'latest' => $tag,
            'available' => version_compare($tag, $current, '>'),
            'name' => (string)($release['name'] ?? $release['tag_name'] ?? $tag),
            'published_at' => (string)($release['published_at'] ?? ''),
            'notes' => (string)($release['body'] ?? ''),
            'html_url' => (string)($release['html_url'] ?? ''),
            'download_url' => $asset['browser_download_url'] ?? ($release['zipball_url'] ?? ''),
            'digest' => $asset['digest'] ?? null,
            'asset_name' => $asset['name'] ?? null,
        ];
    }

    public function installLatest(): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Server nemá PHP rozšíření ZipArchive, které je pro vzdálenou aktualizaci potřeba.');
        $info = $this->check();
        if (empty($info['available'])) throw new RuntimeException('Je již nainstalována nejnovější verze.');
        if (empty($info['download_url'])) throw new RuntimeException('Release neobsahuje ZIP balíček ke stažení.');
        if (!is_writable($this->root)) throw new RuntimeException('Kořenový adresář aplikace není zapisovatelný pro PHP.');

        $work = $this->root . '/storage/update-tmp-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
        if (!@mkdir($work, 0755, true) && !is_dir($work)) throw new RuntimeException('Nelze vytvořit dočasný adresář aktualizace.');
        $zipPath = $work . '/release.zip';
        $extract = $work . '/extract';
        @mkdir($extract, 0755, true);

        try {
            $this->download((string)$info['download_url'], $zipPath);
            if (!empty($info['digest']) && str_starts_with((string)$info['digest'], 'sha256:')) {
                $expected = substr((string)$info['digest'], 7);
                if (!hash_equals(strtolower($expected), strtolower(hash_file('sha256', $zipPath)))) {
                    throw new RuntimeException('Kontrolní součet staženého balíčku nesouhlasí. Aktualizace byla zastavena.');
                }
            }
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) throw new RuntimeException('Stažený soubor není platný ZIP archiv.');
            if (!$zip->extractTo($extract)) { $zip->close(); throw new RuntimeException('ZIP archiv se nepodařilo rozbalit.'); }
            $zip->close();

            $source = $this->detectSourceRoot($extract);
            if (!is_file($source . '/config/version.php') || !is_dir($source . '/app')) {
                throw new RuntimeException('Balíček nevypadá jako platná distribuce PodnikAppky.');
            }
            $packageVersion = require $source . '/config/version.php';
            $pv = is_array($packageVersion) ? ($packageVersion['version'] ?? '') : '';
            if ($pv === '' || version_compare((string)$pv, (string)$info['latest'], '!=')) {
                throw new RuntimeException('Verze uvnitř balíčku neodpovídá GitHub Release.');
            }

            $backup = $this->createFileBackup();
            $this->copyTree($source, $this->root, ['config/local.php', 'storage', 'uploads']);
            return ['version' => $pv, 'backup' => $backup];
        } finally {
            $this->deleteTree($work);
        }
    }

    private function selectZipAsset(array $assets): ?array
    {
        foreach ($assets as $asset) {
            $name = strtolower((string)($asset['name'] ?? ''));
            if (str_ends_with($name, '.zip') && str_contains($name, 'podnikappka')) return $asset;
        }
        return null;
    }

    private function requestJson(string $url): array
    {
        $body = $this->httpGet($url, ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2026-03-10']);
        $data = json_decode($body, true);
        if (!is_array($data)) throw new RuntimeException('GitHub vrátil neplatnou odpověď.');
        return $data;
    }

    private function httpGet(string $url, array $headers = []): string
    {
        $headers[] = 'User-Agent: PodnikAppka-Updater';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_HTTPHEADER => $headers]);
            $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
            if ($body === false || $code < 200 || $code >= 300) throw new RuntimeException('Spojení s GitHubem selhalo' . ($err ? ': ' . $err : ' (HTTP ' . $code . ')') . '.');
            return (string)$body;
        }
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 30, 'follow_location' => 1, 'header' => implode("\r\n", $headers) . "\r\n"]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) throw new RuntimeException('Server se nedokáže připojit ke GitHubu.');
        return $body;
    }

    private function download(string $url, string $dest): void
    {
        $data = $this->httpGet($url, ['Accept: application/octet-stream']);
        if (file_put_contents($dest, $data) === false || filesize($dest) < 1000) throw new RuntimeException('Stažení aktualizačního balíčku selhalo.');
    }

    private function detectSourceRoot(string $extract): string
    {
        if (is_file($extract . '/config/version.php')) return $extract;
        $dirs = array_values(array_filter(glob($extract . '/*') ?: [], 'is_dir'));
        foreach ($dirs as $dir) if (is_file($dir . '/config/version.php')) return $dir;
        throw new RuntimeException('V archivu nebyl nalezen kořen PodnikAppky.');
    }

    private function createFileBackup(): string
    {
        $dir = $this->root . '/storage/update-backups';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new RuntimeException('Nelze vytvořit adresář pro zálohu aktualizace.');
        $path = $dir . '/files-' . date('Ymd-His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Nelze vytvořit zálohu souborů.');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $full = $file->getPathname(); $rel = substr($full, strlen($this->root) + 1);
            if ($file->isDir() || str_starts_with($rel, 'storage/') || str_starts_with($rel, 'uploads/')) continue;
            $zip->addFile($full, $rel);
        }
        $zip->close();
        return basename($path);
    }

    private function copyTree(string $src, string $dst, array $preserve): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $item) {
            $rel = str_replace('\\', '/', substr($item->getPathname(), strlen($src) + 1));
            foreach ($preserve as $p) if ($rel === $p || str_starts_with($rel, rtrim($p, '/') . '/')) continue 2;
            $target = $dst . '/' . $rel;
            if ($item->isDir()) { if (!is_dir($target) && !@mkdir($target, 0755, true)) throw new RuntimeException('Nelze vytvořit adresář: ' . $rel); }
            else { if (!is_dir(dirname($target))) @mkdir(dirname($target), 0755, true); if (!@copy($item->getPathname(), $target)) throw new RuntimeException('Nelze aktualizovat soubor: ' . $rel); }
        }
    }

    private function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        @rmdir($dir);
    }
}

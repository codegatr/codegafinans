<?php
/**
 * CODEGA Finans - Smart Update v5
 *
 * Mizan / CMiner desenine sadık:
 *   1. GitHub'dan en son release bilgisini çek
 *   2. Yeni sürüm varsa ZIP'i indir
 *   3. Mevcut dosyaların ZIP yedeğini al
 *   4. Yeni ZIP'i geçici klasöre aç
 *   5. tracked_paths'i karşı tarafa kopyala (storage/, backups/, .env, config.local.php DOKUNULMAZ)
 *   6. Migration'ları çalıştır
 *   7. version.php / manifest.json güncellenmiş olur (zip içinden gelir)
 *   8. cf_update_log'a kayıt
 *
 * Eğer adımın bir kısmı patlarsa rollback için backup ZIP'inden geri yükleme
 * adım adım yapılabilir (admin paneli üzerinden).
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/migrate.php';

function upd_status(): array
{
    $current = CF_VERSION;
    $latest  = upd_fetch_latest_release();

    $hasUpdate = false;
    if ($latest && !empty($latest['tag'])) {
        $hasUpdate = version_compare(ltrim($latest['tag'], 'vV'), $current, '>');
    }

    return [
        'enabled'      => CF_UPDATE_ENABLED,
        'current'      => $current,
        'repo'         => CF_REPO,
        'branch'       => CF_BRANCH,
        'latest'       => $latest,
        'has_update'   => $hasUpdate,
        'last_log'     => upd_last_log(20),
        'db_migration' => cf_current_db_version(),
    ];
}

function upd_fetch_latest_release(): ?array
{
    $url = 'https://api.github.com/repos/' . CF_REPO . '/releases/latest';
    $headers = [
        'User-Agent: CODEGAFinans-Updater/' . CF_VERSION,
        'Accept: application/vnd.github+json',
    ];
    if (CF_UPDATE_GH_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . CF_UPDATE_GH_TOKEN;
    }

    $opts = [
        'http' => [
            'timeout' => 10,
            'header'  => implode("\r\n", $headers),
            'ignore_errors' => true,
        ],
    ];
    $ctx = stream_context_create($opts);
    $body = @file_get_contents($url, false, $ctx);
    if (!$body) { return null; }

    $data = json_decode($body, true);
    if (!is_array($data) || empty($data['tag_name'])) {
        return null;
    }

    // Tercih: codegafinans-<tag>.zip varsa ona git, yoksa GitHub'ın zipball_url'unu kullan
    $assetUrl = $data['zipball_url'] ?? '';
    $assetName = 'source';
    foreach (($data['assets'] ?? []) as $asset) {
        if (str_ends_with(strtolower($asset['name'] ?? ''), '.zip')) {
            $assetUrl = $asset['browser_download_url'] ?? $assetUrl;
            $assetName = $asset['name'];
            break;
        }
    }

    return [
        'tag'        => $data['tag_name'],
        'name'       => $data['name'] ?? $data['tag_name'],
        'body'       => $data['body'] ?? '',
        'published'  => $data['published_at'] ?? null,
        'zip_url'    => $assetUrl,
        'zip_name'   => $assetName,
        'html_url'   => $data['html_url'] ?? null,
    ];
}

function upd_apply(?int $adminId = null): array
{
    if (!CF_UPDATE_ENABLED) {
        return ['ok' => false, 'message' => 'Güncelleme sistemi kapalı.'];
    }

    if (!class_exists('ZipArchive')) {
        return ['ok' => false, 'message' => 'ZipArchive PHP eklentisi gerekli.'];
    }

    $status = upd_status();
    if (!$status['has_update']) {
        return ['ok' => false, 'message' => 'Sistem güncel görünüyor.'];
    }

    $latest = $status['latest'];
    $tag = $latest['tag'];
    $logId = db_insert(
        'INSERT INTO ' . t('update_log') . ' (from_ver, to_ver, status, admin_id, message, created_at)
         VALUES (:f, :t, "started", :a, "Güncelleme başlatıldı.", NOW())',
        [':f' => CF_VERSION, ':t' => $tag, ':a' => $adminId]
    );

    try {
        // 1) ZIP indir
        if (!is_dir(CF_UPDATES)) { @mkdir(CF_UPDATES, 0775, true); }
        $zipPath = CF_UPDATES . '/' . CF_APP_SLUG . '-' . $tag . '.zip';

        if (!is_file($zipPath)) {
            $bytes = upd_download($latest['zip_url'], $zipPath);
            if ($bytes <= 0) {
                throw new RuntimeException('ZIP indirilemedi: ' . $latest['zip_url']);
            }
        }

        // 2) Yedek al
        if (!is_dir(CF_BACKUPS)) { @mkdir(CF_BACKUPS, 0775, true); }
        $backupPath = CF_BACKUPS . '/' . CF_APP_SLUG . '-pre-' . $tag . '-' . date('Ymd_His') . '.zip';
        upd_make_backup($backupPath);

        // 3) Aç
        $extractDir = CF_UPDATES . '/_extract_' . preg_replace('/[^A-Za-z0-9_.-]/', '', $tag);
        if (is_dir($extractDir)) { upd_rrmdir($extractDir); }
        @mkdir($extractDir, 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('ZIP açılamadı: ' . $zipPath);
        }
        $zip->extractTo($extractDir);
        $zip->close();

        // GitHub zipball içinde tek üst klasör vardır
        $sub = upd_first_subdir($extractDir);
        $sourceDir = $sub ?: $extractDir;

        // 4) Dosyaları kopyala (tracked_paths)
        $manifestNew = $sourceDir . '/manifest.json';
        if (!is_file($manifestNew)) {
            throw new RuntimeException('manifest.json paket içinde bulunamadı.');
        }
        $newMan = json_decode((string)file_get_contents($manifestNew), true);
        $tracked  = $newMan['tracked_paths']  ?? ['public_html/', 'inc/', 'migrations/', 'manifest.json'];
        $excluded = $newMan['excluded_paths'] ?? ['storage/', 'backups/', 'updates/', '.env', 'inc/config.local.php'];

        $copied = 0;
        foreach ($tracked as $rel) {
            $src = rtrim($sourceDir . '/' . $rel, '/');
            $dst = rtrim(CF_ROOT . '/' . $rel, '/');
            if (!file_exists($src)) { continue; }

            if (is_dir($src)) {
                $copied += upd_copy_dir($src, $dst, $excluded);
            } else {
                if (!upd_is_excluded($rel, $excluded)) {
                    @copy($src, $dst);
                    $copied++;
                }
            }
        }

        // 5) Migration
        $migLog = cf_migrate_all();

        // 6) Geçici extract'i sil
        upd_rrmdir($extractDir);

        // 7) Eski yedekleri kırp
        upd_prune_backups();

        $msg = sprintf(
            "Güncelleme tamamlandı: %s → %s | kopyalanan dosya: %d | migrations: %d",
            CF_VERSION, $tag, $copied, count($migLog)
        );
        db_exec(
            'UPDATE ' . t('update_log') . ' SET status="success", message=:m WHERE id=:id',
            [':m' => $msg . "\n" . implode("\n", $migLog), ':id' => $logId]
        );
        audit('update.success', null, $adminId, "tag={$tag}");

        return ['ok' => true, 'message' => $msg, 'log' => $migLog, 'backup' => basename($backupPath)];

    } catch (Throwable $e) {
        db_exec(
            'UPDATE ' . t('update_log') . ' SET status="failed", message=:m WHERE id=:id',
            [':m' => 'HATA: ' . $e->getMessage(), ':id' => $logId]
        );
        audit('update.fail', null, $adminId, $e->getMessage());
        return ['ok' => false, 'message' => 'Güncelleme başarısız: ' . $e->getMessage()];
    }
}

function upd_download(string $url, string $dest): int
{
    $fp = @fopen($dest, 'w');
    if (!$fp) { return 0; }

    $headers = [
        'User-Agent: CODEGAFinans-Updater/' . CF_VERSION,
        'Accept: application/octet-stream',
    ];
    if (CF_UPDATE_GH_TOKEN !== '') {
        $headers[] = 'Authorization: Bearer ' . CF_UPDATE_GH_TOKEN;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if (!$ok) {
            @unlink($dest);
            throw new RuntimeException('cURL: ' . $err);
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'header'  => implode("\r\n", $headers),
                'timeout' => 300,
                'follow_location' => 1,
            ],
        ]);
        $bytes = @stream_copy_to_stream(@fopen($url, 'r', false, $ctx) ?: throw new RuntimeException('stream open'), $fp);
        fclose($fp);
        if ($bytes <= 0) {
            @unlink($dest);
            throw new RuntimeException('stream_copy_to_stream başarısız.');
        }
    }

    $size = (int) (filesize($dest) ?: 0);
    if ($size > CF_UPDATE_ZIP_LIMIT) {
        @unlink($dest);
        throw new RuntimeException('ZIP dosyası boyut limitini aşıyor (' . $size . ' bayt).');
    }
    return $size;
}

function upd_make_backup(string $zipPath): void
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Backup ZIP oluşturulamadı.');
    }

    $exclude = ['storage/', 'backups/', 'updates/', '.env', 'inc/config.local.php', '.git/'];
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(CF_ROOT, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        $abs = $file->getPathname();
        $rel = ltrim(str_replace(CF_ROOT, '', $abs), '/\\');
        $rel = str_replace('\\', '/', $rel);

        if (upd_is_excluded($rel, $exclude)) { continue; }

        if ($file->isDir()) {
            $zip->addEmptyDir($rel);
        } else {
            $zip->addFile($abs, $rel);
        }
    }
    $zip->close();
}

function upd_is_excluded(string $rel, array $excluded): bool
{
    foreach ($excluded as $e) {
        $e = str_replace('\\', '/', $e);
        if (str_ends_with($e, '/')) {
            if (str_starts_with($rel . '/', $e)) { return true; }
        } else {
            if ($rel === $e) { return true; }
        }
    }
    return false;
}

function upd_copy_dir(string $src, string $dst, array $excluded): int
{
    if (!is_dir($dst)) { @mkdir($dst, 0775, true); }
    $copied = 0;

    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        $abs = $file->getPathname();
        $rel = ltrim(str_replace($src, '', $abs), '/\\');
        $rel = str_replace('\\', '/', $rel);

        // Hedef göreceli yol (src kökünden)
        $srcRoot = str_replace('\\','/', str_replace(CF_ROOT.'/', '', $src));
        $checkRel = $srcRoot . '/' . $rel;

        if (upd_is_excluded($checkRel, $excluded)) { continue; }

        $target = $dst . '/' . $rel;

        if ($file->isDir()) {
            if (!is_dir($target)) { @mkdir($target, 0775, true); }
        } else {
            if (!is_dir(dirname($target))) { @mkdir(dirname($target), 0775, true); }
            if (@copy($abs, $target)) { $copied++; }
        }
    }
    return $copied;
}

function upd_first_subdir(string $dir): ?string
{
    $items = @scandir($dir) ?: [];
    foreach ($items as $it) {
        if ($it === '.' || $it === '..') { continue; }
        if (is_dir($dir . '/' . $it)) {
            return $dir . '/' . $it;
        }
    }
    return null;
}

function upd_rrmdir(string $dir): void
{
    if (!is_dir($dir)) { return; }
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($rii as $file) {
        /** @var SplFileInfo $file */
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }
    @rmdir($dir);
}

function upd_prune_backups(): void
{
    $files = glob(CF_BACKUPS . '/*.zip') ?: [];
    if (count($files) <= CF_UPDATE_KEEP_BACKUPS) { return; }
    usort($files, fn($a,$b) => filemtime($a) <=> filemtime($b));
    $drop = array_slice($files, 0, count($files) - CF_UPDATE_KEEP_BACKUPS);
    foreach ($drop as $f) { @unlink($f); }
}

function upd_last_log(int $limit = 20): array
{
    return db_all(
        'SELECT * FROM ' . t('update_log') . ' ORDER BY id DESC LIMIT ' . (int)$limit
    );
}

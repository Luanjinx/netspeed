<?php

namespace App\Services\System;

use App\Models\IspSetting;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use ZipArchive;

class SystemUpdateService
{
    protected string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Get current local git & version information.
     *
     * @return array{
     *     has_git: bool,
     *     commit_hash: string,
     *     commit_full_hash: string,
     *     commit_message: string,
     *     commit_date: string,
     *     commit_author: string,
     *     branch: string,
     *     remote_url: string,
     *     repo_owner: string,
     *     repo_name: string,
     *     has_uncommitted_changes: bool
     * }
     */
    public function getCurrentVersionInfo(): array
    {
        $hasGitDir = is_dir($this->basePath.DIRECTORY_SEPARATOR.'.git');

        if (! $hasGitDir || ! $this->canExecuteGit()) {
            return $this->getStoredVersionInfo();
        }

        $shortHashRes = $this->runGitCommand(['rev-parse', '--short', 'HEAD']);
        if (! $shortHashRes['success'] || empty($shortHashRes['output'])) {
            return $this->getStoredVersionInfo();
        }

        $shortHash = $shortHashRes['output'];
        $fullHash = $this->runGitCommand(['rev-parse', 'HEAD'])['output'] ?: '';
        $message = $this->runGitCommand(['log', '-1', '--pretty=format:%s'])['output'] ?: 'No commit message';
        $date = $this->runGitCommand(['log', '-1', '--pretty=format:%ci'])['output'] ?: '';
        $author = $this->runGitCommand(['log', '-1', '--pretty=format:%an'])['output'] ?: 'Admin';
        $branch = $this->runGitCommand(['rev-parse', '--abbrev-ref', 'HEAD'])['output'] ?: 'main';
        $remoteUrl = $this->runGitCommand(['config', '--get', 'remote.origin.url'])['output'] ?: 'https://github.com/Luanjinx/netspeed.git';

        $statusOut = $this->runGitCommand(['status', '--porcelain'])['output'] ?? '';
        $hasUncommitted = ! empty(trim($statusOut));

        $repoDetails = $this->parseRepoDetails($remoteUrl);

        $gitDir = $this->basePath.DIRECTORY_SEPARATOR.'.git';
        $isGitWritable = is_dir($gitDir) && is_writable($gitDir);

        return [
            'has_git' => true,
            'is_git_writable' => $isGitWritable,
            'commit_hash' => $shortHash,
            'commit_full_hash' => $fullHash,
            'commit_message' => $message,
            'commit_date' => $date,
            'commit_author' => $author,
            'branch' => $branch,
            'remote_url' => $remoteUrl,
            'repo_owner' => $repoDetails['owner'],
            'repo_name' => $repoDetails['repo'],
            'has_uncommitted_changes' => $hasUncommitted,
        ];
    }

    /**
     * Fallback version info stored in settings (used on shared hosting / non-git STB installs).
     *
     * @return array{
     *     has_git: bool,
     *     commit_hash: string,
     *     commit_full_hash: string,
     *     commit_message: string,
     *     commit_date: string,
     *     commit_author: string,
     *     branch: string,
     *     remote_url: string,
     *     repo_owner: string,
     *     repo_name: string,
     *     has_uncommitted_changes: bool
     * }
     */
    protected function getStoredVersionInfo(): array
    {
        $remoteUrl = IspSetting::get('system_update_remote_url', 'https://github.com/Luanjinx/netspeed.git') ?: 'https://github.com/Luanjinx/netspeed.git';
        $repoDetails = $this->parseRepoDetails($remoteUrl);

        return [
            'has_git' => false,
            'commit_hash' => IspSetting::get('system_current_commit', '351b5d1') ?: '351b5d1',
            'commit_full_hash' => IspSetting::get('system_current_commit_full', '') ?: '',
            'commit_message' => IspSetting::get('system_current_commit_message', 'Versi rilis stabil') ?: 'Versi rilis stabil',
            'commit_date' => IspSetting::get('system_current_commit_date', now()->toDateTimeString()) ?: now()->toDateTimeString(),
            'commit_author' => IspSetting::get('system_current_commit_author', 'System') ?: 'System',
            'branch' => IspSetting::get('system_update_branch', 'main') ?: 'main',
            'remote_url' => $remoteUrl,
            'repo_owner' => $repoDetails['owner'],
            'repo_name' => $repoDetails['repo'],
            'has_uncommitted_changes' => false,
        ];
    }

    /**
     * Check if new updates/commits are available on GitHub.
     *
     * @return array{
     *     success: bool,
     *     is_up_to_date: bool,
     *     commits_behind: int,
     *     latest_remote_hash: string,
     *     latest_remote_message: string,
     *     incoming_commits: array<int, array{hash: string, message: string, author: string, date: string, url: string}>,
     *     checked_at: string,
     *     error: ?string
     * }
     */
    public function checkForUpdates(bool $forceFetch = true): array
    {
        $versionInfo = $this->getCurrentVersionInfo();
        $branch = $versionInfo['branch'] ?: 'main';
        $owner = $versionInfo['repo_owner'];
        $repo = $versionInfo['repo_name'];

        // 1. Try Git Fetch method first if git directory exists
        if ($versionInfo['has_git']) {
            $fetchSucceeded = true;
            if ($forceFetch) {
                // Clear unwriteable FETCH_HEAD if possible
                $this->clearUnwriteableFetchHead();
                $fetchRes = $this->runGitCommand(['fetch', 'origin', $branch], 30);
                if (! $fetchRes['success']) {
                    $fetchSucceeded = false;
                    Log::warning('Git fetch origin failed, attempting fallback to GitHub API: '.$fetchRes['error']);
                }
            }

            // Only trust local rev-list if fetch succeeded or wasn't forced
            if ($fetchSucceeded) {
                $countRes = $this->runGitCommand(['rev-list', "HEAD..origin/{$branch}", '--count']);

                if ($countRes['success']) {
                    $behindCount = (int) trim($countRes['output']);
                    $incomingCommits = [];

                    if ($behindCount > 0) {
                        $logRes = $this->runGitCommand(['log', "HEAD..origin/{$branch}", '--pretty=format:%h|%s|%an|%ci', '-n', '25']);
                        if ($logRes['success'] && ! empty($logRes['output'])) {
                            $lines = explode("\n", trim($logRes['output']));
                            foreach ($lines as $line) {
                                $parts = explode('|', $line, 4);
                                if (count($parts) >= 2) {
                                    $hash = trim($parts[0]);
                                    $incomingCommits[] = [
                                        'hash' => $hash,
                                        'message' => trim($parts[1] ?? ''),
                                        'author' => trim($parts[2] ?? 'Developer'),
                                        'date' => trim($parts[3] ?? ''),
                                        'url' => "https://github.com/{$owner}/{$repo}/commit/{$hash}",
                                    ];
                                }
                            }
                        }
                    }

                    $remoteHashRes = $this->runGitCommand(['rev-parse', '--short', "origin/{$branch}"]);
                    $remoteHash = $remoteHashRes['success'] ? trim($remoteHashRes['output']) : $versionInfo['commit_hash'];
                    $remoteMsg = ! empty($incomingCommits) ? $incomingCommits[0]['message'] : $versionInfo['commit_message'];

                    $result = [
                        'success' => true,
                        'is_up_to_date' => $behindCount === 0,
                        'commits_behind' => $behindCount,
                        'latest_remote_hash' => $remoteHash,
                        'latest_remote_message' => $remoteMsg,
                        'incoming_commits' => $incomingCommits,
                        'checked_at' => now()->format('d M Y, H:i:s'),
                        'error' => null,
                    ];

                    $this->saveCheckCache($result);

                    return $result;
                }
            }
        }

        // 2. Fallback to GitHub REST API
        return $this->checkViaGitHubApi($owner, $repo, $branch, $versionInfo['commit_hash']);
    }

    /**
     * Fallback update checker via GitHub Public/Authenticated REST API.
     */
    protected function checkViaGitHubApi(string $owner, string $repo, string $branch, string $localHash): array
    {
        try {
            $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/commits/{$branch}";
            $req = Http::timeout(15)->withHeaders([
                'User-Agent' => 'NetSpeed-ISP-Billing',
                'Accept' => 'application/vnd.github.v3+json',
            ]);

            $githubToken = IspSetting::get('github_access_token');
            if (! empty($githubToken)) {
                $req->withToken($githubToken);
            }

            $response = $req->get($apiUrl);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'is_up_to_date' => true,
                    'commits_behind' => 0,
                    'latest_remote_hash' => $localHash,
                    'latest_remote_message' => 'Gagal memeriksa API GitHub (HTTP '.$response->status().')',
                    'incoming_commits' => [],
                    'checked_at' => now()->format('d M Y, H:i:s'),
                    'error' => 'GitHub API error: '.($response->json('message') ?? 'Status '.$response->status()),
                ];
            }

            $data = $response->json();
            $remoteFullSha = $data['sha'] ?? '';
            $remoteShortSha = substr($remoteFullSha, 0, 7);
            $remoteMsg = $data['commit']['message'] ?? 'New release';
            $remoteAuthor = $data['commit']['author']['name'] ?? 'GitHub';
            $remoteDate = $data['commit']['author']['date'] ?? '';

            $isUpToDate = ($remoteShortSha === $localHash) || str_starts_with($remoteFullSha, $localHash);
            $commitsBehind = $isUpToDate ? 0 : 1;
            $incomingCommits = [];

            if (! $isUpToDate) {
                $incomingCommits[] = [
                    'hash' => $remoteShortSha,
                    'message' => $remoteMsg,
                    'author' => $remoteAuthor,
                    'date' => $remoteDate ? Carbon::parse($remoteDate)->toDateTimeString() : '',
                    'url' => $data['html_url'] ?? "https://github.com/{$owner}/{$repo}/commit/{$remoteShortSha}",
                ];
            }

            $result = [
                'success' => true,
                'is_up_to_date' => $isUpToDate,
                'commits_behind' => $commitsBehind,
                'latest_remote_hash' => $remoteShortSha,
                'latest_remote_message' => $remoteMsg,
                'incoming_commits' => $incomingCommits,
                'checked_at' => now()->format('d M Y, H:i:s'),
                'error' => null,
            ];

            $this->saveCheckCache($result);

            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'is_up_to_date' => true,
                'commits_behind' => 0,
                'latest_remote_hash' => $localHash,
                'latest_remote_message' => 'Terjadi kesalahan saat memeriksa',
                'incoming_commits' => [],
                'checked_at' => now()->format('d M Y, H:i:s'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform the actual system update from GitHub.
     *
     * @param array{
     *     run_migrations?: bool,
     *     clear_cache?: bool,
     *     upgrade_filament?: bool,
     *     force_reset?: bool,
     *     maintenance_mode?: bool
     * } $options
     * @return array{
     *     success: bool,
     *     message: string,
     *     previous_hash: string,
     *     new_hash: string,
     *     logs: array<int, array{step: string, command: string, output: string, success: bool}>
     * }
     */
    public function performUpdate(array $options = []): array
    {
        $runMigrations = $options['run_migrations'] ?? true;
        $clearCache = $options['clear_cache'] ?? true;
        $upgradeFilament = $options['upgrade_filament'] ?? true;
        $forceReset = $options['force_reset'] ?? false;
        $maintenanceMode = $options['maintenance_mode'] ?? true;

        $versionBefore = $this->getCurrentVersionInfo();
        $branch = $versionBefore['branch'] ?: 'main';
        $logs = [];
        $overallSuccess = true;

        try {
            // STEP 1: Put Application into Maintenance Mode
            if ($maintenanceMode) {
                $logs[] = $this->executeArtisanStep(
                    step: '1. Mengaktifkan Mode Pemeliharaan (Maintenance Mode)',
                    command: 'down',
                    parameters: ['--secret' => 'netspeed-update-bypass']
                );
            }

            // STEP 2: Git Pull / Reset, with automatic GitHub ZIP archive fallback for shared hosting / STB
            $gitSucceeded = false;

            if ($versionBefore['has_git'] && $this->canExecuteGit()) {
                $this->clearUnwriteableFetchHead();
                if ($forceReset) {
                    $stepGit = [
                        'step' => '2. Mengambil Pembaruan dari GitHub (Git Fetch & Reset Hard)',
                        'command' => "git fetch origin {$branch} && git reset --hard origin/{$branch}",
                        'output' => '',
                        'success' => true,
                    ];

                    $fetchRes = $this->runGitCommand(['fetch', 'origin', $branch], 120);
                    $stepGit['output'] .= "[fetch]:\n".($fetchRes['output'] ?: $fetchRes['error'])."\n";

                    $resetRes = $this->runGitCommand(['reset', '--hard', "origin/{$branch}"], 60);
                    $stepGit['output'] .= "[reset --hard]:\n".($resetRes['output'] ?: $resetRes['error']);
                    $stepGit['success'] = $fetchRes['success'] && $resetRes['success'];

                    $logs[] = $stepGit;
                    $gitSucceeded = $stepGit['success'];
                } else {
                    $pullRes = $this->runGitCommand(['pull', 'origin', $branch], 120);
                    $logs[] = [
                        'step' => "2. Menarik Kode Terbaru dari GitHub (git pull origin {$branch})",
                        'command' => "git pull origin {$branch}",
                        'output' => $pullRes['output'] ?: ($pullRes['error'] ?: 'Pull completed without output.'),
                        'success' => $pullRes['success'],
                    ];
                    $gitSucceeded = $pullRes['success'];
                }
            }

            if (! $gitSucceeded) {
                $zipRes = $this->updateViaGitHubArchive(
                    $versionBefore['repo_owner'],
                    $versionBefore['repo_name'],
                    $branch
                );
                $logs[] = $zipRes;
                if (! $zipRes['success']) {
                    $overallSuccess = false;
                }
            }

            // STEP 3: Database Migrations
            if ($overallSuccess && $runMigrations) {
                $migrateLog = $this->executeArtisanStep(
                    step: '3. Menjalankan Migrasi Database (php artisan migrate --force)',
                    command: 'migrate',
                    parameters: ['--force' => true]
                );
                $logs[] = $migrateLog;
                if (! $migrateLog['success']) {
                    $overallSuccess = false;
                }
            }

            // STEP 4: Filament Assets & Upgrade
            if ($overallSuccess && $upgradeFilament) {
                $filamentLog = $this->executeArtisanStep(
                    step: '4. Memperbarui Komponen & Aset Filament (php artisan filament:upgrade)',
                    command: 'filament:upgrade',
                    parameters: []
                );
                $logs[] = $filamentLog;
                if (! $filamentLog['success']) {
                    $overallSuccess = false;
                }
            }

            // STEP 5: Clear and Rebuild Caches
            if ($clearCache) {
                $clearLog = $this->executeArtisanStep(
                    step: '5a. Membersihkan Cache Sistem (php artisan optimize:clear)',
                    command: 'optimize:clear',
                    parameters: []
                );
                $logs[] = $clearLog;

                $optLog = $this->executeArtisanStep(
                    step: '5b. Mengoptimasi Cache Konfigurasi & Rute (php artisan optimize)',
                    command: 'optimize',
                    parameters: []
                );
                $logs[] = $optLog;
            }
        } catch (Exception $e) {
            $overallSuccess = false;
            $logs[] = [
                'step' => 'Error Eksekusi Pembaruan Sistem',
                'command' => 'Exception Caught',
                'output' => $e->getMessage()."\n".$e->getTraceAsString(),
                'success' => false,
            ];
            Log::error('System update exception: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        } finally {
            // STEP 6: Ensure Application is Brought Back Up
            if ($maintenanceMode) {
                $logs[] = $this->executeArtisanStep(
                    step: '6. Mengaktifkan Kembali Sistem (php artisan up)',
                    command: 'up',
                    parameters: []
                );
            }
        }

        $versionAfter = $this->getCurrentVersionInfo();

        // Save update execution summary
        IspSetting::set('system_last_update_at', now()->toIso8601String());
        IspSetting::set('system_last_update_status', $overallSuccess ? 'success' : 'failed');
        IspSetting::set('system_last_update_from', $versionBefore['commit_hash']);
        IspSetting::set('system_last_update_to', $versionAfter['commit_hash']);
        IspSetting::set('system_last_update_logs', json_encode($logs));

        return [
            'success' => $overallSuccess,
            'message' => $overallSuccess
                ? "Pembaruan sistem berhasil! Versi saat ini: {$versionAfter['commit_hash']}."
                : 'Pembaruan mengalami kendala pada salah satu langkah. Periksa log terminal di bawah.',
            'previous_hash' => $versionBefore['commit_hash'],
            'new_hash' => $versionAfter['commit_hash'],
            'logs' => $logs,
        ];
    }

    /**
     * Get the latest update history stored in settings.
     *
     * @return ?array{
     *     updated_at: string,
     *     status: string,
     *     from_hash: string,
     *     to_hash: string,
     *     logs: array<int, array{step: string, command: string, output: string, success: bool}>
     * }
     */
    public function getLastUpdateHistory(): ?array
    {
        $updatedAt = IspSetting::get('system_last_update_at');
        if (! $updatedAt) {
            return null;
        }

        $logsJson = IspSetting::get('system_last_update_logs', '[]');
        $logs = json_decode($logsJson, true);

        return [
            'updated_at' => Carbon::parse($updatedAt)->format('d M Y, H:i:s'),
            'status' => IspSetting::get('system_last_update_status', 'success') ?? 'success',
            'from_hash' => IspSetting::get('system_last_update_from', '-') ?? '-',
            'to_hash' => IspSetting::get('system_last_update_to', '-') ?? '-',
            'logs' => is_array($logs) ? $logs : [],
        ];
    }

    /**
     * Execute an Artisan command safely and capture its output.
     *
     * @return array{step: string, command: string, output: string, success: bool}
     */
    protected function executeArtisanStep(string $step, string $command, array $parameters = []): array
    {
        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = trim(Artisan::output());

            return [
                'step' => $step,
                'command' => 'php artisan '.$command.(! empty($parameters) ? ' '.json_encode($parameters) : ''),
                'output' => $output ?: 'Executed successfully (exit code '.$exitCode.').',
                'success' => $exitCode === 0,
            ];
        } catch (Exception $e) {
            return [
                'step' => $step,
                'command' => 'php artisan '.$command,
                'output' => 'Command failed: '.$e->getMessage(),
                'success' => false,
            ];
        }
    }

    /**
     * Clear unwriteable FETCH_HEAD file if parent .git directory allows it.
     */
    protected function clearUnwriteableFetchHead(): void
    {
        $fetchHead = $this->basePath.DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR.'FETCH_HEAD';
        if (file_exists($fetchHead) && ! is_writable($fetchHead)) {
            @unlink($fetchHead);
        }
    }

    /**
     * Determine whether PHP can invoke the git CLI binary.
     */
    protected function canExecuteGit(): bool
    {
        if (! function_exists('proc_open')) {
            return false;
        }

        try {
            $process = new Process(['git', '--version'], $this->basePath);
            $process->setTimeout(5);
            $process->run();

            return $process->isSuccessful();
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Fallback updater via GitHub Zipball API + PHP ZipArchive.
     * Enables one-click updates on shared hosting or STB setups without Git/SSH/proc_open.
     *
     * @return array{step: string, command: string, output: string, success: bool}
     */
    protected function updateViaGitHubArchive(string $owner, string $repo, string $branch): array
    {
        $stepLabel = '2b. Mengunduh & Mengekstrak Pembaruan via GitHub ZIP (Mode Non-Git / Shared Hosting)';
        $zipUrl = "https://api.github.com/repos/{$owner}/{$repo}/zipball/{$branch}";

        if (! class_exists(ZipArchive::class)) {
            return [
                'step' => $stepLabel,
                'command' => "GET {$zipUrl}",
                'output' => 'Ekstensi PHP ZipArchive (ext-zip) tidak aktif di server ini.',
                'success' => false,
            ];
        }

        $tmpZip = sys_get_temp_dir().DIRECTORY_SEPARATOR.'netspeed-update-'.uniqid('', true).'.zip';

        try {
            $headers = [
                'User-Agent' => 'NetSpeed-ISP-Billing',
                'Accept' => 'application/vnd.github+json',
            ];

            $githubToken = IspSetting::get('github_access_token');
            $commitReq = Http::timeout(20)->withHeaders($headers);
            $zipReq = Http::timeout(120)->withHeaders($headers);

            if (! empty($githubToken)) {
                $commitReq = $commitReq->withToken($githubToken);
                $zipReq = $zipReq->withToken($githubToken);
            }

            // Fetch metadata of the target commit so non-git installs show accurate local commit info
            $commitMeta = [];
            $commitResp = $commitReq->get("https://api.github.com/repos/{$owner}/{$repo}/commits/{$branch}");
            if ($commitResp->successful()) {
                $commitMeta = $commitResp->json();
            }

            $zipResponse = $zipReq->get($zipUrl);
            if (! $zipResponse->successful()) {
                return [
                    'step' => $stepLabel,
                    'command' => "GET {$zipUrl}",
                    'output' => 'Gagal mengunduh arsip pembaruan GitHub (HTTP '.$zipResponse->status().').',
                    'success' => false,
                ];
            }

            file_put_contents($tmpZip, $zipResponse->body());

            $zip = new ZipArchive;
            $openStatus = $zip->open($tmpZip);
            if ($openStatus !== true) {
                return [
                    'step' => $stepLabel,
                    'command' => "GET {$zipUrl}",
                    'output' => "Gagal membuka file ZIP pembaruan (kode: {$openStatus}).",
                    'success' => false,
                ];
            }

            $extractedFiles = 0;
            $skippedFiles = 0;
            $rootPrefix = null;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = str_replace('\\', '/', (string) $zip->getNameIndex($i));
                if ($rootPrefix === null && str_contains($entryName, '/')) {
                    $rootPrefix = substr($entryName, 0, strpos($entryName, '/') + 1);
                }

                $relativePath = ($rootPrefix !== null && str_starts_with($entryName, $rootPrefix))
                    ? substr($entryName, strlen($rootPrefix))
                    : $entryName;

                $relativePath = ltrim($relativePath, '/');
                if ($relativePath === '' || str_ends_with($relativePath, '/') || str_contains($relativePath, '..')) {
                    continue;
                }

                if ($this->isProtectedArchivePath($relativePath)) {
                    $skippedFiles++;

                    continue;
                }

                $destination = $this->basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
                $content = $zip->getFromIndex($i);
                if ($content === false) {
                    continue;
                }

                File::ensureDirectoryExists(dirname($destination));
                if (file_put_contents($destination, $content) === false) {
                    $zip->close();

                    return [
                        'step' => $stepLabel,
                        'command' => "GET {$zipUrl}",
                        'output' => "Gagal menulis file pembaruan ke: {$relativePath} (periksa izin tulis direktori).",
                        'success' => false,
                    ];
                }

                $extractedFiles++;
            }

            $zip->close();

            if (! empty($commitMeta['sha'])) {
                $sha = (string) $commitMeta['sha'];
                IspSetting::set('system_current_commit', substr($sha, 0, 7));
                IspSetting::set('system_current_commit_full', $sha);
                IspSetting::set('system_current_commit_message', (string) ($commitMeta['commit']['message'] ?? 'Updated via GitHub ZIP'));
                IspSetting::set('system_current_commit_author', (string) ($commitMeta['commit']['author']['name'] ?? 'GitHub'));
                $dateStr = $commitMeta['commit']['author']['date'] ?? null;
                IspSetting::set('system_current_commit_date', $dateStr ? Carbon::parse($dateStr)->toDateTimeString() : now()->toDateTimeString());
            }

            return [
                'step' => $stepLabel,
                'command' => "GET {$zipUrl}",
                'output' => "Berhasil memperbarui {$extractedFiles} file via arsip GitHub ZIP ({$skippedFiles} file konfigurasi/storage dilindungi).",
                'success' => true,
            ];
        } catch (Exception $e) {
            return [
                'step' => $stepLabel,
                'command' => "GET {$zipUrl}",
                'output' => 'Error pembaruan ZIP: '.$e->getMessage(),
                'success' => false,
            ];
        } finally {
            if (file_exists($tmpZip)) {
                @unlink($tmpZip);
            }
        }
    }

    /**
     * Paths that must never be overwritten during ZIP archive updates.
     */
    protected function isProtectedArchivePath(string $relativePath): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        if ($normalized === '.env' || str_starts_with($normalized, '.env.')) {
            return true;
        }

        $protectedPrefixes = [
            '.git/',
            'storage/',
            'bootstrap/cache/',
            'public/storage/',
            'vendor/',
            'node_modules/',
            'isp_customer_app/android/key.properties',
        ];

        foreach ($protectedPrefixes as $prefix) {
            if ($normalized === rtrim($prefix, '/') || str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        return str_ends_with($normalized, '.jks') || str_ends_with($normalized, '.keystore');
    }

    /**
     * Run a git command in the project directory using Symfony Process.
     *
     * @param  array<int, string>  $args
     * @return array{success: bool, output: string, error: string, exit_code: int}
     */
    protected function runGitCommand(array $args, int $timeout = 60): array
    {
        if (! function_exists('proc_open')) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Fungsi proc_open dinonaktifkan oleh server PHP.',
                'exit_code' => 1,
            ];
        }

        try {
            $safeDir = str_replace('\\', '/', $this->basePath);
            $command = array_merge(['git', '-c', "safe.directory={$safeDir}"], $args);
            $env = [];
            if (empty(getenv('HOME'))) {
                $env['HOME'] = sys_get_temp_dir();
            }

            $process = new Process($command, $this->basePath, ! empty($env) ? $env : null);
            $process->setTimeout($timeout);
            $process->run();

            $errorOutput = trim($process->getErrorOutput());
            if (! empty($errorOutput) && (str_contains($errorOutput, 'FETCH_HEAD') || str_contains($errorOutput, 'Permission denied') || str_contains($errorOutput, 'permission denied') || str_contains($errorOutput, 'dubious ownership'))) {
                $errorOutput .= "\n\n💡 [PENYEBAB & SOLUSI IZIN FILE]:\n"
                    ."Folder proyek atau '.git' di server tidak dimiliki/bisa ditulis oleh user web server (misal: www-data).\n"
                    ."Jalankan perintah berikut di terminal SSH server:\n"
                    ."  sudo chown -R www-data:www-data {$this->basePath}\n"
                    ."  sudo -u www-data git config --global --add safe.directory {$this->basePath}\n";
            }

            return [
                'success' => $process->isSuccessful(),
                'output' => trim($process->getOutput()),
                'error' => $errorOutput,
                'exit_code' => (int) $process->getExitCode(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'exit_code' => 1,
            ];
        }
    }

    /**
     * Parse repository owner and name from remote git URL.
     *
     * @return array{owner: string, repo: string}
     */
    protected function parseRepoDetails(string $remoteUrl): array
    {
        // Matches: https://github.com/owner/repo.git or git@github.com:owner/repo.git
        if (preg_match('/github\.com[:\/]([^\/]+)\/([^\/\.]+)/', $remoteUrl, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => $matches[2],
            ];
        }

        return [
            'owner' => 'Luanjinx',
            'repo' => 'netspeed',
        ];
    }

    /**
     * Cache last check results in settings.
     */
    protected function saveCheckCache(array $result): void
    {
        IspSetting::set('system_update_last_checked', now()->toIso8601String());
        IspSetting::set('system_update_is_available', $result['is_up_to_date'] ? '0' : '1');
        IspSetting::set('system_update_behind_count', (string) $result['commits_behind']);
        IspSetting::set('system_update_latest_hash', $result['latest_remote_hash']);
        IspSetting::set('system_update_latest_msg', $result['latest_remote_message']);
    }
}

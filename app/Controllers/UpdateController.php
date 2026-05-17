<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Session;
use App\Models\Updater;

final class UpdateController extends Controller
{
    public function index(): void
    {
        $this->authorizeUpdater();
        app()->render('updates/index', [
            'title' => 'Akilli Guncelleme',
            'status' => Updater::status(),
            'commands' => Updater::directAdminCommands(),
        ]);
    }

    public function apply(): void
    {
        $this->postOnly();
        $this->authorizeUpdater();
        $result = Updater::apply((string) ($_POST['update_token'] ?? ''));
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/updates');
    }

    private function authorizeUpdater(): void
    {
        $user = $this->requireAuth();
        $adminEmail = env_value('UPDATE_ADMIN_EMAIL', '');
        if ($adminEmail !== '' && strcasecmp((string) $user['email'], $adminEmail) !== 0) {
            http_response_code(403);
            exit('Bu sayfaya erisim yetkiniz yok.');
        }
    }
}

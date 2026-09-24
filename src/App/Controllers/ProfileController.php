<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\ImageService;
use App\Services\UserService;
use App\Support\Csrf;
use Exception;

/**
 * Handles profile editing, password changes, and avatar uploads.
 */
class ProfileController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private UserService $userService,
        private ImageService $imageService
    ) {}

    public function edit(): void
    {
        $userId = $this->requireLogin();
        $user = $this->userService->getById($userId);
        if ($user === null) {
            $this->session->clear();
            header('Location: /login/identifier');
            exit;
        }
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'profile.php';
    }

    public function update(): void
    {
        $userId = $this->requireLogin();
        $this->requireCsrf('/profile');

        try {
            $this->userService->updateProfile(
                $userId,
                (string)$this->request->post('name', ''),
                (string)$this->request->post('lastname', ''),
                (string)$this->request->post('username', ''),
                (string)$this->request->post('email', ''),
                (string)$this->request->post('bio', '')
            );
            // Keep the navbar username in sync.
            $this->session->set('username', trim((string)$this->request->post('username', '')));
            $this->session->setFlash('flash_message', 'Profil je ažuriran.');
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        }

        header('Location: /profile');
        exit;
    }

    public function changePassword(): void
    {
        $userId = $this->requireLogin();
        $this->requireCsrf('/profile');

        try {
            $this->userService->changePassword(
                $userId,
                (string)$this->request->post('current_password', ''),
                (string)$this->request->post('new_password', '')
            );
            $this->session->setFlash('flash_message', 'Lozinka je promenjena.');
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        }

        header('Location: /profile');
        exit;
    }

    public function uploadAvatar(): void
    {
        $userId = $this->requireLogin();
        $this->requireCsrf('/profile');

        try {
            $file = $_FILES['avatar'] ?? null;
            if (!$file) {
                throw new Exception('Niste izabrali sliku.');
            }
            $path = $this->imageService->uploadAvatar($userId, $file);

            $oldPath = $this->userService->updateAvatar($userId, $path);
            $this->imageService->deleteAvatar($oldPath ?? '');

            $this->session->setFlash('flash_message', 'Profilna slika je ažurirana.');
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        }

        header('Location: /profile');
        exit;
    }

    private function requireLogin(): int
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }
        return (int)$userId;
    }

    private function requireCsrf(string $redirectTo): void
    {
        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash('flash_message', 'Nevažeći sigurnosni token.');
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

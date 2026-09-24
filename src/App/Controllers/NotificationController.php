<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Session;
use App\Services\NotificationService;
use App\Support\Csrf;

/**
 * Handles notification pages and notification-related JSON endpoints.
 */
class NotificationController
{
    public function __construct(
        private Session $session,
        private NotificationService $notificationService
    ) {}

    /**
     * Displays the authenticated user's notifications
     * and marks them as read.
     */
    public function index(): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        $userId = (int)$userId;

        $notifications = $this->notificationService->getForUser($userId);

        // Mark everything read once the user opens the page.
        $this->notificationService->markAllRead($userId);

        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'notifications.php';
    }

    public function unreadCount(): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            json_response(['success' => true, 'count' => 0]);
        }

        json_response([
            'success' => true,
            'count' => $this->notificationService->unreadCount((int)$userId)
        ]);
    }

    public function markRead(): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            json_response(['success' => false], 401);
        }

        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            json_response([
                'success' => false,
                'message' => 'Nevažeći sigurnosni token. Osvežite stranicu.'
            ], 419);
        }

        $this->notificationService->markAllRead((int)$userId);

        json_response(['success' => true]);
    }
}
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\MessageService;
use App\Support\Csrf;
use Exception;

/**
 * Handles user conversations, messaging, and message read status.
 */
class MessageController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private MessageService $messageService
    ) {}

    /**
     * Displays the user's conversation inbox.
     */
    public function inbox(): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        $conversations = $this->messageService->getInbox((int)$userId);

        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'inbox.php';
    }

    /**
     * Displays messages from a specific conversation.
     */
    public function viewConversation(int $conversationId): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        try {
            $messages = $this->messageService->getMessages(
                $conversationId,
                (int)$userId
            );
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
            header('Location: /messages/inbox');
            exit;
        }

        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'conversation.php';
    }

    /**
     * Sends a message in a conversation and redirects back to it.
     */
    public function sendMessage(int $conversationId): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token. Osvežite stranicu.'
            );
            header("Location: /messages/conversation/$conversationId");
            exit;
        }

        $message = $this->request->post('message', '');

        try {
            $this->messageService->sendMessage(
                $conversationId,
                (int)$userId,
                $message
            );

            header("Location: /messages/conversation/$conversationId");
            exit;
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
            header("Location: /messages/conversation/$conversationId");
            exit;
        }
    }

    /**
     * Creates a conversation for a listing and redirects to it.
     */
    public function createConversation(): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        $listingId = (int)$this->request->post('listing_id', 0);

        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token. Osvežite stranicu.'
            );
            header("Location: /view/listing/$listingId");
            exit;
        }

        try {
            $conversationId = $this->messageService->create(
                $listingId,
                (int)$userId
            );

            header("Location: /messages/conversation/$conversationId");
            exit;
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
            header("Location: /view/listing/$listingId");
            exit;
        }
    }

    /**
     * Marks a conversation as read for the authenticated user.
     */
    public function markAsRead(): void
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            json_response([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            json_response([
                'success' => false,
                'message' => 'Nevažeći sigurnosni token. Osvežite stranicu.'
            ], 419);
        }

        $conversationId = (int)$this->request->post('conversation_id', 0);

        if ($conversationId <= 0) {
            json_response([
                'success' => false,
                'message' => 'Invalid conversation'
            ], 400);
        }

        try {
            $this->messageService->markAsRead($conversationId, (int)$userId);

            json_response(['success' => true]);
        } catch (Exception $e) {
            json_response([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
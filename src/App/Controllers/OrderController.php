<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Session;
use App\Services\OrderService;
use App\Support\Csrf;
use Exception;

/**
 * Handles order-related HTTP requests such as purchases
 * and displaying the user's orders.
 */
class OrderController
{
    public function __construct(
        private Session $session,
        private OrderService $orderService
    ) {}

    /**
     * Handle an immediate "Buy Now" purchase request.
     */
    public function buyNow(): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            json_response(['success' => false, 'redirect' => '/login/identifier'], 401);
        }

        // Verify the CSRF token before processing the purchase request.
        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            json_response(['success' => false, 'message' => 'Nevažeći sigurnosni token. Osvežite stranicu.'], 419);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $listingId = (int)($input['listing_id'] ?? 0);

        try {
            $result = $this->orderService->buyNow((int)$userId, $listingId);
            json_response([
                'success' => true,
                'message' => 'Kupovina uspešna!',
                'redirect' => '/orders',
                'data' => $result,
            ]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Display the authenticated user's purchases and sales.
     */
    public function index(): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }
        $userId = (int)$userId;

        // Finalize any ended auctions before loading the user's orders.
        $this->orderService->finalizeEndedAuctions();

        $purchases = $this->orderService->getPurchases($userId);
        $sales = $this->orderService->getSales($userId);
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'orders.php';
    }
}

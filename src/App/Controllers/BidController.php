<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Session;
use App\Services\BidService;
use App\Support\Csrf;
use Exception;

/**
 * Handles HTTP requests related to auction bidding
 * and returns JSON responses to the client.
 */
class BidController
{
    public function __construct(
        private Session $session,
        private BidService $bidService
    ) {}

    public function placeBid(): void
    {
        header('Content-Type: application/json');

        $userId = (int)($this->session->get('user_id') ?? 0);

        if ($userId === 0) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'redirect' => '/login/identifier'
            ]);

            return;
        }

        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            http_response_code(419);
            echo json_encode([
                'success' => false,
                'message' => 'Nevažeći sigurnosni token. Osvežite stranicu.'
            ]);

            return;
        }

        $input = json_decode(file_get_contents("php://input"), true);

        $listingId = (int)($input['listing_id'] ?? 0);
        $price = (float)($input['price'] ?? 0);

        try {
            $result = $this->bidService->placeBid($userId, $listingId, $price);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

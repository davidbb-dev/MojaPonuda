<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\FavoriteService;
use App\Support\Csrf;

/**
 * Handles favorite toggle requests and the user's favorites page.
 */
class FavoriteController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private FavoriteService $favoriteService
    ) {}

    public function toggle(): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            json_response(['success' => false, 'redirect' => '/login/identifier'], 401);
        }

        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            json_response(['success' => false, 'message' => 'Nevažeći sigurnosni token.'], 419);
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $listingId = (int)($input['listing_id'] ?? $this->request->post('listing_id', 0));

        if ($listingId <= 0) {
            json_response(['success' => false, 'message' => 'Neispravan oglas.'], 400);
        }

        $favorited = $this->favoriteService->toggle((int)$userId, $listingId);
        json_response(['success' => true, 'favorited' => $favorited]);
    }

    public function index(): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        $allListings = $this->favoriteService->getForUser((int)$userId);
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'favorites.php';
    }
}

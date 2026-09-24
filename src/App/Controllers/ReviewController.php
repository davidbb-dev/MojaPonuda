<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\ReviewService;
use App\Support\Csrf;
use Exception;

/**
 * Handles HTTP requests for creating listing reviews.
 */
class ReviewController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private ReviewService $reviewService
    ) {}

    public function create(): void
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }

        $listingId = (int)$this->request->post('listing_id', 0);

        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash('flash_message', 'Nevažeći sigurnosni token.');
            header("Location: /view/listing/$listingId");
            exit;
        }

        $rating = (int)$this->request->post('rating', 0);
        $comment = (string)$this->request->post('comment', '');

        try {
            $this->reviewService->create((int)$userId, $listingId, $rating, $comment);
            $this->session->setFlash('flash_message', 'Hvala na recenziji!');
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        }

        header("Location: /view/listing/$listingId");
        exit;
    }
}

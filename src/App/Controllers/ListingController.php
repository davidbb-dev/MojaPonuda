<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\UserHasBidsException;
use App\Http\Request;
use App\Http\Session;
use App\Services\BidService;
use App\Services\CategoryService;
use App\Services\FavoriteService;
use App\Services\ImageService;
use App\Services\ListingService;
use App\Services\MessageService;
use App\Services\OrderService;
use App\Services\ReviewService;
use App\Services\UserService;
use App\Support\Csrf;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Handles listing-related HTTP requests, including listing views,
 * creation/editing, AJAX actions, and listing-specific data endpoints.
 */
class ListingController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private ListingService $listingService,
        private CategoryService $categoryService,
        private ImageService $imageService,
        private BidService $bidService,
        private MessageService $messageService,
        private FavoriteService $favoriteService,
        private ReviewService $reviewService,
        private UserService $userService,
        private OrderService $orderService
    ) {}

    //*-*-*-*-*-*-*-*-*
    // VIEWS
    //*-*-*-*-*-*-*-*-*
    public function createListingView()
    {
        if ($this->session->get('user_id') === null) {
            header('Location: /');
            exit;
        }

        $form = [
            'mode' => 'create',
            'action' => '/create/listing',
            'fields' => [
                'listing_type' => ['disabled' => false],
                'duration' => ['disabled' => false],
                'ended_at' => ['disabled' => false]
            ],
            'buttons' => [
                'primary' => [
                    'label' => 'Postavi oglas',
                    'name' => 'action',
                    'value' => 'publish',
                    'id' => 'primary_btn_create'
                ],
                'secondary' => [
                    'label' => 'Sacuvaj u pripremu',
                    'name' => 'action',
                    'value' => 'save_draft',
                    'id' => 'secondary_btn_create'
                ],
            ],
            'values' => [
                'data' => [
                    'name' => '',
                    'description' => '',
                    'listing_type' => 'fixed_price',
                    'duration' => '',
                    'ended_at' => '',
                    'starting_price' => '',
                    'category_id' => ''
                ],
                'images' => []
            ],
            'text' => [
                'title' => 'Dodavanje oglasa',
                'h1' => 'Dodavanje novog oglasa'
            ]
        ];

        $flashMessage = $this->session->getFlash('flash_message');
        $allCategories = $this->categoryService->getAll();

        include VIEW_PATH . 'create_listing.php';
    }

    public function editListingView(int $listingId)
    {
        if ($this->session->get('user_id') === null) {
            header('Location: /');
            exit;
        }

        $listing = $this->listingService->getListingById($listingId);

        if (!$listing) {
            header('Location: /my-listings');
            exit;
        }

        if ((int)$listing['user_id'] !== (int)$this->session->get('user_id')) {
            header('Location: /my-listings');
            exit;
        }

        $form = [
            'mode' => 'edit',
            'action' => "/edit/listing/{$listingId}",
            'fields' => [
                'listing_type' => ['disabled' => $listing['status'] === 'active'],
                'duration' => ['disabled' => $listing['status'] === 'active'],
                'ended_at' => ['disabled' => $listing['status'] === 'active']
            ],
            'buttons' => [
                'primary' => [
                    'label' => 'Sacuvaj izmenu',
                    'name' => 'action',
                    'value' => 'publish',
                    'id' => 'primary_btn_edit'
                ],
                'secondary' => [
                    'label' => 'Sacuvaj i pauziraj',
                    'name' => 'action',
                    'value' => 'pause',
                    'id' => 'secondary_btn_edit'
                ],
            ],
            'values' => [
                'data' => [
                    'name' => $listing['name'],
                    'description' => $listing['description'],
                    'listing_type' => $listing['listing_type'],
                    'starting_price' => $listing['starting_price'],
                    'category_id' => $listing['category_id']
                ],
                'images' => $listing['images']
            ],
            'text' => [
                'title' => 'Izmena oglasa',
                'h1' => 'Izmena oglasa'
            ]
        ];

        $allCategories = $this->categoryService->getAll();
        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'create_listing.php';
    }

    public function listingView(int $listingId)
    {
        $listing = $this->listingService->getListingById($listingId);

        if (!$listing) {
            header('Location: /');
            exit;
        }

        $this->listingService->incrementViews($listingId);

        // Remember this listing in a cookie ("Nedavno gledano" on the home page).
        $this->rememberRecentlyViewed($listingId);

        $bidsData = null;
        if ($listing['listing_type'] === 'auction') {
            $bidsData = $this->bidService->getBidsData($listingId);
        }

        $userId = $this->session->get('user_id');
        $userId = $userId !== null ? (int)$userId : null;

        $conversationId = null;
        $isFavorited = false;
        $canReview = false;

        if ($userId !== null) {
            $conversationId = $this->messageService->findConversation($listingId, $userId);
            $isFavorited = $this->favoriteService->isFavorited($userId, $listingId);
            $canReview = $this->reviewService->canReview($userId, $listingId);
        }

        $seller = $this->userService->getById((int)$listing['user_id']);
        $sellerRating = $this->reviewService->getSellerRating((int)$listing['user_id']);
        $sellerReviews = $this->reviewService->getForSeller((int)$listing['user_id'], 5);

        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'view_listing.php';
    }

    public function showMyListingsView()
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            header('Location: /');
            exit;
        }

        // Settle any auctions that ended while the user was away.
        $this->orderService->finalizeEndedAuctions();

        $flashMessage = $this->session->getFlash('flash_message');
        $allListings = $this->listingService->getMappedUserListingsForView($userId);

        include VIEW_PATH . 'my_listings.php';
    }

    //------------------
    // CREATE / EDIT LOGIC
    //------------------
    public function createListing()
    {
        if ($this->session->get('user_id') === null) {
            header('Location: /login/identifier');
            exit;
        }

        if (!$this->checkFormCsrf()) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token. Osvežite stranicu.'
            );
            header('Location: /create/listing');
            exit;
        }

        $duration = $this->request->post('duration', '');
        $userId = (int)$this->session->get('user_id', '');

        $name = $this->request->post('listing_name', '');
        $description = trim($this->request->post('listing_description', ''));
        $startingPrice = $this->request->post('listing_starting_price', '');

        $action = $this->request->post('action', '');
        $listingType = $this->request->post('listing_type', '');
        $categoryId = (int)$this->request->post('category_id', '');

        if ($name === '' || $description === '' || $startingPrice === '' || $listingType === '' || $categoryId <= 0) {
            $this->session->setFlash('flash_message', 'Sva polja je neophodno popuniti.');
            header('Location: /create/listing');
            exit;
        }

        $startedAt = new DateTime();
        $endedAt = ($listingType === 'auction')
            ? (clone $startedAt)->modify("+{$duration} days")
            : (clone $startedAt)->modify("+30 days");

        try {
            $status = $this->listingService->resolveStatusFromAction($action);

            $tz = new DateTimeZone('UTC');
            $startedAtUTC = (clone $startedAt)->setTimezone($tz);
            $endedAtUTC = (clone $endedAt)->setTimezone($tz);

            $uploadedImages = $_FILES['file_input'] ?? null;

            $this->imageService->validateImages($uploadedImages);

            $listingId = $this->listingService->create(
                $name,
                $description,
                $startingPrice,
                $startedAtUTC,
                $endedAtUTC,
                $status,
                $listingType,
                $userId,
                $categoryId
            );

            $this->imageService->uploadImagesForListing($userId, $listingId, $uploadedImages);
            $this->imageService->renumberPositions($listingId);

            $this->session->setFlash('flash_message', 'Oglas je uspešno kreiran!');
            header('Location: /my-listings');
            exit;
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
            header('Location: /create/listing');
            exit;
        }
    }

    public function editListing(int $listingId)
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }
        $userId = (int)$userId;

        $listing = $this->listingService->getListingById($listingId);
        if (!$listing || (int)$listing['user_id'] !== $userId) {
            header('Location: /my-listings');
            exit;
        }

        if (!$this->checkFormCsrf()) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token. Osvežite stranicu.'
            );
            header("Location: /edit/listing/$listingId");
            exit;
        }

        $name = $this->request->post('listing_name', '');
        $description = trim($this->request->post('listing_description', ''));
        $startingPrice = $this->request->post('listing_starting_price', '');
        $categoryId = (int)$this->request->post('category_id', 0);
        $action = $this->request->post('action', 'publish');

        if ($name === '' || $description === '' || $startingPrice === '' || $categoryId <= 0) {
            $this->session->setFlash('flash_message', 'Sva polja je neophodno popuniti.');
            header("Location: /edit/listing/$listingId");
            exit;
        }

        $imagesForDeletion = json_decode((string)$this->request->post('images_for_deletion', '[]'), true) ?: [];
        $imagesOrder = json_decode((string)$this->request->post('images_order', '[]'), true) ?: [];
        $uploadedImages = $_FILES['file_input'] ?? null;

        // Restrict image operations to images currently belonging to this listing.
        $currentImageIds = array_map(fn($img) => (int)$img['image_id'], $listing['images']);
        $safeDeleteIds = array_values(array_intersect(
            array_map('intval', (array)$imagesForDeletion),
            $currentImageIds
        ));

        $safeImagesOrder = array_values(array_filter(
            (array)$imagesOrder,
            fn($img) =>
                ($img['type'] ?? '') === 'existing'
                && isset($img['id'])
                && in_array((int)$img['id'], $currentImageIds, true)
        ));

        $newValidCount = $this->countValidUploads($uploadedImages);
        $finalImageCount = count($currentImageIds) - count($safeDeleteIds) + $newValidCount;

        if ($finalImageCount < 1) {
            $this->session->setFlash('flash_message', 'Oglas mora imati bar jednu sliku.');
            header("Location: /edit/listing/$listingId");
            exit;
        }

        if ($finalImageCount > 15) {
            $this->session->setFlash('flash_message', 'Mozete imati maksimalno 15 slika.');
            header("Location: /edit/listing/$listingId");
            exit;
        }

        try {
            $status = $this->listingService->resolveStatusFromAction($action);

            if ($newValidCount > 0) {
                $this->imageService->validateImages($uploadedImages, $listingId);
            }

            $this->listingService->updateBasics(
                $userId,
                $listingId,
                $name,
                $description,
                $startingPrice,
                $categoryId,
                $status
            );

            if (!empty($safeDeleteIds)) {
                $this->imageService->deleteImagesFromDB($safeDeleteIds);
            }

            // Reorder existing images, then append new ones after them.
            $this->imageService->updateImageOrder($safeImagesOrder);
            if ($newValidCount > 0) {
                // Ensure new images sort after existing images before renumbering.
                $offset = 1000;
                $this->imageService->uploadImagesForListing($userId, $listingId, $uploadedImages, $offset);
            }
            $this->imageService->renumberPositions($listingId);

            $this->session->setFlash('flash_message', 'Izmene su sačuvane.');
            header('Location: /my-listings');
            exit;
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
            header("Location: /edit/listing/$listingId");
            exit;
        }
    }

    //------------------
    // AJAX METHODS
    //------------------
    public function pauseListingAjax(): void
    {
        $userId = $this->requireUserAjax();
        $this->requireAjaxCsrf();

        $listingId = (int)$this->request->post('listing_id', 0);

        try {
            $ok = $this->listingService->pauseListing($userId, $listingId);
            json_response(['success' => $ok, 'message' => $ok ? 'Oglas je pauziran.' : 'Nije moguće pauzirati oglas.']);
        } catch (UserHasBidsException $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Greška na serveru.'], 500);
        }
    }

    public function activateListingAjax(): void
    {
        $userId = $this->requireUserAjax();
        $this->requireAjaxCsrf();

        $listingId = (int)$this->request->post('listing_id', 0);

        try {
            $ok = $this->listingService->activateListing($userId, $listingId);
            json_response(['success' => $ok, 'message' => $ok ? 'Oglas je aktiviran.' : 'Nije moguće aktivirati oglas.']);
        } catch (UserHasBidsException $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Greška na serveru.'], 500);
        }
    }

    public function deleteListingAjax(): void
    {
        $userId = $this->requireUserAjax();
        $this->requireAjaxCsrf();

        $listingId = (int)$this->request->post('listing_id', 0);

        try {
            $ok = $this->listingService->softDeleteListing($userId, $listingId);
            json_response(['success' => (bool)$ok, 'message' => $ok ? 'Oglas je obrisan.' : 'Nije moguće obrisati oglas.']);
        } catch (UserHasBidsException $e) {
            json_response(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'Greška na serveru.'], 500);
        }
    }

    public function expireListing(): void
    {
        $userId = $this->requireUserAjax();
        $this->requireAjaxCsrf();

        $data = json_decode(file_get_contents('php://input'), true);
        $listingId = (int)($data['listing_id'] ?? 0);

        if ($listingId <= 0) {
            json_response(['success' => false, 'error' => 'Invalid ID'], 400);
        }

        try {
            $expired = $this->listingService->markListingAsExpired($userId, $listingId);

            if (!$expired) {
                json_response(['success' => false, 'error' => 'Listing cannot be expired.'], 400);
            }

            // Assign a winner and create an order for ended auctions with bids.
            $this->orderService->finalizeEndedAuctions();

            json_response(['success' => true, 'status' => 'expired']);
        } catch (Exception $e) {
            json_response(['success' => false, 'error' => 'Server error'], 500);
        }
    }

    public function listingBids(int $listingId): void
    {
        $bids = $this->listingService->getBidHistory($listingId);
        json_response(['success' => true, 'data' => $bids]);
    }

    //------------------
    // HELPERS
    //------------------

    /**
     * Maintains a "recently viewed" list in a browser cookie:
     * newest first, de-duplicated, capped at 8, and kept for 30 days.
     */
    private function rememberRecentlyViewed(int $listingId): void
    {
        $raw = $_COOKIE['recently_viewed'] ?? '';
        $ids = array_filter(array_map('intval', explode(',', (string)$raw)), fn($id) => $id > 0);

        $ids = array_values(array_filter($ids, fn($id) => $id !== $listingId));
        array_unshift($ids, $listingId);
        $ids = array_slice($ids, 0, 8);

        $value = implode(',', $ids);

        setcookie('recently_viewed', $value, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        // Make the new value available through $_COOKIE during the current request.
        $_COOKIE['recently_viewed'] = $value;
    }

    private function requireUserAjax(): int
    {
        $userId = $this->session->get('user_id');

        if ($userId === null) {
            json_response([
                'success' => false,
                'message' => 'Niste prijavljeni.',
                'redirect' => '/login/identifier'
            ], 401);
        }

        return (int)$userId;
    }

    private function checkFormCsrf(): bool
    {
        return Csrf::check($this->request->post('_csrf'));
    }

    private function requireAjaxCsrf(): void
    {
        if (!Csrf::check($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            json_response([
                'success' => false,
                'message' => 'Nevažeći sigurnosni token. Osvežite stranicu.'
            ], 419);
        }
    }

    private function countValidUploads(?array $uploaded): int
    {
        if (!$uploaded || !isset($uploaded['error'])) {
            return 0;
        }

        $count = 0;

        foreach ((array)$uploaded['error'] as $err) {
            if ($err === UPLOAD_ERR_OK) {
                $count++;
            }
        }

        return $count;
    }
}
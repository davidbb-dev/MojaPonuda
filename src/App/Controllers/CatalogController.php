<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\CategoryService;
use App\Services\ListingService;
use App\Services\ReviewService;
use App\Services\UserService;

/**
 * Handles catalog search, category browsing, and public profile pages.
 */
class CatalogController
{
    private const PER_PAGE = 12;

    public function __construct(
        private Request $request,
        private Session $session,
        private ListingService $listingService,
        private CategoryService $categoryService,
        private UserService $userService,
        private ReviewService $reviewService
    ) {}

    public function search(): void
    {
        $this->renderResults(
            heading: 'Pretraga oglasa',
            forcedCategoryId: null
        );
    }

    public function category(int $categoryId): void
    {
        $category = $this->categoryService->getById($categoryId);
        if (!$category) {
            header('Location: /categories');
            exit;
        }
        $this->renderResults(
            heading: 'Kategorija: ' . $category['name'],
            forcedCategoryId: $categoryId
        );
    }

    public function categories(): void
    {
        $categories = $this->categoryService->getAllWithCounts();
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'categories.php';
    }

    public function publicProfile(int $userId): void
    {
        $profile = $this->userService->getPublicProfile($userId);
        if (!$profile) {
            header('Location: /');
            exit;
        }
        $rating = $this->reviewService->getSellerRating($userId);
        $reviews = $this->reviewService->getForSeller($userId, 20);
        $listings = $this->listingService->getPublicListingsByUser($userId);
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'public_profile.php';
    }

    private function renderResults(string $heading, ?int $forcedCategoryId): void
    {
        $q = trim((string)$this->request->get('q', ''));
        $categoryId = $forcedCategoryId ?? $this->intOrNull($this->request->get('category'));
        $type = (string)$this->request->get('type', '');
        $type = in_array($type, ['auction', 'fixed_price'], true) ? $type : null;
        $sort = (string)$this->request->get('sort', 'newest');
        $minPrice = $this->floatOrNull($this->request->get('min_price'));
        $maxPrice = $this->floatOrNull($this->request->get('max_price'));

        $page = max(1, (int)$this->request->get('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $total = 0;
        $listings = $this->listingService->searchListings(
            $q,
            $categoryId,
            $type,
            $minPrice,
            $maxPrice,
            $sort,
            self::PER_PAGE,
            $offset,
            $total
        );

        $totalPages = max(1, (int)ceil($total / self::PER_PAGE));
        $allCategories = $this->categoryService->getAll();

        // Query-string builder for pagination links (preserves filters).
        $baseParams = array_filter([
            'q' => $q !== '' ? $q : null,
            'category' => $forcedCategoryId === null ? $categoryId : null,
            'type' => $type,
            'sort' => $sort !== 'newest' ? $sort : null,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
        ], fn($v) => $v !== null);

        $basePath = $forcedCategoryId !== null ? "/category/$forcedCategoryId" : '/search';

        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'search.php';
    }

    private function intOrNull(mixed $value): ?int
    {
        return ($value !== null && $value !== '' && (int)$value > 0) ? (int)$value : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return ($value !== null && $value !== '' && is_numeric($value)) ? (float)$value : null;
    }
}

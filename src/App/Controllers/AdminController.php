<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\ActivityLogService;
use App\Services\AdminService;
use App\Services\CategoryService;
use App\Services\UserService;
use App\Support\Csrf;
use App\Exceptions\CategoryAlreadyExistsException;
use Exception;

/**
 * Handles administrative pages and actions for users, listings,
 * categories, reviews, and activity logs.
 */
class AdminController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private AdminService $adminService,
        private CategoryService $categoryService,
        private UserService $userService,
        private ActivityLogService $activityLog
    ) {}

    // -------------------------------------------------------
    // Guards
    // -------------------------------------------------------

    private function authorize(): array 
    {
        $userId = $this->session->get('user_id');
        if ($userId === null) {
            header('Location: /login/identifier');
            exit;
        }
        $user = $this->userService->getById((int)$userId);
        if (!$user || !in_array($user['role'], ['admin', 'superadmin'], true) || $user['status'] !== 'active') {
            http_response_code(403);
            $this->session->setFlash('flash_message', 'Nemate pristup administraciji.');
            header('Location: /');
            exit;
        }
        return $user;
    }

    private function authorizePost(): array 
    {
        $admin = $this->authorize();
        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash('flash_message', 'Nevažeći sigurnosni token. Pokušajte ponovo.');
            header('Location: /admin');
            exit;
        }
        return $admin;
    }

    // -------------------------------------------------------
    // Pages
    // -------------------------------------------------------

    public function dashboard(): void 
    {
        $admin = $this->authorize();
        $active = 'dashboard';
        $stats = $this->adminService->getStats(); 
        $recentLogs = $this->adminService->getRecentLogs(8); 
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/dashboard.php';
    }

    public function users(): void
    {
        $admin = $this->authorize();
        $active = 'users';
        $search = trim((string)$this->request->get('q', ''));
        $users = $this->adminService->getUsers($search);
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/users.php';
    }

    public function updateUserRole(): void
    {
        $admin = $this->authorizePost();
        if ($admin['role'] !== 'superadmin') {
            $this->session->setFlash('flash_message', 'Samo superadmin može menjati uloge.');
            header('Location: /admin/users');
            exit;
        }
        $targetId = (int)$this->request->post('user_id', 0); 
        $role = (string)$this->request->post('role', 'user'); 

        if ($targetId === (int)$admin['user_id']) {
            $this->session->setFlash('flash_message', 'Ne možete promeniti sopstvenu ulogu.');
        } else {
            $this->adminService->setUserRole($targetId, $role);
            $this->session->setFlash('flash_message', 'Uloga korisnika je ažurirana.');
        }
        header('Location: /admin/users');
        exit;
    }

    public function setUserStatus(): void 
    {
        $admin = $this->authorizePost();
        $targetId = (int)$this->request->post('user_id', 0);
        $status = (string)$this->request->post('status', 'active');

        if ($targetId === (int)$admin['user_id']) {
            $this->session->setFlash('flash_message', 'Ne možete blokirati sami sebe.');
            header('Location: /admin/users');
            exit;
        }

        $targetRole = $this->adminService->getRole($targetId);
        if ($targetRole === 'superadmin') {
            $this->session->setFlash('flash_message', 'Ne možete menjati superadmin nalog.');
            header('Location: /admin/users');
            exit;
        }

        $this->adminService->setUserStatus($targetId, $status);


        $this->session->setFlash('flash_message', $status === 'blocked' ? 'Korisnik je blokiran.' : 'Korisnik je odblokiran.');
        header('Location: /admin/users');
        exit;
    }

    public function deleteUser(): void
    {
        $admin = $this->authorizePost();
        if ($admin['role'] !== 'superadmin') {
            $this->session->setFlash('flash_message', 'Samo superadmin može brisati korisnike.');
            header('Location: /admin/users');
            exit;
        }
        $targetId = (int)$this->request->post('user_id', 0);
        if ($targetId === (int)$admin['user_id']) {
            $this->session->setFlash('flash_message', 'Ne možete obrisati sopstveni nalog.');
        } else {
            $this->adminService->softDeleteUser($targetId);
            $this->session->setFlash('flash_message', 'Korisnik je obrisan.');
        }
        header('Location: /admin/users');
        exit;
    }

    public function listings(): void 
    {
        $admin = $this->authorize();
        $active = 'listings';
        $search = trim((string)$this->request->get('q', ''));
        $status = trim((string)$this->request->get('status', '')); 
        $listings = $this->adminService->getListings($search, $status);
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/listings.php';
    }

    public function deleteListing(): void
    {
        $this->authorizePost();
        $listingId = (int)$this->request->post('listing_id', 0);
        $this->adminService->softDeleteListing($listingId);
        $this->session->setFlash('flash_message', 'Oglas je uklonjen.');
        header('Location: /admin/listings');
        exit;
    }

    public function toggleFeatured(): void
    {
        $this->authorizePost();
        $listingId = (int)$this->request->post('listing_id', 0);
        $now = $this->adminService->toggleFeatured($listingId);
        $this->session->setFlash('flash_message', $now ? 'Oglas je istaknut.' : 'Oglas više nije istaknut.');
        header('Location: /admin/listings');
        exit;
    }

    public function categories(): void
    {
        $admin = $this->authorize();
        $active = 'categories';
        $categories = $this->categoryService->getAllWithCounts(); 
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/categories.php';
    }

    public function createCategory(): void
    {
        $this->authorizePost();
        try {
            $this->categoryService->create(
                (string)$this->request->post('name', ''),
                (string)$this->request->post('description', '')
            );
            $this->session->setFlash('flash_message', 'Kategorija je dodata.');
        } catch (CategoryAlreadyExistsException $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        }
        header('Location: /admin/categories');
        exit;
    }

    public function updateCategory(): void
    {
        $this->authorizePost();
        try {
            $this->categoryService->update(
                (int)$this->request->post('category_id', 0),
                (string)$this->request->post('name', ''),
                (string)$this->request->post('description', '')
            );
            $this->session->setFlash('flash_message', 'Kategorija je ažurirana.');
        } catch (Exception $e) {
            $this->session->setFlash('flash_message', $e->getMessage());
        }
        header('Location: /admin/categories');
        exit;
    }

    public function deleteCategory(): void
    {
        $this->authorizePost();
        $this->categoryService->delete((int)$this->request->post('category_id', 0));
        $this->session->setFlash('flash_message', 'Kategorija je obrisana.');
        header('Location: /admin/categories');
        exit;
    }

    public function reviews(): void
    {
        $admin = $this->authorize();
        $active = 'reviews';
        $reviews = $this->adminService->getReviews();
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/reviews.php';
    }

    public function deleteReview(): void
    {
        $this->authorizePost();
        $this->adminService->deleteReview((int)$this->request->post('review_id', 0));
        $this->session->setFlash('flash_message', 'Recenzija je obrisana.');
        header('Location: /admin/reviews');
        exit;
    }

    public function logs(): void
    {
        $admin = $this->authorize();
        $active = 'logs';
        $logs = $this->adminService->getRecentLogs(200);
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/logs.php';
    }

    /**
     * Reads the plain-text access log file from disk and displays it,
     * together with access statistics derived from the same file.
     */
    public function fileLog(): void
    {
        $admin = $this->authorize();
        $active = 'filelog';
        $logContent = $this->activityLog->readLogFile(300);
        $fileStats = $this->activityLog->fileStats();
        $flashMessage = $this->session->getFlash('flash_message');
        include VIEW_PATH . 'admin/file_log.php';
    }
}

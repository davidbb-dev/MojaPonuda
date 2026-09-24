<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Enums\ListingStatusEnum;
use App\Enums\ListingTypeEnum;
use App\Exceptions\InvalidUserInputException;
use App\Exceptions\UserAlreadyExistsException;
use App\Http\Request;
use App\Http\Session;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Services\ListingService;
use App\Services\OrderService;
use App\Support\Csrf;
use Exception;

/**
 * Handles user registration, authentication, logout,
 * and user-related page requests.
 */
class UserController
{
    public function __construct(
        private Request $request,
        private Session $session,
        private AuthService $authService,
        private ListingService $listingService,
        private ActivityLogService $activityLog,
        private OrderService $orderService
    ) {}

    //------------------
    // VIEWS
    //------------------
    public function registerView(): void
    {
        $old = $this->session->get('register_old', []);
        $this->session->remove('register_old');

        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'register.php';
    }

    public function loginIdentifierView(): void
    {
        include VIEW_PATH . 'login_identifier.php';
    }

    public function loginPasswordView(): void
    {
        include VIEW_PATH . 'login_password.php';
    }

    public function homeView(): void
    {
        // Settle auctions that ended while nobody was looking.
        $this->orderService->finalizeEndedAuctions();

        $featuredListings = $this->listingService->getFeatured(8);

        $fixedPriceListings = $this->listingService->getListings(
            listingTypeEnum: ListingTypeEnum::FIXED_PRICE,
            listingStatusEnum: ListingStatusEnum::ACTIVE,
            limit: 8
        );

        $auctionListings = $this->listingService->getListings(
            listingTypeEnum: ListingTypeEnum::AUCTION,
            listingStatusEnum: ListingStatusEnum::ACTIVE,
            limit: 8
        );

        // "Nedavno gledano" — read the recently-viewed listing IDs from the cookie.
        $recentlyViewed = [];
        $rawRecent = $_COOKIE['recently_viewed'] ?? '';

        if ($rawRecent !== '') {
            $recentIds = array_filter(
                array_map(
                    'intval',
                    explode(',', (string) $rawRecent)
                ),
                fn($id) => $id > 0
            );

            if (!empty($recentIds)) {
                $recentlyViewed = $this->listingService->getListingsByIds($recentIds);
            }
        }

        $flashMessage = $this->session->getFlash('flash_message');

        include VIEW_PATH . 'home.php';
    }

    //------------------
    // REGISTRATION
    //------------------
    public function register(): void
    {
        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token.'
            );

            header('Location: /register');
            exit;
        }

        $name = trim($this->request->post('name', ''));
        $lastname = trim($this->request->post('lastname', ''));
        $username = trim($this->request->post('username', ''));
        $email = trim($this->request->post('email', ''));
        $password = (string) $this->request->post('password', '');

        $registerOld = [
            'name' => $name,
            'lastname' => $lastname,
            'username' => $username,
            'email' => $email,
        ];

        if (
            $name === '' ||
            $lastname === '' ||
            $username === '' ||
            $email === '' ||
            $password === ''
        ) {
            $this->session->set(
                'register_old',
                $registerOld
            );

            $this->session->setFlash(
                'flash_message',
                'Sva polja je neophodno popuniti.'
            );

            header('Location: /register');
            exit;
        }

        try {
            $newUserId = $this->authService->register(
                $name,
                $lastname,
                $username,
                $email,
                $password
            );

            $this->session->remove('register_old');

            $this->activityLog->log($newUserId, 'register');

            $this->session->setFlash(
                'flash_message',
                'Uspesno ste registrovani!'
            );

            header('Location: /login/identifier');
            exit;
        } catch (UserAlreadyExistsException $e) {
            $this->session->set(
                'register_old',
                $registerOld
            );

            $this->session->setFlash(
                'flash_message',
                $e->getMessage()
            );
        } catch (InvalidUserInputException $e) {
            $this->session->set(
                'register_old',
                $registerOld
            );

            $this->session->setFlash(
                'flash_message',
                $e->getMessage()
            );
        } catch (Exception $e) {
            $this->session->set(
                'register_old',
                $registerOld
            );

            $this->session->setFlash(
                'flash_message',
                'Doslo je do greske, pokusajte ponovo.'
            );
        }

        header('Location: /register');
        exit;
    }

    //------------------
    // VALIDATION
    //------------------
    public function validateIdentifier(): void
    {
        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token.'
            );

            header('Location: /login/identifier');
            exit;
        }

        if ($this->request->post('identifier') !== null) {
            $identifier = trim(
                $this->request->post('identifier', '')
            );

            if ($identifier === '') {
                $this->session->setFlash(
                    'flash_message',
                    'Morate popuniti prazno polje.'
                );

                header('Location: /login/identifier');
                exit;
            }

            $isEmail = filter_var(
                $identifier,
                FILTER_VALIDATE_EMAIL
            );

            $isUsername = preg_match(
                '/^[A-Za-z0-9_]{3,30}$/',
                $identifier
            );

            if (!$isEmail && !$isUsername) {
                $this->session->setFlash(
                    'flash_message',
                    'Unesite validan email ili korisnicko ime.'
                );

                header('Location: /login/identifier');
                exit;
            }

            $this->session->set('identifier', $identifier);

            header('Location: /login/password');
            exit;
        }
    }

    //------------------
    // LOGIN
    //------------------
    public function login(): void
    {
        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token.'
            );

            header('Location: /login/identifier');
            exit;
        }

        if ($this->request->post('password') !== null) {
            $identifier = $this->session->get('identifier', '');

            if ($identifier === '') {
                header('Location: /login/identifier');
                exit;
            }

            $password = $this->request->post('password', '');

            if ($password === '') {
                $this->session->setFlash(
                    'flash_message',
                    'Niste popunili polje za lozinku.'
                );

                header('Location: /login/password');
                exit;
            }

            $userData = $this->authService->login(
                $identifier,
                $password
            );

            if ($userData !== null) {
                if (($userData['status'] ?? 'active') === 'blocked') {
                    $this->session->setFlash(
                        'flash_message',
                        'Vaš nalog je blokiran. Kontaktirajte podršku.'
                    );

                    header('Location: /login/identifier');
                    exit;
                }

                // Prevent session fixation after successful authentication.
                session_regenerate_id(true);

                $this->session->remove('identifier');

                $this->session->set('user_id', $userData['user_id']);
                $this->session->set('username', $userData['username']);
                $this->session->set('role', $userData['role'] ?? 'user');

                $this->activityLog->log(
                    $userData['user_id'],
                    'log_in'
                );

                header('Location: /');
                exit;
            }

            $this->session->setFlash(
                'flash_message',
                'Neuspesno logovanje. Pokusajte da unesete podatke ponovo.'
            );

            header('Location: /login/password');
            exit;
        }
    }

    public function logout(): void
    {
        if (!Csrf::check($this->request->post('_csrf'))) {
            $this->session->setFlash(
                'flash_message',
                'Nevažeći sigurnosni token.'
            );

            header('Location: /');
            exit;
        }

        $userId = $this->session->get('user_id');

        if ($userId !== null) {
            $this->activityLog->log(
                (int) $userId,
                'log_out'
            );
        }

        $this->session->clear();

        header('Location: /');
        exit;
    }
}
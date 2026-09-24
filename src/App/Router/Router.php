<?php
declare(strict_types=1);

namespace App\Router;

use App\Controllers\AdminController;
use App\Controllers\BidController;
use App\Controllers\CatalogController;
use App\Controllers\CategoryController;
use App\Controllers\FavoriteController;
use App\Controllers\ListingController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\OrderController;
use App\Controllers\ProfileController;
use App\Controllers\ReviewController;
use App\Controllers\UserController;
use App\Database\Database;
use App\Http\Request;
use App\Http\Session;
use App\Services\ActivityLogService;
use App\Services\AdminService;
use App\Services\AuthService;
use App\Services\BidService;
use App\Services\CategoryService;
use App\Services\FavoriteService;
use App\Services\ImageService;
use App\Services\ListingService;
use App\Services\MessageService;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\ReviewService;
use App\Services\UserService;

/**
 * Dispatches incoming HTTP requests to the appropriate controller
 * based on the request URI and HTTP method.
 *
 * Also acts as the composition root by creating services and
 * injecting their dependencies into controllers.
 */
class Router{
    public function routing(){
        $db = new Database();
        $pdo = $db->databaseConnection();

        $request = Request::capture();
        $session = new Session();

        //------------------
        // Services - Create application services and inject shared dependencies such as PDO.
        //------------------
        $userService         = new UserService($pdo);
        $notificationService = new NotificationService($pdo);
        $authService         = new AuthService($pdo);
        $activityLog         = new ActivityLogService($pdo);
        $categoryService     = new CategoryService($pdo);
        $listingService      = new ListingService($pdo);
        $imageService        = new ImageService($pdo);
        $bidService          = new BidService($pdo, $listingService, $notificationService);
        $messageService      = new MessageService($pdo);
        $favoriteService     = new FavoriteService($pdo);
        $reviewService       = new ReviewService($pdo);
        $orderService        = new OrderService($pdo, $listingService, $notificationService);
        $adminService        = new AdminService($pdo);

        //------------------
        // Controllers - Create controllers and inject the request, session, and required services.
        //------------------
        $userController         = new UserController($request, $session, $authService, $listingService, $activityLog, $orderService);
        $categoryController     = new CategoryController($request, $session, $categoryService);
        $listingController      = new ListingController($request, $session, $listingService, $categoryService, $imageService, $bidService, $messageService, $favoriteService, $reviewService, $userService, $orderService);
        $bidController          = new BidController($session, $bidService);
        $messageController      = new MessageController($request, $session, $messageService);
        $favoriteController     = new FavoriteController($request, $session, $favoriteService);
        $reviewController       = new ReviewController($request, $session, $reviewService);
        $orderController        = new OrderController($session, $orderService);
        $profileController      = new ProfileController($request, $session, $userService, $imageService);
        $notificationController = new NotificationController($session, $notificationService);
        $catalogController      = new CatalogController($request, $session, $listingService, $categoryService, $userService, $reviewService);
        $adminController        = new AdminController($request, $session, $adminService, $categoryService, $userService, $activityLog);

        $uri = parse_url((string)$request->server('REQUEST_URI'), PHP_URL_PATH);
        $method = $request->server('REQUEST_METHOD');

        // Parameterized routes are checked before static routes
        // because they contain dynamic path parameters such as IDs.

        //------------------
        // Dynamic Routes
        //------------------
        if(preg_match('#^/category/(\d+)$#', $uri, $matches)){
            $categoryId = (int) $matches[1];
            if($method === 'GET'){
                $catalogController->category($categoryId);
                return;
            }
        }

        if(preg_match('#^/user/(\d+)$#', $uri, $matches)){
            $userId = (int) $matches[1];
            if($method === 'GET'){
                $catalogController->publicProfile($userId);
                return;
            }
        }

        if(preg_match('#^/view/listing/(\d+)$#', $uri, $matches)){
            $listingId = (int) $matches[1];
            if($method === 'GET'){
                $listingController->listingView($listingId);
                return;
            }
        }

        if(preg_match('#^/listing/(\d+)/bids$#', $uri, $matches)){
            $listingId = (int) $matches[1];
            if($method === 'GET'){
                $listingController->listingBids($listingId);
                return;
            }
        }

        if(preg_match('#^/edit/listing/(\d+)$#', $uri, $matches)){
            $listingId = (int) $matches[1];

            if($method === 'GET'){
                $listingController->editListingView($listingId);
                return;
            }
            if($method === 'POST'){
                $listingController->editListing($listingId);
                return;
            }
        }

        if(preg_match('#^/messages/conversation/(\d+)$#', $uri, $matches)){
            $conversationId = (int) $matches[1];

            if($method === 'GET'){
                $messageController->viewConversation($conversationId);
                return;
            }
            if($method === 'POST'){
                $messageController->sendMessage($conversationId);
                return;
            }
        }

        //------------------
        // Static Routes
        //------------------
        switch($uri){
            case '/':
                $userController->homeView();
                return;
                break;

            //------------------
            // Public catalog
            //------------------
            case '/search':
                if($method === 'GET'){
                    $catalogController->search();
                    return;
                }
                break;
            case '/categories':
                if($method === 'GET'){
                    $catalogController->categories();
                    return;
                }
                break;

            //------------------
            // Authentication
            //------------------
            case '/login/identifier':
                if($method === 'GET'){
                    $userController->loginIdentifierView();
                    return;
                }
                if($method === 'POST'){
                    $userController->validateIdentifier();
                    return;
                }
                break;
            case '/login/password':
                if($method === 'GET'){
                    $userController->loginPasswordView();
                    return;
                }
                if($method === 'POST'){
                    $userController->login();
                    return;
                }
                break;
            case '/register':
                if($method === 'GET'){
                    $userController->registerView();
                    return;
                }
                if($method === 'POST'){
                    $userController->register();
                    return;
                }
                break;
            case '/logout':
                if($method === 'POST'){
                    $userController->logout();
                    return;
                }
                break;
                
            //------------------
            // Listings
            //------------------
            case '/create/listing':
                if($method === 'GET'){
                    $listingController->createListingView();
                    return;
                }
                if($method === 'POST'){
                    $listingController->createListing();
                    return;
                }
                break;
            case '/my-listings':
                if($method === 'GET'){
                    $listingController->showMyListingsView();
                    return;
                }
                break;
            case '/pause/listing':
                if($method === 'POST'){
                    $listingController->pauseListingAjax();
                    return;
                }
                break;
            case '/activate/listing':
                if($method === 'POST'){
                    $listingController->activateListingAjax();
                    return;
                }
                break;
            case '/delete/listing':
                if($method === 'POST'){
                    $listingController->deleteListingAjax();
                    return;
                }
                break;
            case '/auction/expire':
                if($method === 'POST'){
                    $listingController->expireListing();
                    return;
                }
                break;
            
            //------------------
            // Auctions
            //------------------
            case '/place/bid':
                if($method === 'POST'){
                    $bidController->placeBid();
                    return;
                }
                break;
            
            //------------------
            // Orders
            //------------------
            case '/buy-now':
                if($method === 'POST'){
                    $orderController->buyNow();
                    return;
                }
                break;
            case '/orders':
                if($method === 'GET'){
                    $orderController->index();
                    return;
                }
                break;
            
            //------------------
            // Reviews
            //------------------
            case '/reviews':
                if($method === 'POST'){
                    $reviewController->create();
                    return;
                }
                break;
            
            //------------------
            // Favorites
            //------------------
            case '/favorites/toggle':
                if($method === 'POST'){
                    $favoriteController->toggle();
                    return;
                }
                break;
            case '/favorites':
                if($method === 'GET'){
                    $favoriteController->index();
                    return;
                }
                break;
            
            //------------------
            // Profile
            //------------------
            case '/profile':
                if($method === 'GET'){
                    $profileController->edit();
                    return;
                }
                if($method === 'POST'){
                    $profileController->update();
                    return;
                }
                break;
            case '/profile/password':
                if($method === 'POST'){
                    $profileController->changePassword();
                    return;
                }
                break;
            case '/profile/avatar':
                if($method === 'POST'){
                    $profileController->uploadAvatar();
                    return;
                }
                break;
            
            //------------------
            // Notifications
            //------------------
            case '/notifications':
                if($method === 'GET'){
                    $notificationController->index();
                    return;
                }
                break;
            case '/notifications/unread-count':
                if($method === 'GET'){
                    $notificationController->unreadCount();
                    return;
                }
                break;
            case '/notifications/read':
                if($method === 'POST'){
                    $notificationController->markRead();
                    return;
                }
                break;
            
            //------------------
            // Messaging
            //------------------
            case '/messages/inbox':
                if($method === 'GET'){
                    $messageController->inbox();
                    return;
                }
                break;
            case '/messages/conversation/create':
                if($method === 'POST'){
                    $messageController->createConversation();
                    return;
                }
                break;
            case '/messages/read':
                if($method === 'POST'){
                    $messageController->markAsRead();
                    return;
                }
                break;
            //------------------
            // Admin
            //------------------
            case '/admin':
                if($method === 'GET'){
                    $adminController->dashboard();
                    return;
                }
                break;
            case '/admin/users':
                if($method === 'GET'){
                    $adminController->users();
                    return;
                }
                break;
            case '/admin/users/role':
                if($method === 'POST'){
                    $adminController->updateUserRole();
                    return;
                }
                break;
            case '/admin/users/status':
                if($method === 'POST'){
                    $adminController->setUserStatus();
                    return;
                }
                break;
            case '/admin/users/delete':
                if($method === 'POST'){
                    $adminController->deleteUser();
                    return;
                }
                break;
            case '/admin/listings':
                if($method === 'GET'){
                    $adminController->listings();
                    return;
                }
                break;
            case '/admin/listings/delete':
                if($method === 'POST'){
                    $adminController->deleteListing();
                    return;
                }
                break;
            case '/admin/listings/feature':
                if($method === 'POST'){
                    $adminController->toggleFeatured();
                    return;
                }
                break;
            case '/admin/categories':
                if($method === 'GET'){
                    $adminController->categories();
                    return;
                }
                break;
            case '/admin/categories/create':
                if($method === 'POST'){
                    $adminController->createCategory();
                    return;
                }
                break;
            case '/admin/categories/update':
                if($method === 'POST'){
                    $adminController->updateCategory();
                    return;
                }
                break;
            case '/admin/categories/delete':
                if($method === 'POST'){
                    $adminController->deleteCategory();
                    return;
                }
                break;
            case '/admin/reviews':
                if($method === 'GET'){
                    $adminController->reviews();
                    return;
                }
                break;
            case '/admin/reviews/delete':
                if($method === 'POST'){
                    $adminController->deleteReview();
                    return;
                }
                break;
            case '/admin/logs':
                if($method === 'GET'){
                    $adminController->logs();
                    return;
                }
                break;
            case '/admin/file-log':
                if($method === 'GET'){
                    $adminController->fileLog();
                    return;
                }
                break;

            default:
                $userController->homeView();
                return;
        }
    }
}

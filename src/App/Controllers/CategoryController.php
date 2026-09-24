<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Http\Request;
use App\Http\Session;
use App\Services\CategoryService;
use Exception;
use InvalidArgumentException;

/**
 * Handles category creation requests.
 */
class CategoryController{
    public function __construct(
        private Request $request,
        private Session $session,
        private CategoryService $categoryService
    ){}

    public function createCategoryView(): void{
        include VIEW_PATH.'create_category.php';
    }

    public function createCategory(): void{

        $name = $this->request->post('categoryName','');
        $description = $this->request->post('categoryDescription','');

        if($name === '' || $description === ''){
            $this->session->setFlash('flash_message','Sva polja je neophodno popuniti.');
            header('Location: /create/category');
            exit;
        }

        try{
            $this->categoryService->create($name, $description);
            $this->session->setFlash('flash_message','Uspesno ste dodali kategoriju!');
        }
        catch(InvalidArgumentException $e){
            $this->session->setFlash('flash_message',$e->getMessage());
        }
        catch(Exception $e){
            $this->session->setFlash('flash_message','Doslo je do greske, pokusajte ponovo.');
        }

        header('Location: /create/category');
        exit;
    }
}
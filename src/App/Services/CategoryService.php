<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CategoryAlreadyExistsException;
use App\Exceptions\InvalidCategoryInputException;
use PDO;
use PDOException;

/**
 * Handles category creation, retrieval, updates, and deletion.
 */
class CategoryService{
    public function __construct(
        private PDO $pdo
    ){}
    public function create(string $name, string $description): int{

        $name = trim($name);
        $description = trim($description);

        if(mb_strlen($name) < 3){
            throw new InvalidCategoryInputException('Naziv kategorije je prekratak!');
        }
         if(mb_strlen($name) > 100){
            throw new InvalidCategoryInputException('Naziv kategorije je predugačak!');
        }
        if(mb_strlen($description) < 3){
            throw new InvalidCategoryInputException('Opis kategorije je prekratak!');
        }
        if(mb_strlen($description) > 1000){
            throw new InvalidCategoryInputException('Opis kategorije je predugačak!');
        }

        $stmt = $this->pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");

        try{
            $stmt->execute([
            ':name' => $name,
            ':description' => $description
        ]);
        }
        catch(PDOException $e){
            //23000 for integrity constraint violation (duplicate entry etc.)
            if($e->getCode() === '23000'){ 
                throw new CategoryAlreadyExistsException('Category already exists!');
            }
            throw $e;
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function getAll(): array{
        $stmt = $this->pdo->query('SELECT * FROM categories ORDER BY name ASC');
        return $stmt->fetchAll();
    }

    /**
     * Categories with a count of active, non-deleted listings (for the browse page).
     */
    public function getAllWithCounts(): array{
        $stmt = $this->pdo->query(
            "SELECT c.category_id, c.name, c.description,
                    (SELECT COUNT(*) FROM listings l
                     WHERE l.category_id = c.category_id
                       AND l.is_deleted = 0 AND l.status = 'active') AS listing_count
             FROM categories c
             ORDER BY c.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function getById(int $categoryId): ?array{
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE category_id = :id LIMIT 1');
        $stmt->execute([':id' => $categoryId]);
        $category = $stmt->fetch();
        return $category ?: null;
    }

    public function update(int $categoryId, string $name, string $description): void{
        $name = trim($name);
        $description = trim($description);

        if(mb_strlen($name) < 3 || mb_strlen($name) > 100){
            throw new InvalidCategoryInputException('Naziv kategorije mora imati 3-100 karaktera.');
        }
        if(mb_strlen($description) < 3 || mb_strlen($description) > 1000){
            throw new InvalidCategoryInputException('Opis kategorije mora imati 3-1000 karaktera.');
        }

        try{
            $stmt = $this->pdo->prepare(
                'UPDATE categories SET name = :name, description = :description WHERE category_id = :id'
            );
            $stmt->execute([':name' => $name, ':description' => $description, ':id' => $categoryId]);
        }
        catch(PDOException $e){
            if($e->getCode() === '23000'){
                throw new CategoryAlreadyExistsException('Kategorija sa tim nazivom već postoji!');
            }
            throw $e;
        }
    }

    public function delete(int $categoryId): void{
        // listings.category_id is ON DELETE SET NULL, so listings survive.
        $stmt = $this->pdo->prepare('DELETE FROM categories WHERE category_id = :id');
        $stmt->execute([':id' => $categoryId]);
    }
}
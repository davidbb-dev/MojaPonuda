<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Handles adding, removing, and retrieving user favorites.
 */
class FavoriteService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Toggle a listing in the user's favorites. Returns true if it is now favorited.
     */
    public function toggle(int $userId, int $listingId): bool
    {
        if ($this->isFavorited($userId, $listingId)) {
            $stmt = $this->pdo->prepare(
                "DELETE FROM favorites WHERE user_id = :u AND listing_id = :l"
            );
            $stmt->execute([':u' => $userId, ':l' => $listingId]);
            return false;
        }

        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO favorites (user_id, listing_id) VALUES (:u, :l)"
        );
        $stmt->execute([':u' => $userId, ':l' => $listingId]);
        return true;
    }

    public function isFavorited(int $userId, int $listingId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM favorites WHERE user_id = :u AND listing_id = :l LIMIT 1"
        );
        $stmt->execute([':u' => $userId, ':l' => $listingId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Returns the number of listings favorited by the user.
     */
    public function countForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :u");
        $stmt->execute([':u' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Returns listings favorited by the user, including their card display data.
     */
    public function getForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                l.listing_id,
                l.listing_type,
                l.name,
                l.current_price,
                l.status,
                l.ended_at,
                li.image_path
             FROM favorites f
             JOIN listings l ON l.listing_id = f.listing_id AND l.is_deleted = 0
             LEFT JOIN listings_images li ON li.listing_id = l.listing_id AND li.image_position = 0
             WHERE f.user_id = :u
             ORDER BY f.created_at DESC"
        );
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll();
    }
}

<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use PDO;

/**
 * Handles review eligibility, creation, seller ratings,
 * and retrieval of seller reviews.
 */
class ReviewService
{
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * Determines whether a buyer is allowed to review a completed purchase.
     */
    public function canReview(int $userId, int $listingId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT buyer_id, status FROM listings WHERE listing_id = :l LIMIT 1"
        );
        $stmt->execute([':l' => $listingId]);
        $listing = $stmt->fetch();

        if (!$listing) {
            return false;
        }
        if ((int)$listing['buyer_id'] !== $userId || $listing['status'] !== 'sold') {
            return false;
        }

        return !$this->hasReviewed($userId, $listingId);
    }

    public function hasReviewed(int $userId, int $listingId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM reviews WHERE listing_id = :l AND reviewer_id = :u LIMIT 1"
        );
        $stmt->execute([':l' => $listingId, ':u' => $userId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Creates a review after verifying that the buyer is eligible
     * to review the purchased listing and has not already reviewed it.
     */
    public function create(int $reviewerId, int $listingId, int $rating, string $comment): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Ocena mora biti između 1 i 5.');
        }
        $comment = trim($comment);
        if (mb_strlen($comment) > 1000) {
            throw new Exception('Komentar je predugačak.');
        }

        if (!$this->canReview($reviewerId, $listingId)) {
            throw new Exception('Ne možete oceniti ovaj oglas.');
        }

        $stmt = $this->pdo->prepare("SELECT user_id FROM listings WHERE listing_id = :l");
        $stmt->execute([':l' => $listingId]);
        $sellerId = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "INSERT INTO reviews (listing_id, reviewer_id, seller_id, rating, comment)
             VALUES (:l, :r, :s, :rating, :comment)"
        );
        $stmt->execute([
            ':l' => $listingId,
            ':r' => $reviewerId,
            ':s' => $sellerId,
            ':rating' => $rating,
            ':comment' => $comment !== '' ? $comment : null,
        ]);
    }

    /**
     * Returns the seller's average rating and total number of reviews.
     */
    public function getSellerRating(int $sellerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total
             FROM reviews WHERE seller_id = :s"
        );
        $stmt->execute([':s' => $sellerId]);
        $row = $stmt->fetch();

        return [
            'avg' => (float)($row['avg_rating'] ?? 0),
            'count' => (int)($row['total'] ?? 0),
        ];
    }

    /**
     * Returns recent reviews for a seller.
     */
    public function getForSeller(int $sellerId, int $limit = 50): array
    {
        $limit = max(1, min($limit, 1000));

        $stmt = $this->pdo->prepare(
            "SELECT r.rating, r.comment, r.created_at,
                    u.username AS reviewer_name,
                    l.name AS listing_name, l.listing_id
             FROM reviews r
             JOIN users u ON u.user_id = r.reviewer_id
             JOIN listings l ON l.listing_id = r.listing_id
             WHERE r.seller_id = :s
             ORDER BY r.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':s', $sellerId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

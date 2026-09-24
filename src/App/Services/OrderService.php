<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use PDO;

/**
 * Handles order creation and retrieval, Buy Now purchases,
 * and finalization of completed auctions.
 */
class OrderService
{
    public function __construct(
        private PDO $pdo,
        private ListingService $listingService,
        private NotificationService $notifications
    ) {}

    /**
     * Immediate "Kupi odmah" purchase of a fixed-price listing.
     */
    public function buyNow(int $buyerId, int $listingId): array
    {
        if ($buyerId <= 0 || $listingId <= 0) {
            throw new Exception('Nevažeći podaci.');
        }

        try {
            $this->pdo->beginTransaction();

            // Lock the listing so concurrent purchases cannot buy it twice.
            $stmt = $this->pdo->prepare(
                "SELECT listing_id, name, listing_type, status,
                        user_id, current_price, ended_at, buyer_id
                 FROM listings
                 WHERE listing_id = :listing_id
                 FOR UPDATE"
            );
            $stmt->execute([':listing_id' => $listingId]);
            $listing = $stmt->fetch();

            if (!$listing) {
                $this->pdo->rollBack();
                throw new Exception('Oglas nije pronađen.');
            }

            if ($listing['listing_type'] !== 'fixed_price') {
                $this->pdo->rollBack();
                throw new Exception('Ovaj oglas nije za trenutnu kupovinu.');
            }

            if ((int)$listing['user_id'] === $buyerId) {
                $this->pdo->rollBack();
                throw new Exception('Ne možete kupiti sopstveni predmet.');
            }

            if (
                $listing['status'] !== 'active' ||
                $listing['buyer_id'] !== null
            ) {
                $this->pdo->rollBack();
                throw new Exception('Oglas trenutno nije dostupan za kupovinu.');
            }

            $sellerId = (int)$listing['user_id'];
            $price = (float)$listing['current_price'];

            /*
             * The expiry check is performed by MySQL using UTC time.
             * This avoids comparing the database timestamp with PHP's
             * potentially differently configured timezone.
             */
            $stmt = $this->pdo->prepare(
                "UPDATE listings
                 SET buyer_id = :buyer_id,
                     status = 'sold'
                 WHERE listing_id = :listing_id
                   AND buyer_id IS NULL
                   AND status = 'active'
                   AND ended_at > UTC_TIMESTAMP()"
            );
            $stmt->execute([
                ':buyer_id' => $buyerId,
                ':listing_id' => $listingId,
            ]);

            if ($stmt->rowCount() !== 1) {
                $this->pdo->rollBack();
                throw new Exception('Kupovina nije uspela. Oglas je možda upravo istekao ili je prodat.');
            }

            // Keep the listing update and order creation in the same transaction.
            $orderId = $this->insertOrder(
                $listingId,
                $buyerId,
                $sellerId,
                $price
            );

            $this->pdo->commit();

            $name = $listing['name'];

            try {
                $this->notifications->notify(
                    $sellerId,
                    'sale',
                    'Vaš oglas je prodat! 🎉',
                    "Predmet \"$name\" je upravo kupljen za " . (int)$price . " din.",
                    "/view/listing/$listingId"
                );

                $this->notifications->notify(
                    $buyerId,
                    'purchase',
                    'Uspešna kupovina',
                    "Kupili ste \"$name\" za " . (int)$price . " din. Kontaktirajte prodavca radi preuzimanja.",
                    "/orders"
                );
            } catch (\Throwable $e) {
                error_log('buyNow notification error: ' . $e->getMessage());
            }

            return [
                'order_id' => $orderId,
                'listing_id' => $listingId
            ];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Assign winners and create orders for auctions that have ended with bids.
     * Safe to call from read paths because only not-yet-finalized auctions are processed.
     */
    public function finalizeEndedAuctions(): int
    {
        $this->listingService->expireDueListings();

        $candidates = $this->pdo->query(
            "SELECT l.listing_id
             FROM listings l
             WHERE l.listing_type = 'auction'
               AND l.status = 'expired'
               AND l.buyer_id IS NULL
               AND l.is_deleted = 0
               AND EXISTS (
                   SELECT 1
                   FROM bids b
                   WHERE b.listing_id = l.listing_id
               )
             LIMIT 50"
        )->fetchAll(PDO::FETCH_COLUMN);

        $finalized = 0;

        foreach ($candidates as $listingId) {
            if ($this->finalizeOne((int)$listingId)) {
                $finalized++;
            }
        }

        return $finalized;
    }

    private function finalizeOne(int $listingId): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Lock the listing so only one finalization can assign a winner.
            $stmt = $this->pdo->prepare(
                "SELECT listing_id, name, status, buyer_id, user_id
                 FROM listings
                 WHERE listing_id = :listing_id
                 FOR UPDATE"
            );
            $stmt->execute([':listing_id' => $listingId]);
            $listing = $stmt->fetch();

            if (
                !$listing ||
                $listing['buyer_id'] !== null ||
                $listing['status'] !== 'expired'
            ) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare(
                "SELECT user_id, price
                 FROM bids
                 WHERE listing_id = :listing_id
                 ORDER BY price DESC, bid_date ASC
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':listing_id' => $listingId]);
            $winner = $stmt->fetch();

            if (!$winner) {
                $this->pdo->rollBack();
                return false;
            }

            $winnerId = (int)$winner['user_id'];
            $price = (float)$winner['price'];
            $sellerId = (int)$listing['user_id'];

            $stmt = $this->pdo->prepare(
                "UPDATE listings
                 SET buyer_id = :buyer_id,
                     status = 'sold'
                 WHERE listing_id = :listing_id
                   AND buyer_id IS NULL"
            );
            $stmt->execute([
                ':buyer_id' => $winnerId,
                ':listing_id' => $listingId
            ]);

            if ($stmt->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            $orderId = $this->insertOrder(
                $listingId,
                $winnerId,
                $sellerId,
                $price
            );

            $this->pdo->commit();

            $name = $listing['name'];

            
            $this->notifications->notify(
                $winnerId,
                'auction_won',
                'Pobedili ste na aukciji! 🏆',
                "Osvojili ste \"$name\" sa ponudom od " . (int)$price . " din.",
                "/view/listing/$listingId"
            );

            $this->notifications->notify(
                $sellerId,
                'sale',
                'Vaša aukcija je završena',
                "Aukcija \"$name\" je prodata za " . (int)$price . " din.",
                "/view/listing/$listingId"
            );       

            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('finalizeOne error: ' . $e->getMessage());

            return false;
        }
    }

    private function insertOrder(
        int $listingId,
        int $buyerId,
        int $sellerId,
        float $price
    ): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO orders (
                listing_id,
                buyer_id,
                seller_id,
                price,
                status
             )
             VALUES (
                :listing_id,
                :buyer_id,
                :seller_id,
                :price,
                'completed'
             )"
        );

        $stmt->execute([
            ':listing_id' => $listingId,
            ':buyer_id' => $buyerId,
            ':seller_id' => $sellerId,
            ':price' => $price,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Retrieve purchases made by the specified buyer.
     */
    public function getPurchases(int $buyerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.order_id, o.listing_id, o.price, o.status, o.created_at,
                    l.name AS listing_name, l.listing_type,
                    u.username AS seller_name, u.user_id AS seller_id,
                    li.image_path,
                    (
                        SELECT COUNT(*)
                        FROM reviews r
                        WHERE r.listing_id = o.listing_id
                          AND r.reviewer_id = o.buyer_id
                    ) AS reviewed
             FROM orders o
             JOIN listings l ON l.listing_id = o.listing_id
             JOIN users u ON u.user_id = o.seller_id
             LEFT JOIN listings_images li
                    ON li.listing_id = l.listing_id
                   AND li.image_position = 0
             WHERE o.buyer_id = :buyer_id
             ORDER BY o.created_at DESC"
        );

        $stmt->execute([':buyer_id' => $buyerId]);

        return $stmt->fetchAll();
    }

    /**
     * Retrieve sales made by the specified seller.
     */
    public function getSales(int $sellerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT o.order_id, o.listing_id, o.price, o.status, o.created_at,
                    l.name AS listing_name, l.listing_type,
                    u.username AS buyer_name, u.user_id AS buyer_id,
                    li.image_path
             FROM orders o
             JOIN listings l ON l.listing_id = o.listing_id
             JOIN users u ON u.user_id = o.buyer_id
             LEFT JOIN listings_images li
                    ON li.listing_id = l.listing_id
                   AND li.image_position = 0
             WHERE o.seller_id = :seller_id
             ORDER BY o.created_at DESC"
        );

        $stmt->execute([':seller_id' => $sellerId]);

        return $stmt->fetchAll();
    }
}
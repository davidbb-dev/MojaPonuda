<?php
declare(strict_types=1);

namespace App\Services;

use App\Enums\DeletedFilterEnum;
use InvalidArgumentException;
use PDO;

/**
 * Handles administrative user, listing, category, review, and log operations.
 */
class AdminService
{
    public function __construct(
        private PDO $pdo
    ) {}

    public function getStats(): array
    {
        $q = fn(string $sql) => (int)$this->pdo->query($sql)->fetchColumn();

        return [
            'users'        => $q("SELECT COUNT(*) FROM users WHERE is_deleted = 0"),
            'blocked'      => $q("SELECT COUNT(*) FROM users WHERE is_deleted = 0 AND status = 'blocked'"),
            'listings'     => $q("SELECT COUNT(*) FROM listings WHERE is_deleted = 0"),
            'active'       => $q("SELECT COUNT(*) FROM listings WHERE is_deleted = 0 AND status = 'active'"),
            'sold'         => $q("SELECT COUNT(*) FROM listings WHERE is_deleted = 0 AND status = 'sold'"),
            'auctions'     => $q("SELECT COUNT(*) FROM listings WHERE is_deleted = 0 AND listing_type = 'auction' AND status = 'active'"),
            'bids'         => $q("SELECT COUNT(*) FROM bids"),
            'orders'       => $q("SELECT COUNT(*) FROM orders"),
            'categories'   => $q("SELECT COUNT(*) FROM categories"),
            'reviews'      => $q("SELECT COUNT(*) FROM reviews"),
            'revenue'      => (float)$this->pdo->query("SELECT COALESCE(SUM(price),0) FROM orders WHERE status = 'completed'")->fetchColumn(),
        ];
    }

    public function getUsers(string $search = '', int $limit = 50, int $offset = 0, DeletedFilterEnum $deletedFilter = DeletedFilterEnum::ONLY_ACTIVE): array
    {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE (u.username LIKE :s1 OR u.email LIKE :s2 OR u.name LIKE :s3 OR u.lastname LIKE :s4)";
            $like = '%' . $search . '%';
            $params = [':s1' => $like, ':s2' => $like, ':s3' => $like, ':s4' => $like];
        }

        switch($deletedFilter){
            case DeletedFilterEnum::ONLY_ACTIVE:
                $where .= ($where === '' ? 'WHERE ' : ' AND ')."u.is_deleted = 0";
                break;
            case DeletedFilterEnum::ONLY_DELETED:
                $where .= ($where === '' ? 'WHERE ' : ' AND ')."u.is_deleted = 1";
                break;
            case DeletedFilterEnum::ALL:
                break;
        }

        $sql = "SELECT u.user_id, u.name, u.lastname, u.username, u.email, u.role, u.status, u.created_at,
                       (SELECT COUNT(*) FROM listings l WHERE l.user_id = u.user_id AND l.is_deleted = 0) AS listing_count
                FROM users u
                $where
                ORDER BY u.created_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function setUserRole(int $userId, string $role): void
    {
        if (!in_array($role, ['user', 'admin', 'superadmin'], true)) {
            return;
        }
        $stmt = $this->pdo->prepare("UPDATE users SET role = :r WHERE user_id = :user_id AND is_deleted = 0");
        $stmt->execute([':r' => $role, ':user_id' => $userId]);
    }

    public function setUserStatus(int $userId, string $status): void
    {
        if (!in_array($status, ['active', 'blocked'], true)) {
            throw new InvalidArgumentException("Invalid status.");
        }
        $stmt = $this->pdo->prepare("UPDATE users SET status = :s WHERE user_id = :user_id AND is_deleted = 0");
        $stmt->execute([':s' => $status, ':user_id' => $userId]);
    }

    public function softDeleteUser(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE users
            SET is_deleted = 1
            WHERE user_id = :user_id
                AND is_deleted = 0
            LIMIT 1"
        );
        $stmt->execute([':user_id' => $userId]);
    }

    public function getRole(int $userId): ?string
    {
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE user_id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $role = $stmt->fetchColumn();
        return $role === false ? null : (string)$role;
    }

    public function getListings(string $search = '', string $status = '', int $limit = 50, int $offset = 0): array
    {
        $conditions = ['l.is_deleted = 0'];
        $params = [];
        if ($search !== '') {
            $conditions[] = '(l.name LIKE :s1 OR u.username LIKE :s2)';
            $like = '%' . $search . '%';
            $params[':s1'] = $like;
            $params[':s2'] = $like;
        }
        if ($status !== '' && in_array($status, ['active', 'paused', 'draft', 'sold', 'expired'], true)) {
            $conditions[] = 'l.status = :st';
            $params[':st'] = $status;
        }
        $where = 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT l.listing_id, l.name, l.current_price, l.status, l.listing_type,
                       l.is_featured, l.created_at, u.username AS owner, u.user_id AS owner_id
                FROM listings l
                JOIN users u ON u.user_id = l.user_id
                $where
                ORDER BY l.created_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function softDeleteListing(int $listingId): void
    {
        $stmt = $this->pdo->prepare("UPDATE listings SET is_deleted = 1 WHERE listing_id = :id");
        $stmt->execute([':id' => $listingId]);
    }

    public function toggleFeatured(int $listingId): bool 
    {
        $stmt = $this->pdo->prepare("UPDATE listings SET is_featured = 1 - is_featured WHERE listing_id = :id"); 
        $stmt->execute([':id' => $listingId]);
        $stmt = $this->pdo->prepare("SELECT is_featured FROM listings WHERE listing_id = :id");
        $stmt->execute([':id' => $listingId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getRecentLogs(int $limit = 100): array 
    {
        $stmt = $this->pdo->prepare(
            "SELECT la.log_id, la.action, la.ip_address, la.created_at, u.username
             FROM log_activities la
             LEFT JOIN users u ON u.user_id = la.user_id
             ORDER BY la.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT); 
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getReviews(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.review_id, r.rating, r.comment, r.created_at,
                    rev.username AS reviewer, sel.username AS seller, l.name AS listing_name
             FROM reviews r
             JOIN users rev ON rev.user_id = r.reviewer_id
             JOIN users sel ON sel.user_id = r.seller_id
             JOIN listings l ON l.listing_id = r.listing_id
             ORDER BY r.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function deleteReview(int $reviewId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM reviews WHERE review_id = :id");
        $stmt->execute([':id' => $reviewId]);
    }
}

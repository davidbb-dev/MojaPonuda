<?php
declare(strict_types=1);

namespace App\Services;

use App\Enums\DeletedFilterEnum;
use App\Enums\ListingStatusEnum;
use App\Enums\ListingTypeEnum;
use App\Exceptions\InvalidEnumValueException;
use App\Exceptions\InvalidListingInputException;
use App\Exceptions\InvalidUserInputException;
use App\Exceptions\UserHasBidsException;
use DateTime;
use DateTimeZone;
use Exception;
use PDO;

/**
 * Handles listing business logic, validation, persistence,
 * filtering, status transitions, and auction finalization.
 */
class ListingService{
    public function __construct(
        private PDO $pdo
    ){}

    //------------------
    // CREATE
    //------------------
    public function create(
        string $name, 
        string $description, 
        string $starting_price,
        DateTime $startedAt,
        DateTime $endedAt,
        string $status, 
        string $listingType, 
        int $userId, 
        int $categoryId): int
    {

        $name = trim($name);
        if(mb_strlen($name)>100){
            throw new InvalidUserInputException('Ime je predugacko!');
        }
         if(mb_strlen($name)<2){
            throw new InvalidUserInputException('Ime je prekratko!');
        }

        $description = trim($description);
        if(mb_strlen($description)>1000){
            throw new InvalidUserInputException('Opis je predugacak!');
        }
        if(mb_strlen($description)<3){
            throw new InvalidUserInputException('Opis je prekratak!');
        }
        
        if(!is_numeric($starting_price) || $starting_price <= 0){
            throw new InvalidUserInputException('Pogresno uneta cena!');
        }

        $status = ListingStatusEnum::tryFrom($status);
        $listingType = ListingTypeEnum::tryFrom($listingType);
        if($status === null || $listingType === null){
            throw new InvalidEnumValueException('Pogresan status ili tip aukcije/oglasa!');
        }
        
        $startedAtNoSeconds = clone $startedAt;
        $startedAtNoSeconds->setTime((int)$startedAtNoSeconds->format('H'), (int)$startedAtNoSeconds->format('i'), 0);

        $nowNoSeconds = new DateTime('now', new DateTimeZone('UTC'));
        $nowNoSeconds->setTime((int)$nowNoSeconds->format('H'), (int)$nowNoSeconds->format('i'), 0);


        if($startedAtNoSeconds < $nowNoSeconds){
            throw new InvalidUserInputException('Datum pocetka aukcije ili oglasa ne sme biti u proslosti!');
        }

        $minEnd = (clone $startedAt)->modify('+1 day');
        if($endedAt < $minEnd){
            throw new InvalidUserInputException('Trajanje aukcije ili oglasa ne sme biti krace od jednog dana!');
        }

        $maxEndAuction = (clone $startedAt)->modify('+14 days');
        $maxEndFixedPrice = (clone $startedAt)->modify('+60 days');
        if($listingType === ListingTypeEnum::AUCTION){
            if($endedAt > $maxEndAuction){
                throw new InvalidUserInputException('Aukcija ne moze trajati duze od tacno 14 dana!');
            }
        }
        else if($listingType === ListingTypeEnum::FIXED_PRICE){
            if($endedAt > $maxEndFixedPrice){
                throw new InvalidUserInputException('Prodaja po fiksnoj ceni ne moze trajati duze od 60 dana!');
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO listings (name, description, starting_price, current_price, started_at, ended_at, status, listing_type, user_id, category_id) 
        VALUES (:name, :description, :starting_price, :current_price, :started_at, :ended_at, :status, :listing_type, :user_id, :category_id)");
        
        $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':starting_price' => $starting_price,
        ':current_price' => $starting_price,
        ':started_at' => $startedAt->format('Y-m-d H:i:s'),
        ':ended_at' => $endedAt->format('Y-m-d H:i:s'),
        ':status' => $status->value,
        ':listing_type' => $listingType->value,
        ':user_id' => $userId,
        ':category_id' => $categoryId
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    //------------------
    // EDIT
    //------------------
    public function edit(
        string $name, 
        string $description, 
        string $starting_price,
        DateTime $startedAt,
        DateTime $endedAt,
        string $status, 
        string $listingType, 
        int $userId, 
        int $categoryId,
        int $listingId
        ): int{

        $name = trim($name);
        if(mb_strlen($name)>100){
            throw new InvalidListingInputException('Ime je predugacko!');
        }
         if(mb_strlen($name)<2){
            throw new InvalidListingInputException('Ime je prekratko!');
        }

        $description = trim($description);
        if(mb_strlen($description)>1000){
            throw new InvalidListingInputException('Opis je predugacak!');
        }
        if(mb_strlen($description)<3){
            throw new InvalidListingInputException('Opis je prekratak!');
        }

        if(!is_numeric($starting_price) || $starting_price <= 0){
            throw new InvalidListingInputException('Pogresno uneta cena!');
        }

        $status = ListingStatusEnum::tryFrom($status);
        if($status === null){
            throw new InvalidEnumValueException('Pogresan status aukcije/oglasa!');
        }

        $listingType = ListingTypeEnum::tryFrom($listingType);
        if($listingType === null){
            throw new InvalidEnumValueException('Pogresan tip aukcije/oglasa!');
        }
        
        $startedAtNoSeconds = clone $startedAt;
        $startedAtNoSeconds->setTime((int)$startedAtNoSeconds->format('H'), (int)$startedAtNoSeconds->format('i'), 0);

        $nowNoSeconds = new DateTime('now', new DateTimeZone('UTC'));
        $nowNoSeconds->setTime((int)$nowNoSeconds->format('H'), (int)$nowNoSeconds->format('i'), 0);


        if($startedAtNoSeconds < $nowNoSeconds){
            throw new InvalidListingInputException('Datum pocetka aukcije ili oglasa ne sme biti u proslosti!');
        }

        $minEnd = (clone $startedAt)->modify('+1 day');
        if($endedAt < $minEnd){
            throw new InvalidListingInputException('Trajanje aukcije ili oglasa ne sme biti krace od jednog dana!');
        }

        $maxEndAuction = (clone $startedAt)->modify('+14 days');
        $maxEndFixedPrice = (clone $startedAt)->modify('+60 days');
        if($listingType === ListingTypeEnum::AUCTION){
            if($endedAt > $maxEndAuction){
                throw new InvalidListingInputException('Aukcija ne moze trajati duze od tacno 14 dana!');
            }
        }
        else if($listingType === ListingTypeEnum::FIXED_PRICE){
            if($endedAt > $maxEndFixedPrice){
                throw new InvalidListingInputException('Prodaja po fiksnoj ceni ne moze trajati duze od 60 dana!');
            }
        }

        $stmt = $this->pdo->prepare("
        UPDATE listings 
        SET
            name = :name,
            description = :description,
            starting_price = :starting_price,
            current_price = :current_price,
            started_at = :started_at,
            ended_at = :ended_at,
            status = :status,
            listing_type = :listing_type,
            category_id = :category_id
        WHERE listing_id = :listing_id
            AND user_id = :user_id
            AND is_deleted = 0
        ");

        $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':starting_price' => $starting_price,
        ':current_price' => $starting_price,
        ':started_at' => $startedAt->format('Y-m-d H:i:s'),
        ':ended_at' => $endedAt->format('Y-m-d H:i:s'),
        ':status' => $status->value,
        ':listing_type' => $listingType->value,
        ':category_id' => $categoryId,
        ':listing_id' => $listingId,
        ':user_id' => $userId
        ]);

        if($stmt->rowCount() === 0){
            throw new InvalidListingInputException('Listing not found or not owned by user');
        }

        return $listingId;
    }

    public function softDeleteListing(int $userId, int $listingId): bool{
        if($userId <= 0 || $listingId <= 0){
            return false;
        }

        $message = 'Ne možete obrisati aukciju jer već postoje licitacije.';

        $this->assertNoBids($listingId, $message);

        $stmt = $this->pdo->prepare(
            "UPDATE listings 
            SET is_deleted = 1
            WHERE listing_id = :listing_id
                AND user_id = :user_id
                AND is_deleted = 0
            LIMIT 1"
        );
        
        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getUserListings(int $userId, DeletedFilterEnum $deletedFilter = DeletedFilterEnum::ONLY_ACTIVE): array{

        $this->expireDueListings();

        $query = "SELECT 
        l.listing_id,
        l.name, 
        l.description, 
        l.starting_price, 
        l.current_price, 
        l.created_at, 
        l.ended_at_actual, 
        l.started_at, 
        l.ended_at, 
        l.status, 
        l.listing_type, 
        l.category_id,
        c.name AS category_name
        FROM listings l
        LEFT JOIN categories c ON l.category_id = c.category_id
        WHERE l.user_id = :user_id";

        switch($deletedFilter){
            case DeletedFilterEnum::ONLY_ACTIVE:
                $query .= " AND l.is_deleted = 0 ";
                break;
            case DeletedFilterEnum::ONLY_DELETED:
                $query .= " AND l.is_deleted = 1 ";
                break;
            case DeletedFilterEnum::ALL:
                break;
        }

        $query .= " ORDER BY l.started_at DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getUserListingsForView(int $userId, bool $includeDeleted = false): array{
        $query = "SELECT 
        l.listing_id,
        l.listing_type,
        l.name,
        LEFT(l.description, 40) AS description,
        l.current_price,
        l.status,
        l.ended_at,
        li.image_path
        FROM listings l 
        LEFT JOIN listings_images li ON li.listing_id = l.listing_id AND li.image_position = 0
        WHERE l.user_id = :user_id";

        if(!$includeDeleted){
            $query .= " AND l.is_deleted = 0 ";
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function getListings(
        ?ListingTypeEnum $listingTypeEnum = null,
        ?ListingStatusEnum $listingStatusEnum = null, 
        ?int $categoryId = null, 
        int $limit = 20, 
        int $offset = 0,
        string $sortByColumn = 'created_at',
        string $direction = 'DESC',
        DeletedFilterEnum $deletedFilter = DeletedFilterEnum::ONLY_ACTIVE):array
    {

        $this->expireDueListings();

        $allowedSorts = [
            'created_at',
            'current_price',
            'ended_at'
        ];

        // Column names and sort directions cannot be bound as parameters,
        // so restrict them to known-safe values before inserting them into SQL.
        if(!in_array($sortByColumn, $allowedSorts, true)){
            $sortByColumn = 'created_at';
        }

        $direction = strtoupper($direction);

        if(!in_array($direction, ['ASC', 'DESC'], true)){
            $direction = 'DESC';
        }

        $conditions = [];
        $params = [];


        if($listingTypeEnum !== null){
            $conditions[] = 'l.listing_type = :listingType';
            $params[':listingType'] = $listingTypeEnum->value;
        }
        
        if($listingStatusEnum !== null){
            $conditions[] = 'l.status = :listingStatus';
            $params[':listingStatus'] = $listingStatusEnum->value;
        }

        if($categoryId !== null){
            $conditions[] = 'l.category_id = :categoryId';
            $params[':categoryId'] = $categoryId;
        }

        switch($deletedFilter){
            case DeletedFilterEnum::ONLY_ACTIVE:
                $conditions[] = 'l.is_deleted = 0';
                break;
            case DeletedFilterEnum::ONLY_DELETED:
                $conditions[] = 'l.is_deleted = 1';
                break;
            case DeletedFilterEnum::ALL:
                break;
        }

        $whereClause = '';

        if(!empty($conditions)){
            $whereClause = 'WHERE '.implode(' AND ',$conditions);
        }

        $query = "SELECT 
            l.listing_id,
            l.listing_type,
            l.name,
            l.ended_at,
            LEFT(l.description, 40) AS description,
            l.current_price,
            li.image_path 
            FROM listings l 
            LEFT JOIN listings_images li 
                ON li.listing_id = l.listing_id 
                AND li.image_position = 0 
            $whereClause 
            ORDER BY $sortByColumn  $direction
            LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($query);
        
        foreach($params as $key => $value){
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }
    
    /**
     * Lightweight update of editable fields only. Does NOT touch the auction
     * timer or listing type, editing a description must never reset an auction.
     */
    public function updateBasics(
        int $userId,
        int $listingId,
        string $name,
        string $description,
        string $startingPrice,
        int $categoryId,
        ?string $status = null
    ): void {
        $name = trim($name);
        $description = trim($description);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            throw new InvalidListingInputException('Naziv mora imati 2-100 karaktera!');
        }
        if (mb_strlen($description) < 3 || mb_strlen($description) > 1000) {
            throw new InvalidListingInputException('Opis mora imati 3-1000 karaktera!');
        }
        if (!is_numeric($startingPrice) || (float)$startingPrice <= 0) {
            throw new InvalidListingInputException('Pogrešno uneta cena!');
        }

        $hasBids = $this->hasBids($listingId);

        // Load the current type so we know whether current_price tracks starting_price.
        $stmt = $this->pdo->prepare("SELECT listing_type, status FROM listings WHERE listing_id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $listingId, ':uid' => $userId]);
        $current = $stmt->fetch();
        if (!$current) {
            throw new Exception('Oglas nije pronađen ili nije vaš.');
        }

        // current_price follows starting_price unless an auction already has bids.
        $syncCurrent = ($current['listing_type'] === 'fixed_price' || !$hasBids);

        // Only change status when there are no bids (mirrors pause/activate rules).
        $applyStatus = $status !== null
            && in_array($status, ['active', 'paused', 'draft'], true)
            && !$hasBids;

        $sql = "UPDATE listings SET name = :name, description = :description,
                    starting_price = :starting_price, category_id = :category_id";
        if ($syncCurrent) {
            $sql .= ", current_price = :current_price";
        }
        if ($applyStatus) {
            $sql .= ", status = :status";
        }
        $sql .= " WHERE listing_id = :listing_id AND user_id = :user_id";

        $params = [
            ':name' => $name,
            ':description' => $description,
            ':starting_price' => $startingPrice,
            ':category_id' => $categoryId,
            ':listing_id' => $listingId,
            ':user_id' => $userId,
        ];
        if ($syncCurrent) {
            $params[':current_price'] = $startingPrice;
        }
        if ($applyStatus) {
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Search / filter listings (public catalogue + /search page).
     * Returns listing cards. $total is filled by reference for pagination.
     */
    public function searchListings(
        string $q = '',
        ?int $categoryId = null,
        ?string $type = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        string $sort = 'newest',
        int $limit = 12,
        int $offset = 0,
        ?int &$total = null
    ): array {
        $this->expireDueListings();

        $conditions = ["l.is_deleted = 0", "l.status = 'active'"];
        $params = [];

        if ($q !== '') {
            // Distinct placeholders: named params can't be reused when
            // PDO::ATTR_EMULATE_PREPARES is false.
            $conditions[] = "(l.name LIKE :qname OR l.description LIKE :qdesc)";
            $params[':qname'] = '%' . $q . '%';
            $params[':qdesc'] = '%' . $q . '%';
        }
        if ($categoryId !== null) {
            $conditions[] = "l.category_id = :cat";
            $params[':cat'] = $categoryId;
        }
        if ($type !== null && in_array($type, ['auction', 'fixed_price'], true)) {
            $conditions[] = "l.listing_type = :type";
            $params[':type'] = $type;
        }
        if ($minPrice !== null) {
            $conditions[] = "l.current_price >= :minp";
            $params[':minp'] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions[] = "l.current_price <= :maxp";
            $params[':maxp'] = $maxPrice;
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $orderMap = [
            'newest'      => 'l.created_at DESC',
            'oldest'      => 'l.created_at ASC',
            'price_asc'   => 'l.current_price ASC',
            'price_desc'  => 'l.current_price DESC',
            'ending_soon' => 'l.ended_at ASC',
        ];
        $orderBy = $orderMap[$sort] ?? $orderMap['newest'];

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM listings l $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT l.listing_id, l.listing_type, l.name,
                       LEFT(l.description, 60) AS description,
                       l.current_price, l.ended_at, l.is_featured,
                       li.image_path
                FROM listings l
                LEFT JOIN listings_images li ON li.listing_id = l.listing_id AND li.image_position = 0
                $where
                ORDER BY l.is_featured DESC, $orderBy
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Fetch listing cards for a set of IDs, preserving the given order
     * (used for the cookie-based "recently viewed" strip).
     */
    public function getListingsByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT l.listing_id, l.listing_type, l.name, l.current_price, l.ended_at, li.image_path
             FROM listings l
             LEFT JOIN listings_images li ON li.listing_id = l.listing_id AND li.image_position = 0
             WHERE l.listing_id IN ($placeholders) AND l.is_deleted = 0 AND l.status = 'active'"
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();

        // Re-order to match the requested (cookie) order.
        $byId = [];
        foreach ($rows as $r) {
            $byId[(int)$r['listing_id']] = $r;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    public function getPublicListingsByUser(int $userId, int $limit = 24): array
    {
        $this->expireDueListings();
        $stmt = $this->pdo->prepare(
            "SELECT l.listing_id, l.listing_type, l.name, l.current_price, l.ended_at,
                    li.image_path
             FROM listings l
             LEFT JOIN listings_images li ON li.listing_id = l.listing_id AND li.image_position = 0
             WHERE l.user_id = :uid AND l.is_deleted = 0 AND l.status = 'active'
             ORDER BY l.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getFeatured(int $limit = 4): array
    {
        $this->expireDueListings();
        $stmt = $this->pdo->prepare(
            "SELECT l.listing_id, l.listing_type, l.name, l.current_price, l.ended_at,
                    li.image_path
             FROM listings l
             LEFT JOIN listings_images li ON li.listing_id = l.listing_id AND li.image_position = 0
             WHERE l.is_deleted = 0 AND l.status = 'active' AND l.is_featured = 1
             ORDER BY l.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function incrementViews(int $listingId): void
    {
        $stmt = $this->pdo->prepare("UPDATE listings SET views = views + 1 WHERE listing_id = :id");
        $stmt->execute([':id' => $listingId]);
    }

    public function getBidHistory(int $listingId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.price, b.bid_date, u.username
             FROM bids b
             JOIN users u ON u.user_id = b.user_id
             WHERE b.listing_id = :l
             ORDER BY b.price DESC, b.bid_date ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':l', $listingId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markListingAsExpired(int $userId, int $listingId): bool{
        if($userId <= 0 || $listingId <= 0){
            return false;
        }

        $query = "UPDATE listings 
            SET ended_at_actual = NOW(), 
            status = 'expired'
            WHERE listing_id = :listing_id 
                AND user_id = :user_id
                AND status IN ('active', 'paused')
                AND ended_at <= NOW()";

        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => $userId
            ]);

        return $stmt->rowCount() > 0;
    }
    
    //------------------
    //HELPERS
    //------------------
    public function descriptiveStatus(string $listing_status): string{
        $listing_status_descriptive = '';
        switch($listing_status){
            case 'active':
                $listing_status_descriptive = 'U toku';
                break;
            case 'paused':
                $listing_status_descriptive = 'Pauziran';
                break;
            case 'draft':
                $listing_status_descriptive = 'U pripremi';
                break;
            case 'sold':
                $listing_status_descriptive = 'Prodat';
                break;
            case 'expired':
                $listing_status_descriptive = 'Istekao';
                break;
            default:
                $listing_status_descriptive = 'Nepoznat status';
                break;
        }
        return $listing_status_descriptive;
    }

    public function getMappedUserListingsForView(int $userId, bool $includeDeleted = false): array{
        $allListings = $this->getUserListingsForView($userId, $includeDeleted);

        return array_map(fn($listing)=>[
            'listing_id' => $listing['listing_id'],
            'listing_type' => $listing['listing_type'],
            'name' => $listing['name'],
            'description' => $listing['description'],
            'current_price' => $listing['current_price'],
            'image_path' => $listing['image_path'],
            'status' => $listing['status'] ?? 'active',
            'ended_at' => $listing['ended_at'] ?? null
        ], $allListings);
    }

    public function getListingById(int $listingId): ?array{

        $this->expireDueListings();

        $query = "
        SELECT
            l.listing_id,
            l.user_id, 
            l.name, 
            l.description, 
            l.starting_price, 
            l.current_price, 
            l.started_at, 
            l.ended_at, 
            l.status, 
            l.listing_type, 
            l.category_id,
            li.image_id,
            li.image_path,
            li.image_position
        FROM listings l
        LEFT JOIN listings_images li ON l.listing_id = li.listing_id
        WHERE l.listing_id = :listing_id
        ORDER BY image_position ASC
        ";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'listing_id' => $listingId
        ]);

        $result = $stmt->fetchAll();

        if(!$result){
            return null;
        }

        $listing = [
            'listing_id' => $result[0]['listing_id'],
            'user_id' => $result[0]['user_id'],
            'name' => $result[0]['name'],
            'description' => $result[0]['description'],
            'starting_price' => $result[0]['starting_price'],
            'current_price' => $result[0]['current_price'],
            'started_at' => $result[0]['started_at'],
            'ended_at' => $result[0]['ended_at'],
            'status' => $result[0]['status'],
            'listing_type' => $result[0]['listing_type'],
            'category_id' => $result[0]['category_id'],
            'images' => []
        ];

        foreach($result as $row){
            if($row['image_path']){
                $listing['images'][] = [
                    'image_id' => $row['image_id'],
                    'image_path' => $row['image_path'],
                    'image_position' => $row['image_position']
                ];
            }
        }


        return $listing;
    }

    /**
     * Converts a form action into the corresponding listing status.
     */
    public function resolveStatusFromAction(string $action){

        switch($action){
            case 'publish':
                $status = 'active';
                break;
            case 'save_draft':
                $status = 'draft';
                break;
            case 'pause':
                $status = 'paused';
                break;
            default:
                throw new Exception('Neispravna akcija');
        }

        return $status;
    } 

    private function hasBids(int $listingId): bool{
        $stmt = $this->pdo->prepare(
            "SELECT 1 
            FROM bids
            WHERE listing_id = :listing_id
            LIMIT 1"
        );

        $stmt->execute([
            'listing_id' => $listingId
        ]);

        return $stmt->fetchColumn() !== false;
    }

    private function assertNoBids(int $listingId, string $message): void{
        if($this->hasBids($listingId)){
            throw new UserHasBidsException($message);
        }
    }

    public function pauseListing(int $userId, int $listingId): bool{
        if($userId <= 0 || $listingId <= 0){
            return false;
        }
        
        $message = 'Ne možete pauzirati aukciju jer već postoje licitacije.';

        $this->assertNoBids($listingId, $message);

        $stmt = $this->pdo->prepare(
            "UPDATE listings 
            SET status = 'paused' 
            WHERE listing_id = :listing_id 
            AND user_id = :user_id 
            AND status IN ('active', 'draft')");
        
        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

     public function activateListing(int $userId, int $listingId): bool{
        if($userId <= 0 || $listingId <= 0){
            return false;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE listings 
            SET status = 'active' 
            WHERE listing_id = :listing_id 
            AND user_id = :user_id 
            AND status = 'paused'");
        
        $stmt->execute([
            ':listing_id' => $listingId,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function expireDueListings(): int{

        $stmt = $this->pdo->prepare("UPDATE listings
            SET status = 'expired', 
            ended_at_actual = NOW()
            WHERE status IN ('active', 'paused')
            AND ended_at <= NOW()");
        
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function setBuyerId(int $listingId, int $buyerId, bool $isBuyNow = false): bool{
    if ($listingId <= 0 || $buyerId <= 0) {
        return false;
    }

    try {
        $this->pdo->beginTransaction();

        // Lock the listing so concurrent Buy Now/winner-finalization requests
        // cannot assign different buyers to the same listing.
        $stmt = $this->pdo->prepare("
            SELECT listing_id, status, ended_at, buyer_id
            FROM listings
            WHERE listing_id = :listingId
            FOR UPDATE
        ");

        $stmt->execute([
            ':listingId' => $listingId
        ]);

        $listing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$listing) {
            $this->pdo->rollBack();
            return false;
        }

        // Already sold or assigned
        if ($listing['buyer_id'] !== null || $listing['status'] === 'sold') {
            $this->pdo->rollBack();
            return false;
        }

        // Buy Now: verify that the listing is still active and not expired,
        // then assign the buyer while the listing row remains locked.
        if ($isBuyNow) {

            if ($listing['status'] !== 'active' || strtotime($listing['ended_at']) <= time()) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare("
                UPDATE listings
                SET buyer_id = :buyerId,
                    status = 'sold'
                WHERE listing_id = :listingId
                AND buyer_id IS NULL
            ");

            $stmt->execute([
                ':buyerId' => $buyerId,
                ':listingId' => $listingId
            ]);

            if ($stmt->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        }

        // Select the highest bid while the listing lock is held,
        // ensuring the winner is chosen consistently with concurrent operations.
        $stmt = $this->pdo->prepare("
            SELECT user_id
            FROM bids
            WHERE listing_id = :listingId
            ORDER BY price DESC, bid_date ASC
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->execute([
            ':listingId' => $listingId
        ]);

        $winner = $stmt->fetch();

        if (!$winner) {
            $this->pdo->rollBack();
            return false;
        }

        // Final update is conditional on the listing still being unassigned,
        // active/paused, and already past its end time.
        $stmt = $this->pdo->prepare("
            UPDATE listings
            SET buyer_id = :winnerId,
                status = 'sold'
            WHERE listing_id = :listingId
            AND buyer_id IS NULL
            AND status IN ('active', 'paused')
            AND ended_at <= NOW()
        ");

        $stmt->execute([
            ':winnerId' => $winner['user_id'],
            ':listingId' => $listingId
        ]);

        if ($stmt->rowCount() !== 1) {
            $this->pdo->rollBack();
            return false;
        }

        $this->pdo->commit();
        return true;

    } catch (\Throwable $e) {
        if($this->pdo->inTransaction()){
            $this->pdo->rollBack();
        }
        
        throw $e;
    }
    }

}
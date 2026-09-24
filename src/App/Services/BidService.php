<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Exception;
use App\Services\NotificationService;

/**
 * Handles auction bidding operations and enforces
 * the business rules required to place a valid bid.
 */
class BidService{
    public function __construct(
        private PDO $pdo,
        private ListingService $listingService,
        private NotificationService $notifications
    ){}

    /**
     * Returns the number of bids and current highest bid
     * for a listing.
     */
    public function getBidsData(int $listingId): ?array{
        $query = 
            "SELECT 
                COUNT(bid_id) AS bid_count,
                MAX(price) AS highest_bid
            FROM bids
            WHERE listing_id = :listing_id";
        
        $stmt = $this->pdo->prepare($query);

        $stmt->execute([
            'listing_id' => $listingId
        ]);

        return $stmt->fetch();
    }

    /**
     * Validates and places a bid, updates the listing price,
     * and sends notifications to affected users.
     */
    public function placeBid(int $userId, int $listingId, float $price): array
    {
        if($listingId <= 0){
            throw new Exception('Nepravilan id oglasa.');
        }
        
        if($price <= 0){
            throw new Exception("Neispravna cena licitacije.");
        }

        $listing = $this->listingService->getListingById($listingId);

        if(!$listing){
            throw new Exception("Oglas nije pronađen.");
        }
        
        if((int)$listing['user_id'] === $userId){
            throw new Exception('Ne možete licitirati na sopstvenim aukcijama.');
        }

        if($listing['listing_type'] !== 'auction'){
            throw new Exception("Nije aukcija.");
        }

        if($listing['status'] !== 'active'){
            throw new Exception("Aukcija nije više aktivna.");
        }

        if(strtotime($listing['ended_at']) < time()){
            throw new Exception("Završena aukcija.");
        }

        $stmt = $this->pdo->prepare("
            SELECT MAX(price) AS highest_bid
            FROM bids
            WHERE listing_id = :listing_id
        ");

        $stmt->execute(['listing_id' => $listingId]);
        $highest = $stmt->fetch();

        $currentHighest = (float)($highest['highest_bid'] ?? $listing['current_price']);

        $minIncrement = AUCTION_MIN_BID_INCREMENT;

        if($price < $currentHighest + $minIncrement){
            throw new Exception("Licitacija je ispod minimuma.");
        }

        // Identify the current highest bidder so they can be notified if outbid.
        $stmt = $this->pdo->prepare("
            SELECT user_id FROM bids
            WHERE listing_id = :listing_id
            ORDER BY price DESC, bid_date ASC
            LIMIT 1
        ");
        $stmt->execute(['listing_id' => $listingId]);
        $previousLeader = $stmt->fetchColumn();
        $previousLeader = $previousLeader !== false ? (int)$previousLeader : null;

        $stmt = $this->pdo->prepare("
            INSERT INTO bids (user_id, listing_id, price, bid_date)
            VALUES (:user_id, :listing_id, :price, NOW())
        ");


        $stmt->execute([
            'price' => $price,
            'user_id' => $userId,
            'listing_id' => $listingId
        ]);

        $stmt = $this->pdo->prepare("
            UPDATE listings
            SET current_price = :price
            WHERE listing_id = :listing_id
        ");

        $stmt->execute([
            'price' => $price,
            'listing_id' => $listingId
        ]);

        $bidData = $this->getBidsData($listingId);

        // Notify the seller and the previous highest bidder.
        $listingName = $listing['name'];
        $sellerId = (int)$listing['user_id'];

        $this->notifications->notify(
            $sellerId,
            'new_bid',
            'Nova ponuda na vašem oglasu',
            "Stigla je ponuda od " . (int)$price . " din na \"$listingName\".",
            "/view/listing/$listingId"
        );

        if ($previousLeader !== null && $previousLeader !== $userId) {
            $this->notifications->notify(
                $previousLeader,
                'outbid',
                'Nadmašeni ste!',
                "Neko je dao veću ponudu na \"$listingName\". Nova cena je " . (int)$price . " din.",
                "/view/listing/$listingId"
            );
        }

        return[
            'listing_id' => $listingId,
            'new_price' => $price,
            'min_increment' => AUCTION_MIN_BID_INCREMENT,
            'bid_count' => (int)($bidData['bid_count'] ?? 0)
        ];
    }
}
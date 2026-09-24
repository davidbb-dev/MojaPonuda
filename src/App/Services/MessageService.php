<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use PDO;

/**
 * Handles conversations and messages, including access control,
 * message retrieval, sending, and read status.
 */
class MessageService{
    public function __construct(
        private PDO $pdo
    ){}

    /**
     * Verifies that a user is a participant in the specified conversation.
     */
    private function assertConversationAccess(int $conversationId, int $userId): void{
        
        $stmt = $this->pdo->prepare(
            "SELECT buyer_id, seller_id
            FROM conversations
            WHERE conversation_id = :conversation_id"
        );

        $stmt->execute([
            ':conversation_id' => $conversationId
        ]);

        $conversation = $stmt->fetch();

        if(!$conversation){
            throw new Exception('Conversation not found.');
        }

        if(
            (int)$conversation['buyer_id'] !== $userId &&
            (int)$conversation['seller_id'] !== $userId
        ){
            throw new Exception('You are not part of this conversation.');
        }
    }

    /**
     * Creates a conversation for a listing and buyer if one does not already exist.
     */
    public function create(int $listingId, int $buyerId): int{ 

        $stmt = $this->pdo->prepare("
        SELECT conversation_id
        FROM conversations
        WHERE listing_id = :listing_id AND buyer_id = :buyer_id");
        
        $stmt->execute([
            ':listing_id' => $listingId,
            ':buyer_id' => $buyerId
        ]);

        $existingId = $stmt->fetchColumn();

        if($existingId !== false){
            return (int)$existingId;
        }

        $stmt = $this->pdo->prepare(
            "SELECT user_id 
            FROM listings 
            WHERE listing_id = :listing_id 
            LIMIT 1"
        );

        $stmt->execute([
            ':listing_id' => $listingId
        ]);

        $sellerId = $stmt->fetchColumn();

        if(!$sellerId){
            throw new Exception('Listing not found.');
        }

        if((int)$sellerId === $buyerId){
            throw new Exception('You cannot message yourself.');
        }

        $stmt = $this->pdo->prepare("INSERT INTO conversations (listing_id, buyer_id, seller_id)
            VALUES (:listing_id, :buyer_id, :seller_id)");

        $stmt->execute([
            ':listing_id' => $listingId,
            ':buyer_id' => $buyerId,
            ':seller_id' => $sellerId
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Sends a message in a conversation after verifying user access and message content.
     */
    public function sendMessage(
        int $conversationId,
        int $senderId,
        string $message
    ): int{

        $this->assertConversationAccess($conversationId, $senderId);

        $message = trim($message);

        if($message === ''){
            throw new Exception('Message cannot be empty.');
        }

        if(mb_strlen($message) > 5000){
            throw new Exception('Message too long.');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO messages (
                conversation_id, 
                sender_user_id, 
                message
            )
            VALUES (
                :conversation_id, 
                :sender_user_id, 
                :message
            )"
        );

        $stmt->execute([
            ':conversation_id' => $conversationId,
            ':sender_user_id' => $senderId,
            ':message' => $message
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Retrieves messages from a conversation with pagination.
     */
    public function getMessages(
        int $conversationId,
        int $userId,
        int $limit = 50,
        int $offset = 0
    ): array{
        
        $limit = max(1, min($limit, 100));
        $offset = max(0, $offset);

        $this->assertConversationAccess($conversationId, $userId);

        $stmt = $this->pdo->prepare(
            "SELECT message_id, conversation_id, sender_user_id, message, created_at 
            FROM messages
            WHERE conversation_id = :conversation_id
            ORDER BY created_at ASC, message_id ASC
            LIMIT :limit OFFSET :offset"
        );

        $stmt->bindValue(':conversation_id', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }

     /**
     * Retrieves all conversations involving the user, including the latest message
     * and the number of unread messages in each conversation.
     */
    public function getInbox(int $userId): array{

        $stmt = $this->pdo->prepare(
            "SELECT
                c.conversation_id,
                c.listing_id,
                l.name AS listing_name,
                c.buyer_id,
                c.seller_id,
                m.message AS last_message,
                m.created_at AS last_message_time,
                m.sender_user_id AS last_sender,
                (
                    SELECT COUNT(*)
                    FROM messages m2
                    WHERE m2.conversation_id = c.conversation_id
                        AND m2.sender_user_id != ?
                        AND m2.created_at >
                            CASE
                                WHEN c.buyer_id = ?
                                THEN COALESCE(c.buyer_last_read_at, '1970-01-01')
                                ELSE COALESCE(c.seller_last_read_at, '1970-01-01')
                            END
                ) AS unread_count
            FROM conversations c

            JOIN listings l
                ON l.listing_id = c.listing_id

            LEFT JOIN messages m
                ON m.message_id = (
                    SELECT message_id
                    FROM messages
                    WHERE conversation_id = c.conversation_id
                    ORDER BY created_at DESC, message_id DESC
                    LIMIT 1
                )

            WHERE c.buyer_id = ?
            OR c.seller_id = ?

            ORDER BY m.created_at DESC, m.message_id DESC"
        );

        $stmt->execute([
            $userId,
            $userId,
            $userId,
            $userId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marks conversation as read for the specified user.
     */
    public function markAsRead(int $conversationId, int $userId): void{

        $stmt = $this->pdo->prepare(
            "SELECT buyer_id, seller_id
            FROM conversations
            WHERE conversation_id = :conversation_id"
        );

        $stmt->execute([
            ':conversation_id' => $conversationId
        ]);

        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$conversation){
            throw new Exception('Conversation not found.');
        }

        $userId = (int)$userId;

        if((int)$conversation['buyer_id'] === $userId){

            $stmt = $this->pdo->prepare(
                "UPDATE conversations
                SET buyer_last_read_at = NOW()
                WHERE conversation_id = :conversation_id"
            );

        }
        elseif((int)$conversation['seller_id'] === $userId){
            $stmt = $this->pdo->prepare(
                "UPDATE conversations
                SET seller_last_read_at = NOW()
                WHERE conversation_id = :conversation_id"
            );
        }
        else{
            throw new Exception('You are not part of this conversation.');
        }

        $stmt->execute([
            ':conversation_id' => $conversationId
        ]);
    }

    /**
     * Finds an existing conversation with specific listing and buyer.
     */
    public function findConversation(int $listingId, int $buyerId): ?int{
        $stmt = $this->pdo->prepare("
        SELECT conversation_id
        FROM conversations
        WHERE listing_id = :listing_id
            AND buyer_id = :buyer_id
        LIMIT 1"
        );

        $stmt->execute([
            ':listing_id' => $listingId,
            ':buyer_id' => $buyerId
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

}
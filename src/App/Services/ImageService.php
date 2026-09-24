<?php
declare(strict_types=1);

namespace App\Services;

use Exception;
use PDO;

/**
 * Handles image validation, storage, resizing, deletion, and ordering
 * for listings and user avatars.
 */
class ImageService{
     public function __construct(
        private PDO $pdo
    ){}

    // Images larger than this on the longest side get downscaled on upload.
    private const MAX_DIMENSION = 2200;

    /**
     * Validates uploaded listing images for type, size, and upload count.
     *
     * For existing listings, also ensures at least one image remains.
     */
    public function validateImages(?array $uploadedImages, int $listingId = 0): void{
        $allowedTypes = [
            IMAGETYPE_JPEG,
            IMAGETYPE_PNG, 
            IMAGETYPE_WEBP
        ];

        // 30MB per image (nginx/php allow up to 64MB per request)
        $maxSize = 30 * 1024 * 1024; 
        $maxCount = 15;

        $validIndexes = [];

        if(!$uploadedImages || !isset($uploadedImages['error'])){
            throw new Exception('Niste poslali nijednu sliku.');
        }

        foreach($uploadedImages['error'] as $index => $error){
            if($error === UPLOAD_ERR_NO_FILE){
                continue;
            }

            if($error !== UPLOAD_ERR_OK){
                throw new Exception('Došlo je do greške pri otpremanju slike.');
            }

            $validIndexes[] = $index;
        }

        $validFileCount = count($validIndexes);

        if($listingId === 0){
            if($validFileCount === 0){
                throw new Exception('Morate selektovati bar jednu sliku.');
            }
        }
        else{
            $numberImagesDB = $this->countImagesFromDB($listingId);

            if($validFileCount === 0 && $numberImagesDB <= 0){
                throw new Exception('Morate selektovati bar jednu sliku.');
            }   
        }
        
        if($validFileCount > $maxCount){
            throw new Exception('Mozete dodati maksimalno '.$maxCount.' slika.');
        }

        foreach($validIndexes as $index){
            $info = getimagesize($uploadedImages['tmp_name'][$index]);

            if($info === false || !in_array($info[2], $allowedTypes, true)){
                throw new Exception('Slika '.$uploadedImages['name'][$index].' nije podržanog formata.');
            }

            if($uploadedImages['size'][$index] > $maxSize){
                throw new Exception('Slika '.$uploadedImages['name'][$index].' je prevelika.');
            }
        }
    }

    /**
     * Stores uploaded listing images and creates their database records.
     *
     * @param int $positionOffset Starting position used when appending images.
     */
    public function uploadImagesForListing(int $userId, int $listingId, array $uploadedImages, int $positionOffset = 0): void{

        foreach($uploadedImages['tmp_name'] as $index => $tmpPath){
            if(($uploadedImages['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK){
                continue; // Skip empty or unsuccessful upload entries.
            }

            $info = getimagesize($tmpPath);

            if($info === false){
                throw new Exception('Neuspešno učitavanje slike '.$uploadedImages['name'][$index]);
            }

            $extMap = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp'
            ];

            $extension = $extMap[$info[2]] ?? null;

            if($extension === null){
                throw new Exception('Slika '.$uploadedImages['name'][$index].' nije podržanog formata.');
            }

            $filename = uniqid('listing_').'.'.$extension;

            $directoryPath = UPLOAD_IMAGES_PATH.$userId.'/'.$listingId.'/';
            
            if(!is_dir($directoryPath) && !mkdir($directoryPath, 0755, true) && !is_dir($directoryPath)){
                throw new Exception('Neuspelo kreiranje datoteke za korisnika.');
            }

            $destination = $directoryPath.$filename;
            $webPath = '/uploads/images/listings/'.$userId.'/'.$listingId.'/'.$filename;

            if(!$this->storeImage($tmpPath, $destination)){
                throw new Exception('Neuspesno ucitavanje slike '.$uploadedImages['name'][$index]);
            }

            $stmt = $this->pdo->prepare("INSERT INTO listings_images (listing_id, image_path, image_position) VALUES (:listing_id, :image_path, :image_position)");
            $stmt->execute([
                ':listing_id' => $listingId,
                ':image_path' => $webPath,
                ':image_position' => $positionOffset + $index
            ]);
        }
    }

    /**
     * Collapse positions to a contiguous 0..n-1 sequence, preserving the
     * current relative order. Guarantees an image at position 0 (the cover).
     */
    public function renumberPositions(int $listingId): void{
        $stmt = $this->pdo->prepare(
            "SELECT image_id FROM listings_images
             WHERE listing_id = :l
             ORDER BY image_position ASC, image_id ASC"
        );
        $stmt->execute([':l' => $listingId]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $update = $this->pdo->prepare("UPDATE listings_images SET image_position = :pos WHERE image_id = :id");
        foreach($ids as $pos => $imageId){
            $update->execute([':pos' => $pos, ':id' => (int)$imageId]);
        }
    }

    /**
     * Deletes listing image records and their corresponding files from disk.
     *
     * @param int[] $imageIds Image IDs authorized by the caller.
     */
    public function deleteImagesFromDB(array $imageIds): void
    {
        if(empty($imageIds)){
            return;
        }

        $imageIds = array_map('intval', $imageIds);

        $placeholders = implode(',', array_fill(0, count($imageIds), '?'));

        $queryFindPath = "SELECT image_path FROM listings_images WHERE image_id IN ($placeholders)";

        $stmtFindPath = $this->pdo->prepare($queryFindPath);
        $stmtFindPath->execute($imageIds);

        $images = $stmtFindPath->fetchAll();

        $queryDelete = "DELETE FROM listings_images WHERE image_id IN ($placeholders)";

        $stmtDelete = $this->pdo->prepare($queryDelete);
        $stmtDelete->execute($imageIds);

        foreach($images as $img){
            $imagePath = $img['image_path'];

            $fullPath = $_SERVER['DOCUMENT_ROOT'].$imagePath;
            
            if(!empty($imagePath) && file_exists($fullPath)){
                    unlink($fullPath);
            }
        }
        
    }

    public function countImagesFromDB(int $listingId): int{
        $query = "SELECT COUNT(*) FROM listings_images WHERE listing_id = :listingId";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['listingId' => $listingId]);
        
        return (int)$stmt->fetchColumn();
    }

    /**
     * Store an uploaded image, downscaling it with GD when it is very large.
     * Falls back to a plain move if GD isn't available or the file isn't a
     * processable raster image — so uploads never silently fail.
     */
    private function storeImage(string $tmpPath, string $destination): bool
    {
        if (!is_uploaded_file($tmpPath)) {
            return false;
        }

        if (function_exists('imagecreatetruecolor')) {
            $info = getimagesize($tmpPath);
            if ($info !== false) {
                [$width, $height] = $info;
                $max = self::MAX_DIMENSION;

                if ($width > $max || $height > $max) {
                    if ($this->resizeAndSave($tmpPath, $destination, $info[2], $width, $height, $max)) {
                        return true;
                    }
                }
            }
        }

        return move_uploaded_file($tmpPath, $destination);
    }

    private function resizeAndSave(string $src, string $dest, int $imageType, int $width, int $height, int $max): bool
    {
        $source = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
            IMAGETYPE_PNG  => @imagecreatefrompng($src),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
            default        => false,
        };
        if (!$source) {
            return false;
        }

        $scale = $max / max($width, $height);
        $newW = (int)round($width * $scale);
        $newH = (int)round($height * $scale);

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG/WEBP
        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);

        $ok = match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($dst, $dest, 85),
            IMAGETYPE_PNG  => imagepng($dst, $dest, 6),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dst, $dest, 85) : false,
            default        => false,
        };

        return $ok;
    }

    /**
     * Validate + store a user avatar. Returns the web path.
     */
    public function uploadAvatar(int $userId, array $file): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Greška pri otpremanju slike.');
        }

        $info = getimagesize($file['tmp_name']);
        $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];
        if ($info === false || !in_array($info[2], $allowed, true)) {
            throw new Exception('Avatar mora biti JPG, PNG ili WEBP slika.');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Avatar je prevelik (max 10MB).');
        }

        $extMap = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
        $extension = $extMap[$info[2]];

        $dir = dirname(UPLOAD_IMAGES_PATH) . '/avatars/' . $userId . '/';
        
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('Neuspelo kreiranje datoteke za avatar.');
        }

        $filename = uniqid('avatar_') . '.' . $extension;
        $destination = $dir . $filename;

        if (!$this->storeImage($file['tmp_name'], $destination)) {
            throw new Exception('Neuspešno čuvanje avatara.');
        }

        return '/uploads/images/avatars/' . $userId . '/' . $filename;
    }

    /**
     * Deletes an avatar file from disk if the path belongs to the avatar directory.
     */
    public function deleteAvatar(string $webPath): void
    {
        if($webPath === ''){
            return;
        }

        $avatarPrefix = '/uploads/images/avatars/';

        if(!str_starts_with($webPath, $avatarPrefix)){
            return;
        }

        $fullPath = $_SERVER['DOCUMENT_ROOT'].$webPath;

        if(is_file($fullPath)){
            unlink($fullPath);
        }
    }

    /**
     * Updates positions of existing listing images according to the submitted order.
     *
     * @param array[] $order Ordered image data containing image IDs and types.
     */
    public function updateImageOrder(array $order): void{

        foreach($order as $position => $img){
            if(($img['type'] ?? '') === 'existing' && isset($img['id'])){

                $stmt = $this->pdo->prepare("UPDATE listings_images SET image_position = :pos WHERE image_id = :id");

                $stmt->execute([
                    ':pos' => $position,
                    ':id' => (int)$img['id']
                ]);
            }
        }
                
            
        
    }
}
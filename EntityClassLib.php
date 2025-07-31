<?php

include_once 'functions.php';
class User
{
    private $userId;
    private $name;
    private $phone;
    private $password; // Add this property to store the hashed password

    // Constructor to initialize the User object
    public function __construct($userId, $name, $phone, $password = null)
    {
        $this->userId = $userId;
        $this->name = $name;
        $this->phone = $phone;
        $this->password = $password; // Store the hashed password
    }

    // Getter methods to retrieve user properties
    public function getUserId()
    {
        return $this->userId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    // Getter for the hashed password
    public function getPassword()
    {
        return $this->password;
    }

    public function fetchAllAlbums($accessibilityCode = null)
    {
        $pdo = getPDO();
        $sql = "SELECT * FROM album WHERE Owner_Id = ?";
        $params = [$this->userId];
        if ($accessibilityCode) {
            $sql .= " AND Accessibility_Code = ?";
            $params[] = $accessibilityCode;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $albums = [];
        foreach ($result as $row) {
            $albums[] = new Album($row['Title'], $row['Description'], $row['Accessibility_Code'], $row['Owner_Id'], $row['Album_Id']);
        }
        return $albums;
    }

    public function fetchAllFriends()
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM user WHERE UserId IN
                 (SELECT friendship.Friend_RequesteeId FROM friendship
                    WHERE Friend_RequesterId = :userId AND Status = 'accepted'
                 UNION
                SELECT friendship.Friend_RequesterId FROM friendship
                    WHERE Friend_RequesteeId = :userId AND Status = 'accepted');");
        $stmt->execute(['userId'=>$this->userId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $friends = [];
        foreach ($result as $row) {
            if ($row['UserId'] === $this->userId) {
                continue;
            }
            $friends[] = new User($row['UserId'], $row['Name'], $row['Phone']);
        }
        return $friends;
    }

    // Setter for name
    public function setName($name)
    {
        $this->name = $name;
    }

    // Setter for password
    public function setPassword($password)
    {
        $this->password = $password;
    }
}

class Album
{
    private $albumId;
    private $title;
    private $description;
    private $accessibilityCode;
    private $ownerId;

    public function __construct($title, $description, $accessibilityCode, $ownerId, $albumId = null)
    {
        $this->albumId = $albumId;
        $this->title = $title;
        $this->description = $description;
        $this->accessibilityCode = $accessibilityCode;
        $this->ownerId = $ownerId;
    }

    public function getAlbumId()
    {
        return $this->albumId;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAccessibilityCode()
    {
        return $this->accessibilityCode;
    }

    public function getOwnerId()
    {
        return $this->ownerId;
    }

    public function setAlbumId($albumId)
    {
        $this->albumId = $albumId;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setAccessibilityCode($accessibilityCode)
    {
        $this->accessibilityCode = $accessibilityCode;
    }

    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }


    public function create()
    {
        $pdo = getPDO();
        $sql = "INSERT INTO Album (Title, Description, Accessibility_Code, Owner_Id) 
                VALUES (:title, :description, :accessibility, :owner_id)";
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([
                'title' => $this->title,
                'description' => $this->description,
                'accessibility' => $this->accessibilityCode,
                'owner_id' => $this->ownerId
            ]);
            $this->albumId = $pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Album creation failed.");
        }
    }


    public static function read($albumId)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM album WHERE Album_Id = ?");
        $stmt->execute([$albumId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return new Album($row['Title'], $row['Description'], $row['Accessibility_Code'], $row['Owner_Id'], $row['Album_Id']);
        } else {
            throw new Exception("Album not found.");
        }
    }


    public function update()
    {
        if ($this->albumId === null) {
            throw new Exception("Cannot update album without an Album_Id.");
        }

        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE album SET Title = ?, Description = ?, Accessibility_Code = ?, Owner_Id = ? WHERE Album_Id = ?");
        if (!$stmt->execute([$this->title, $this->description, $this->accessibilityCode, $this->ownerId, $this->albumId])) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error updating album: " . $errorInfo[2]);
        }
    }

    public static function delete($albumId)
    {
        $pdo = getPDO();
        $album = Album::read($albumId);
        $pictures = $album->fetchAllPictures();
        if (count($pictures) > 0) {
            foreach ($pictures as $picture) {
                Picture::delete($picture->getPictureId());
            }
        }
        $stmt = $pdo->prepare("DELETE FROM album WHERE Album_Id = ?");
        if (!$stmt->execute([$albumId])) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Error deleting album: " . $errorInfo[2]);
        }
        rmdir("uploads/album_$albumId/thumbnails");
        rmdir("uploads/album_$albumId");
    }


    public function fetchAllPictures()
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM picture WHERE Album_Id = ?");
        $stmt->execute([$this->albumId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pictures = [];
        foreach ($result as $row) {
            $pictures[] = new Picture($row['File_Name'], $row['Album_Id'], $row['Title'], $row['Description'], $row['Picture_Id']);
        }
        return $pictures;
    }
}


class Picture
{

    private $pictureId;
    private $albumId;
    private $fileName;
    private $title;
    private $description;

    public function __construct($fileName, $albumId, $title = null, $description = null, $pictureId = null)
    {
        $this->pictureId = $pictureId;
        $this->albumId = $albumId;
        $this->fileName = $fileName;
        $this->title = $title;
        $this->description = $description;
    }

    public function getPictureId()
    {
        return $this->pictureId;
    }

    public function getAlbumId()
    {
        return $this->albumId;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getFilePath()
    {
        return "uploads/album_{$this->albumId}/" . $this->fileName;
    }

    public function getThumbnailPath()
    {
        return "uploads/album_{$this->albumId}/thumbnails/thumbnail_" . $this->fileName;
    }

    public function setPictureId($pictureId)
    {
        $this->pictureId = $pictureId;
    }


    public function create()
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO picture (Album_Id, File_Name, Title, Description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$this->albumId, $this->fileName, $this->title, $this->description]);
        if ($stmt->rowCount() > 0) {
            $this->pictureId = $pdo->lastInsertId();
        } else {
            throw new Exception("Error creating picture: " . $stmt->errorInfo());
        }
    }

    public function saveToUploadFolder($tmpFilePath, $albumId)
    {
        $uploadDir = "./uploads/album_$albumId/";
        $thumbnailDir = "./uploads/album_$albumId/thumbnails/";

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0777, true);
        }

        $uniqueFileName = uniqid() . "_" . basename($this->fileName);
        $this->fileName = $uniqueFileName;
        $destination = $uploadDir . $uniqueFileName;

        if (!move_uploaded_file($tmpFilePath, $destination)) {
            throw new Exception("Failed to upload file.");
        }


        $thumbnailFileName = "thumbnail_" . $uniqueFileName;
        $thumbnailDestination = $thumbnailDir . $thumbnailFileName;
        $this->createThumbnail($destination, $thumbnailDestination);


        return $destination;
    }

    public function createThumbnail($originalImagePath, $thumbnailPath, $thumbWidth = 150)
    {
        list($width, $height, $type, $attr) = getimagesize($originalImagePath);
        $imgRatio = $width / $height;

        if ($imgRatio > 1) {
            $newWidth = $thumbWidth;
            $newHeight = $thumbWidth / $imgRatio;
        } else {
            $newHeight = $thumbWidth;
            $newWidth = $thumbWidth * $imgRatio;
        }

        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($originalImagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($originalImagePath);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($originalImagePath);
                break;
            default:
                throw new Exception("Unsupported image type.");
        }

        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumbnail, $thumbnailPath);
                break;
            case IMAGETYPE_PNG:
                imagepng($thumbnail, $thumbnailPath);
                break;
            case IMAGETYPE_GIF:
                imagegif($thumbnail, $thumbnailPath);
                break;
        }

        imagedestroy($thumbnail);
        imagedestroy($source);
    }


    public static function read($pictureId)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM picture WHERE Picture_Id = ?");
        $stmt->execute([$pictureId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $picture = new Picture($row['File_Name'], $row['Album_Id'], $row['Title'], $row['Description'], $row['Picture_Id']);
        } else {
            throw new Exception("Picture not found.");
        }

        return $picture;
    }


    public static function delete($pictureId)
    {
        $pdo = getPDO();
        $picture = Picture::read($pictureId);
        $filePath = $picture->getFilePath();
        $thumbnailPath = $picture->getThumbnailPath();
        $stmt = $pdo->prepare("DELETE FROM comment WHERE Picture_Id = ?");
        $stmt->execute([$pictureId]);
        $stmt = $pdo->prepare("DELETE FROM picture WHERE Picture_Id = ?");
        $stmt->execute([$pictureId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Error deleting picture: ");
        }

        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                throw new Exception("Error deleting picture file from the file system.");
            }
        }
        if (file_exists($thumbnailPath)) {
            if (!unlink($thumbnailPath)) {
                throw new Exception("Error deleting thumbnail file from the file system.");
            }
        }
    }

    public function addComment($authorId, $commentText)
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO Comment (Author_Id, Picture_Id, Comment_Text) VALUES (?, ?, ?)");
        if (!$stmt->execute([$authorId, $this->pictureId, $commentText])) {
            throw new Exception("Failed to add comment.");
        }
    }

    public function fetchComments()
    {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
        SELECT c.Comment_Id, c.Author_Id, c.Comment_Text, u.Name
        FROM Comment c
        JOIN User u ON c.Author_Id = u.UserId
        WHERE c.Picture_Id = ?
        order by c.Comment_Id DESC
    ");
        $stmt->execute([$this->pictureId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $comments;
    }
}

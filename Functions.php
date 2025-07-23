<?php
include_once 'EntityClassLib.php';

// Get PDO connection using Lab5.ini configuration
function getPDO() {
    $dbConnection = parse_ini_file("cst8257project.ini");
    extract($dbConnection);

    $pdo = new PDO($dsn, $scriptUser, $scriptPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    return $pdo;
}

function getUserByIdAndPassword($userId, $password) {
    $pdo = getPDO();
    $sql = "SELECT UserId, Name, Phone, Password FROM User WHERE UserId = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($password, $row['Password'])) {
        return new User($row['UserId'], $row['Name'], $row['Phone']);
    }
    return null;
}



function addNewUser($userId, $name, $phone, $password) {
    $pdo = getPDO();
    $sql = "INSERT INTO User (UserId, Name, Phone, Password) VALUES (:UserId, :name, :phone, :password)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'UserId' => $userId,
        'name' => $name,
        'phone' => $phone,
        'password' => $password
    ]);
}

function getUserById($userId) {
    $pdo = getPDO();
    $sql = "SELECT UserId FROM User WHERE UserId = :userId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':userId', $userId);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserAlbums($userId) {
    $pdo = getPDO();
    $sql = "SELECT a.Album_Id, a.Title, 
                   a.Description, a.Accessibility_Code, 
                   COUNT(p.Picture_Id) as PictureCount 
            FROM Album a 
            LEFT JOIN Picture p ON a.Album_Id = p.Album_Id 
            WHERE a.Owner_Id = :owner_id 
            GROUP BY a.Album_Id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':owner_id', $userId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAccessibilityOptions() {
    $pdo = getPDO();
    $sql = "SELECT Accessibility_Code, Description FROM Accessibility";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateAlbumAccessibility($albumId, $newAccessibility) {
    $pdo = getPDO();
    $sql = "UPDATE Album SET Accessibility_Code = :accessibility WHERE Album_Id = :album_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':accessibility' => $newAccessibility,
        ':album_id' => $albumId
    ]);
}

function deleteAlbum($albumId) {
    $pdo = getPDO();
    $sql = "DELETE FROM Album WHERE Album_Id = :album_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':album_id', $albumId);
    $stmt->execute();
}
?>

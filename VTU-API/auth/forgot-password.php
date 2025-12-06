<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/user.php';
use Binali\Config\Database;
use PDO;
use PDOException;
$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email)){
    $user->sEmail = $data->email;
    
    if($user->emailExists()){
        // Generate password reset token
        $reset_token = bin2hex(random_bytes(32));
        $user->reset_token = $reset_token;
        $user->reset_token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        if($user->updateResetToken()){
            // Here you would typically send an email with the reset link
            // For now, we'll just return the token
            http_response_code(200);
            echo json_encode(array(
                "message" => "Password reset initiated.",
                "reset_token" => $reset_token
            ));
        }
        else{
            http_response_code(503);
            echo json_encode(array("message" => "Unable to process reset request."));
        }
    }
    else{
        http_response_code(404);
        echo json_encode(array("message" => "Email not found."));
    }
}
else{
    http_response_code(400);
    echo json_encode(array("message" => "Unable to process request. Email is required."));
}
?>

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/user.php';
use Binali\Config\Database;
use Binali\Models\User;
use PDO;
use PDOException;
$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->reset_token) && !empty($data->new_password)){
    if($user->validateResetToken($data->reset_token)){
        $user->sPass = $data->new_password;
        
        if($user->resetPassword()){
            http_response_code(200);
            echo json_encode(array("message" => "Password was reset successfully."));
        }
        else{
            http_response_code(503);
            echo json_encode(array("message" => "Unable to reset password."));
        }
    }
    else{
        http_response_code(400);
        echo json_encode(array("message" => "Invalid or expired reset token."));
    }
}
else{
    http_response_code(400);
    echo json_encode(array("message" => "Unable to reset password. Data is incomplete."));
}
?>

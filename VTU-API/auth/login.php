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

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->email) && !empty($data->password)){
    $user->sEmail = $data->email;
    $user->sPass = $data->password;
    
    if($user->emailExists()){
        // Check if account is deleted
        if((int)$user->sRegStatus === 3) {
            http_response_code(401);
            echo json_encode(array("message" => "This account has been deleted. Please contact support if you wish to reactivate it."));
            exit();
        }
        
        if($user->validatePassword($data->password)){
            http_response_code(200);
            echo json_encode(array(
                "message" => "Login successful.",
                "id" => (string)$user->sId,
                "fullname" => $user->sFname . ' ' . $user->sLname,
                "email" => $user->sEmail,
                "phone" => $user->sPhone,
                "wallet" => $user->sWallet
            ));
        }
        else{
            http_response_code(401);
            echo json_encode(array("message" => "Invalid password."));
        }
    }
    else{
        http_response_code(404);
        echo json_encode(array("message" => "Email not found."));
    }
}
else{
    http_response_code(400);
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}
?>

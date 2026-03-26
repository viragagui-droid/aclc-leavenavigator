<?php
header("Content-Type: application/xml; charset=utf-8");
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$inputXML = file_get_contents('php://input');

/**
 * HELPER FUNCTION - DAPAT ISA LANG ITO SA BUONG FILE
 */
if (!function_exists('sendXMLResponse')) {
    function sendXMLResponse($status, $message, $data = null) {
        $xml = new SimpleXMLElement('<response/>');
        $xml->addChild('status', $status);
        $xml->addChild('message', $message);
        
        if ($data && is_array($data)) {
            $list = $xml->addChild('data_list');
            foreach ($data as $row) {
                $item = $list->addChild('item');
                foreach ($row as $key => $value) {
                    $item->addChild($key, htmlspecialchars($value ?? ''));
                }
            }
        }
        echo $xml->asXML();
        exit();
    }
}

switch($method) {
    case 'GET':
        // URI: api.php?action=users | approved | pending | rejected
        $action = $_GET['action'] ?? '';
        
        if ($action == 'users') {
            $query = "SELECT id, name, username, role, date_hired FROM users";
        } elseif (in_array($action, ['approved', 'pending', 'rejected'])) {
            $query = "SELECT r.*, u.name as employee_name 
                      FROM leave_requests r 
                      JOIN users u ON r.user_id = u.id 
                      WHERE r.status='$action'";
        } else {
            sendXMLResponse("error", "Invalid action. Use: users, approved, pending, or rejected");
        }

        $result = mysqli_query($conn, $query);
        $rows = [];
        while($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        sendXMLResponse("success", "Data retrieved", $rows);
        break;

    case 'POST':
        // CREATE STAFF (Logic from manage_staff.php)
        $xmlData = simplexml_load_string($inputXML);
        if (!$xmlData) sendXMLResponse("error", "Invalid XML Input");

        $name = mysqli_real_escape_string($conn, $xmlData->name);
        $username = mysqli_real_escape_string($conn, $xmlData->username);
        $password = password_hash($xmlData->password, PASSWORD_DEFAULT);
        $role = mysqli_real_escape_string($conn, $xmlData->role); 
        $date_hired = mysqli_real_escape_string($conn, $xmlData->date_hired);

        // Check for existing username
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            sendXMLResponse("error", "Username already exists");
        }

        $sql = "INSERT INTO users (name, username, password, role, date_hired, vacation_leave, sick_leave) 
                VALUES ('$name', '$username', '$password', '$role', '$date_hired', 0, 0)";
        
        if (mysqli_query($conn, $sql)) sendXMLResponse("success", "Staff created successfully");
        else sendXMLResponse("error", "Failed: " . mysqli_error($conn));
        break;

  case 'PATCH':
        $xmlData = simplexml_load_string($inputXML);
        
        if (!$xmlData) {
            sendXMLResponse("error", "Invalid XML Input.");
        }

        if (!isset($xmlData->user_id) || empty($xmlData->user_id)) {
            sendXMLResponse("error", "Missing user_id in XML request.");
        }

        $id = intval($xmlData->user_id);
        $updates = [];

        // Dynamic Update Logic - Iche-check kung anong tags ang sinend mo
        if (!empty($xmlData->name)) {
            $val = mysqli_real_escape_string($conn, (string)$xmlData->name);
            $updates[] = "name = '$val'";
        }
        if (!empty($xmlData->username)) {
            $val = mysqli_real_escape_string($conn, (string)$xmlData->username);
            $updates[] = "username = '$val'";
        }
        if (!empty($xmlData->role)) {
            $val = mysqli_real_escape_string($conn, (string)$xmlData->role);
            $updates[] = "role = '$val'";
        }
        if (!empty($xmlData->date_hired)) {
            $val = mysqli_real_escape_string($conn, (string)$xmlData->date_hired);
            $updates[] = "date_hired = '$val'";
        }
        if (!empty($xmlData->password)) {
            $hash = password_hash((string)$xmlData->password, PASSWORD_DEFAULT);
            $updates[] = "password = '$hash'";
        }

        if (empty($updates)) {
            sendXMLResponse("error", "No valid fields provided for update.");
        }

        // EXECUTE: I-update ang record basta hindi Admin ang target
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = $id AND role != 'admin'";
        
        if (mysqli_query($conn, $sql)) {
            if (mysqli_affected_rows($conn) > 0) {
                sendXMLResponse("success", "User ID $id has been updated successfully.");
            } else {
                sendXMLResponse("error", "No changes made. Either the ID is wrong, it's an Admin, or the data is already identical.");
            }
        } else {
            sendXMLResponse("error", "Database Error: " . mysqli_error($conn));
        }
        break;

    case 'DELETE':
        // DELETE USER (?id=5)
        $id = intval($_GET['id'] ?? 0);
        if ($id == 0) sendXMLResponse("error", "ID required");

        $sql = "DELETE FROM users WHERE id = $id AND role != 'admin'";
        if (mysqli_query($conn, $sql)) sendXMLResponse("success", "User deleted");
        else sendXMLResponse("error", "Delete failed");
        break;

    default:
        sendXMLResponse("error", "Method $method not allowed");
        break;
}
?>
<?php
// koneksi database
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "db_parking"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        // Get total users
        if ($action == 'get_total_users') {
            $result = $conn->query("SELECT COUNT(*) AS total FROM users");
            $data = $result->fetch_assoc();
            echo json_encode(['total' => $data['total']]);
        }

        // Get total accesses today
        if ($action == 'get_accesses_today') {
            $today = date('Y-m-d'); 
            $result = $conn->query("SELECT COUNT(*) AS total FROM log_akses WHERE DATE(waktu_akses) = '$today'");
            $data = $result->fetch_assoc();
            echo json_encode(['total' => $data['total']]);
        }

        // Get total vehicles inside the parking lot
        if ($action == 'get_vehicles_inside') {
            $result = $conn->query("SELECT COUNT(*) AS total FROM log_akses WHERE status = 'masuk' AND id NOT IN (SELECT id FROM log_akses WHERE status = 'keluar')");
            $data = $result->fetch_assoc();
            echo json_encode(['total' => $data['total']]);
        }

        // Get users
        if ($action == 'get_users') {
            $result = $conn->query("SELECT * FROM users");
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            echo json_encode($users);
        }

        // Get single user
        if ($action == 'get_single_user' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $result = $conn->query("SELECT * FROM users WHERE id = $id");
            $user = $result->fetch_assoc();
            
            echo json_encode($user);
        }

        // Get logs
        if ($action == 'get_logs') {
            $result = $conn->query("SELECT * FROM log_akses");
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            echo json_encode($logs);
        }

        // Get single user for editing
        if (isset($_GET['id']) && $action == 'get_user') {
            $id = $_GET['id'];
            $result = $conn->query("SELECT * FROM users WHERE id = $id");
            echo json_encode($result->fetch_assoc());
        }

        // Delete user
        if (isset($_GET['id']) && $action == 'delete_user') {
            $id = $_GET['id'];
            if ($conn->query("DELETE FROM users WHERE id = $id") === TRUE) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_user') {
        $rfid_code = $_POST['rfidCode'];
        $nama = $_POST['nama'];
        $waktu_terdaftar = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO users (rfid_code, nama, waktu_terdaftar) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $rfid_code, $nama, $waktu_terdaftar);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } 
    
    if ($_POST['action'] == 'update_user') {
        $id = intval($_POST['id']);
        $rfid_code = $_POST['rfidCode'];
        $nama = $_POST['nama'];

        $stmt = $conn->prepare("UPDATE users SET rfid_code = ?, nama = ? WHERE id = ?");
        $stmt->bind_param('ssi', $rfid_code, $nama, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
}

$conn->close();
?>

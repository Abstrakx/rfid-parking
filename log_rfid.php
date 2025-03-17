<?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "db_parking";

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (isset($_GET['rfid'])) {
        $rfid = $_GET['rfid'];
        
        // Cek apakah RFID terdaftar dan diizinkan masuk
        $sql = "SELECT * FROM users WHERE rfid_code='$rfid'";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            // Cek log terakhir pengguna
            $log_sql = "SELECT status FROM log_akses WHERE rfid_code='$rfid' ORDER BY waktu DESC LIMIT 1";
            $log_result = $conn->query($log_sql);
            
            if ($log_result->num_rows > 0) {
                $last_status = $log_result->fetch_assoc()['status'];
                $new_status = ($last_status == 'masuk') ? 'keluar' : 'masuk';
            } else {
                $new_status = 'masuk'; 
            }
            
            // Simpan ke log akses
            $conn->query("INSERT INTO log_akses (rfid_code, status, waktu) VALUES ('$rfid', '$new_status', NOW())");

            echo "allowed";  
        } else {
            echo "denied";
        }
    }

    $conn->close();
?>

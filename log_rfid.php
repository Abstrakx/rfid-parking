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
        $rfid = $conn->real_escape_string($_GET['rfid']); // Prevent SQL Injection

        // Cek apakah RFID terdaftar dan ambil nama
        $sql = "SELECT nama FROM users WHERE rfid_code='$rfid'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $nama = $row['nama']; 

            // Cek log terakhir pengguna
            $log_sql = "SELECT status FROM log_akses WHERE rfid_code='$rfid' ORDER BY waktu_akses DESC LIMIT 1";
            $log_result = $conn->query($log_sql);

            if ($log_result->num_rows > 0) {
                $last_status = $log_result->fetch_assoc()['status'];
                $new_status = ($last_status == 'masuk') ? 'keluar' : 'masuk';
            } else {
                $new_status = 'masuk';
            }

            // Simpan ke log akses dengan nama
            $insert_sql = "INSERT INTO log_akses (rfid_code, nama, status, waktu_akses) 
                           VALUES ('$rfid', '$nama', '$new_status', NOW())";
            $conn->query($insert_sql);

            echo "allowed";
        } else {
            echo "denied";
        }
    }

    $conn->close();
?>

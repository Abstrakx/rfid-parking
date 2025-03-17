<?php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "db_parking";

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Pastikan request dari ESP32 mengandung data nama dan rfid
    if (isset($_POST['nama']) && isset($_POST['rfid'])) {
        $nama = $_POST['nama'];
        $rfid = $_POST['rfid'];

        // Simpan data ke database
        $sql = "INSERT INTO users (nama, rfid_code) VALUES ('$nama', '$rfid')";

        if ($conn->query($sql) === TRUE) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "invalid_request";
    }

    $conn->close();
?>

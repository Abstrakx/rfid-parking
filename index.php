<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Tracking Parkir</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">Sistem Tracking Parkir</div>
        </div>
    </header>
    
    <div class="container">
        <div class="dashboard">
            <div class="card">
                <h3>Total Pengguna</h3>
                <div class="stat" id="totalPengguna">0</div>
                <p>Pengguna terdaftar</p>
            </div>
            <div class="card">
                <h3>Akses Hari Ini</h3>
                <div class="stat" id="aksesHariIni">0</div>
                <p>Kendaraan masuk/keluar</p>
            </div>
            <div class="card">
                <h3>Kendaraan di Dalam</h3>
                <div class="stat" id="kendaraanDiDalam">0</div>
                <p>Saat ini di area parkir</p>
            </div>
        </div>
        
        <div class="tables-container">
            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Data Pengguna RFID</h2>
                    <button class="btn btn-primary" id="btnTambahPengguna">Tambah Pengguna</button>
                </div>
                <table id="dataPengguna">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kode RFID</th>
                            <th>Nama</th>
                            <th>Waktu Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan dimuat menggunakan AJAX -->
                    </tbody>
                </table>
            </div>
            
            <div class="table-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Log Akses Terbaru</h2>
                    <button class="btn btn-success">Ekspor Data</button>
                </div>
                <table id="dataLog">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kode RFID</th>
                            <th>Nama Pengguna</th>
                            <th>Waktu Akses</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data log akses dimuat menggunakan AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah Pengguna -->
    <div id="modalTambahPengguna" class="modal">
        <div class="modal-content">
            <span class="close" id="closeTambahPengguna">&times;</span>
            <h2>Tambah Pengguna Baru</h2>
            <form id="formTambahPengguna">
                <div class="form-group">
                    <label for="rfidCode">Kode RFID:</label>
                    <input type="text" id="rfidCode" name="rfidCode" required>
                </div>
                <div class="form-group">
                    <label for="nama">Nama:</label>
                    <input type="text" id="nama" name="nama" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Edit Pengguna -->
    <div id="modalEditPengguna" class="modal">
        <div class="modal-content">
            <span class="close" id="closeEditPengguna">&times;</span>
            <h2>Edit Pengguna</h2>
            <form id="formEditPengguna">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editRfidCode">Kode RFID:</label>
                    <input type="text" id="editRfidCode" name="rfidCode" required>
                </div>
                <div class="form-group">
                    <label for="editNama">Nama:</label>
                    <input type="text" id="editNama" name="nama" required>
                </div>
                <div class="form-group">
                    <label for="editWaktuTerdaftar">Waktu Terdaftar:</label>
                    <input type="text" id="editWaktuTerdaftar" name="waktuTerdaftar" required disabled>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus Pengguna -->
    <div id="modalHapusPengguna" class="modal">
        <div class="modal-content">
            <span class="close" id="closeHapusPengguna">&times;</span>
            <h2>Hapus Pengguna</h2>
            <p class="form-group">Apakah Anda yakin ingin menghapus pengguna ini?</p>
            <p id="hapusId"></p>
            <div class="form-actions">
                <button type="button" class="btn btn-danger" id="confirmHapusPengguna">Hapus</button>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; 2025 Sistem Tracking Parkir. Hak Cipta Dilindungi.</p>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.1/xlsx.full.min.js"></script>
    <script>
        // AJAX call to load data
        function loadUsers() {
            fetch('backend.php?action=get_users')
                .then(response => response.json())
                .then(data => {
                    let tableBody = document.querySelector('#dataPengguna tbody');
                    tableBody.innerHTML = '';
                    data.forEach(user => {
                        let row = `<tr>
                            <td>${user.id}</td>
                            <td>${user.rfid_code}</td>
                            <td>${user.nama}</td>
                            <td>${user.waktu_terdaftar}</td>
                            <td class="action-buttons">
                                <button class="btn btn-primary btn-edit" onclick="editUser(${user.id})">Edit</button>
                                <button class="btn btn-danger btn-hapus" onclick="deleteUser(${user.id})">Hapus</button>
                            </td>
                        </tr>`;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                });
        }

        function loadLogs() {
            fetch('backend.php?action=get_logs')
                .then(response => response.json())
                .then(data => {
                    let tableBody = document.querySelector('#dataLog tbody');
                    tableBody.innerHTML = '';
                    data.forEach(log => {
                        let row = `<tr>
                            <td>${log.id}</td>
                            <td>${log.rfid_code}</td>
                            <td>${log.nama}</td>
                            <td>${log.waktu_akses}</td>
                            <td>${log.status}</td>
                        </tr>`;
                        tableBody.insertAdjacentHTML('beforeend', row);
                    });
                });
        }

        function loadTotalUsers() {
            fetch('backend.php?action=get_total_users')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalPengguna').innerText = data.total;
                });
        }

        // Function to fetch and display total accesses today
        function loadAccessesToday() {
            fetch('backend.php?action=get_accesses_today')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('aksesHariIni').innerText = data.total;
                });
        }

        // Function to fetch and display vehicles inside the parking lot
        function loadVehiclesInside() {
            fetch('backend.php?action=get_vehicles_inside')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('kendaraanDiDalam').innerText = data.total;
                });
        }


        // Function to add new user
        document.getElementById('formTambahPengguna').addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'add_user');  

            fetch('backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())  
            .then(text => {
                console.log("Server Response:", text);  
                return text ? JSON.parse(text) : {};  
            })
            .then(data => {
                if (data.success) {
                    loadUsers();
                    closeModal(document.getElementById('modalTambahPengguna'));
                    location.reload();
                } else {
                    console.error("Failed to add user:", data);
                }
            })
            .catch(error => console.error("Error:", error));
        });

        // Event listener for edit form submission
        document.getElementById('formEditPengguna').addEventListener('submit', function(e) {
            e.preventDefault(); 

            const formData = new FormData(this);
            formData.append('action', 'update_user'); 

            fetch('backend.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadUsers(); 
                    closeModal(document.getElementById('modalEditPengguna')); 
                    location.reload();
                } else {
                    console.error("Failed to edit user:", data);
                }
            })
            .catch(error => console.error("Error:", error));
        });

        // Function to edit a user
        function editUser(id) {
            fetch(`backend.php?action=get_user&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editRfidCode').value = data.rfid_code;
                    document.getElementById('editNama').value = data.nama;
                    document.getElementById('editWaktuTerdaftar').value = data.waktu_terdaftar;
                    showModal(document.getElementById('modalEditPengguna'));
                });
        }

        // Function to delete a user
        function deleteUser(id) {
            fetch(`backend.php?action=get_single_user&id=${id}`)
                .then(response => response.json())
                .then(user => {
                    if (user && user.nama) {
                        document.getElementById('hapusId').textContent = `Hapus Pengguna: ${user.nama}`;
                        showModal(document.getElementById('modalHapusPengguna'));

                        document.getElementById('confirmHapusPengguna').onclick = function() {
                            fetch(`backend.php?action=delete_user&id=${id}`, {
                                method: 'GET',
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    loadUsers(); 
                                    closeModal(document.getElementById('modalHapusPengguna'));
                                    location.reload();
                                }
                            });
                        };
                    }
                })
                .catch(error => console.error("Error fetching user data:", error));
        }


        // Open and close modals
        function showModal(modal) {
            modal.style.display = 'block';
        }

        function closeModal(modal) {
            modal.style.display = 'none';
        }

        // Event listener untuk tombol tambah pengguna
        document.getElementById('btnTambahPengguna').addEventListener('click', function() {
            showModal(document.getElementById('modalTambahPengguna'));
        });

        // Event listener untuk tombol close (x) pada modal tambah pengguna
        document.getElementById('closeTambahPengguna').addEventListener('click', function() {
            closeModal(document.getElementById('modalTambahPengguna'));
        });

        // Event listener untuk tombol close (x) pada modal edit
        document.getElementById('closeEditPengguna').addEventListener('click', function() {
            closeModal(document.getElementById('modalEditPengguna'));
        });

        // Event listener untuk tombol close (x) pada modal hapus
        document.getElementById('closeHapusPengguna').addEventListener('click', function() {
            closeModal(document.getElementById('modalHapusPengguna'));
        });

        loadUsers();
        loadLogs();
        loadTotalUsers();
        loadAccessesToday();
        loadVehiclesInside();
    </script>
</body>
</html>

<?php
// Proteksi 1 : pengguna hrus memiliki session login
if (!isset($_SESSION['id_user'])) {
    exit;
}

// Proteksi 2 : hanya admin yg bisa mengelola pengguna
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo "<div class='alert alert-danger shadow-sm text-center mt-4 border-0'>
        <i class='bi bi-shield-lock-fill display-4 d-block mb-3 text-danger'></i>
        <h5 class='fw-bold'>Akses Ditolak!</h5>
        <p class='mb-0'>Hanya pengguna dengan hak akses <b>admin</b> yang dapat mengelola data sistem ini.</p>
    </div>";
    exit;
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : 'view';
switch ($aksi) {

    case 'view':
        $keyword = isset($_GET['cari']) ? trim($_GET['cari']) : '';

        if ($keyword !== '') {
            $param = '%' . $keyword . '%';
            $stmt = $koneksi->prepare(
                "SELECT id_user, nama, username, role
                 FROM user
                 WHERE nama LIKE ? OR username LIKE ?
                 ORDER BY id_user DESC"
            );
            $stmt->bind_param("ss", $param, $param);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $koneksi->query(
                "SELECT id_user, nama, username, role
                 FROM user
                 ORDER BY id_user DESC"
            );    
        }
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold m-0">Data Pengguna Sistem</h5>
        <a href="index.php?page=user&aksi=tambah" class="btn btn-primary btn-sm rounded-pill">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah User
        </a>
    </div>

    <form action="index.php" method="GET" class="mb-4">
        <input type="hidden" name="page" value="user">
        <div class="input-group shadow-sm">
            <input type="text" name="cari" class="form-control" placeholder="Cari nama atau Username.." value="<?= htmlspecialchars($keyword); ?>">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-search"></i> Cari
            </button>
        </div>
    </form>

    <div class="row g-3">
        <?php if ($result && $result->num_rows > 0) { ?>
            <?php while ($row = $result->fetch_assoc()) {
                $warna = ($row['role'] === 'Admin') ? 'bg-danger' : 'bg-primary';
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-light rounded-circle d-flex justify-content-center align-items-center me-3 border" style="width: 45px; height: 45px;">
                                    <i class="bi bi-person-badge fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($row['nama']); ?></h6>
                                    <span class="badge <?= $warna; ?>"><?= htmlspecialchars($row['role']); ?></span>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-at me-1"></i><?= htmlspecialchars($row['username']); ?>
                            </p>
                            <div class="d-flex gap-2">
                                <a class="btn btn-outline-warning btn-sm rounded-pill w-50" href="index.php?page=user&aksi=edit&id=<?= (int)$row['id_user']; ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <!-- Diperbaiki: Arahkan ke aksi=hapus, bukan edit -->
                                <a class="btn btn-outline-danger btn-sm rounded-pill w-50" href="index.php?page=user&aksi=hapus&id=<?= (int)$row['id_user']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                                    <i class="bi bi-trash"></i> Hapus
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12">
                <div class="alert alert-light border text-center">
                    Data pengguna tidak ditemukan.
                </div>
            </div>
        <?php } ?>
    </div>
    <?php
        if (isset($stmt)) {
            $stmt->close();
        }
        break;


    case 'tambah':
        $pesan = '';
        if (isset($_POST['simpan'])) {
            $nama = trim($_POST['nama']);
            $username = trim($_POST['username']);
            $password_input = $_POST['password'];
            $role = $_POST['role'];

            // Diperbaiki: Ditambahkan tanda seru (!) agar mendeteksi jika role tidak sesuai
            if (!in_array($role, ['Admin', 'Kasir'], true)) {
                $pesan = 'Role yang dipilih tidak valid.';
            } elseif ($nama === '' || $username === '' || $password_input === '') {
                $pesan = 'Semua field harus diisi.';
            } else {
                $cek = $koneksi->prepare("SELECT id_user FROM user WHERE username = ?");
                $cek->bind_param("s", $username);
                $cek->execute();
                $cek->store_result();
                $sudah_ada = $cek->num_rows > 0;
                $cek->close();

                if ($sudah_ada) {
                    $pesan = 'Username sudah digunakan. Silakan pilih username lain.';
                } else {
                    $password = md5($password_input);
                    $stmt = $koneksi->prepare(
                        "INSERT INTO user (nama, username, password, role) VALUES (?, ?, ?, ?)"
                    );
                    $stmt->bind_param("ssss", $nama, $username, $password, $role);
                    if ($stmt->execute()) {
                        $stmt->close();
                        echo "<script>
                            alert('Pengguna berhasil ditambahkan.');
                            window.location='index.php?page=user';
                        </script>";
                        exit;
                    } else {
                        $pesan = 'Terjadi kesalahan saat menyimpan data.';
                    }
                    if (isset($stmt)) {
                        $stmt->close();
                    }
                }
            }
        }
    ?>
        <h5 class="fw-bold mb-3">Tambah Pengguna Baru</h5>
        <?php if ($pesan !== '') { ?>
            <div class="alert alert-danger shadow-sm border-0 mb-3"><?= htmlspecialchars($pesan); ?></div>
        <?php } ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="index.php?page=user&aksi=tambah" method="POST">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" id="nama" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Hak Akses</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="Admin">Admin</option>
                            <option value="Kasir">Kasir</option>
                        </select>
                    </div>
                    <button type="submit" name="simpan" class="btn btn-primary mb-2">Simpan Pengguna</button>
                    <a href="index.php?page=user" class="btn btn-light w-100">Batal</a>
                </form>
            </div>
        </div>
    <?php
        break;


    case 'edit':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            echo '<div class="alert alert-warning">ID pengguna tidak valid.</div>';
            break;
        }

        $stmt_get = $koneksi->prepare("SELECT id_user, nama, username, role FROM user WHERE id_user = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $data = $stmt_get->get_result()->fetch_assoc();
        $stmt_get->close();

        if (!$data) {
            echo '<div class="alert alert-warning">Pengguna tidak ditemukan.</div>';
            break;
        }

        $pesan = '';
        if (isset($_POST['update'])) {
            $nama = trim($_POST['nama']);
            $username = trim($_POST['username']);
            $password_input = trim($_POST['password']);
            $role = $_POST['role'];

            if (!in_array($role, ['Admin', 'Kasir'], true)) {
                $pesan = 'Role yang dipilih tidak valid.';
            } elseif ($nama === '' || $username === '') {
                $pesan = 'Nama dan Username harus diisi.';
            } else {
                // Cek apakah username dipakai user lain
                $cek = $koneksi->prepare("SELECT id_user FROM user WHERE username = ? AND id_user != ?");
                $cek->bind_param("si", $username, $id);
                $cek->execute();
                $username_dipakai = $cek->get_result()->num_rows > 0;
                $cek->close();

                if ($username_dipakai) {
                    $pesan = 'Username sudah digunakan oleh pengguna lain.';
                } else {
                    if ($password_input !== '') {
                        $password = md5($password_input);
                        $stmt_update = $koneksi->prepare(
                            "UPDATE user SET nama = ?, username = ?, password = ?, role = ? WHERE id_user = ?"
                        );
                        $stmt_update->bind_param("ssssi", $nama, $username, $password, $role, $id);
                    } else {
                        // password kosong = jangan ubah kolom password
                        $stmt_update = $koneksi->prepare(
                            "UPDATE user SET nama = ?, username = ?, role = ? WHERE id_user = ?"
                        );
                        $stmt_update->bind_param("sssi", $nama, $username, $role, $id);
                    }

                    if ($stmt_update->execute()) {
                        $stmt_update->close();
                        echo "<script>
                            alert('Pengguna berhasil diperbarui.');
                            window.location='index.php?page=user';
                        </script>";
                        exit;
                    } else {
                        $pesan = 'Terjadi kesalahan saat memperbarui data.';
                    }
                    if (isset($stmt_update)) {
                        $stmt_update->close();
                    }
                }
            }
            
            // Perbarui array $data jika validasi gagal agar input tidak kosong kembali
            $data['nama'] = $nama;
            $data['username'] = $username;
            $data['role'] = $role;
        }
    ?>
        <h5 class="fw-bold mb-3">Edit Pengguna</h5>
        <?php if ($pesan !== '') { ?>
            <div class="alert alert-danger shadow-sm border-0 mb-3"><?= htmlspecialchars($pesan); ?></div>
        <?php } ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="index.php?page=user&aksi=edit&id=<?= (int)$data['id_user']; ?>" method="POST">
                    <div class="mb-3">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" id="nama" class="form-control" value="<?= htmlspecialchars($data['nama']); ?>" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($data['username']); ?>" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password Baru</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Kosongkan jika tidak ingin merubah password">
                        <div class="form-text">Password lama tetap digunakan jika field ini kosong.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold" for="role">Hak Akses</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="Admin" <?= $data['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="Kasir" <?= $data['role'] === 'Kasir' ? 'selected' : ''; ?>>Kasir</option>
                        </select>
                    </div>
                    <button type="submit" name="update" class="btn btn-primary mb-2">Perbarui Pengguna</button>
                    <a href="index.php?page=user" class="btn btn-light w-100">Batal</a>
                </form>
            </div>    
        </div>
    <?php
        break;

    case 'hapus':
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            echo "<script>
                alert('ID pengguna tidak valid.');
                window.location='index.php?page=user';
            </script>";
            exit;
        }
        // self-deletion guard: admin tidak boleh menghapus akun sendiri.
        if ($id === $_SESSION['id_user']) {
            echo '<script>
                alert("Gagal. Anda tidak dapat menghapus akun yang sedang digunakan.");
                window.location="index.php?page=user";
            </script>';
            exit;
        }
        $stmt = $koneksi->prepare("DELETE FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            echo "<script>
                alert('Pengguna berhasil dihapus.');
                window.location='index.php?page=user';
            </script>";
            exit;
        } else {
            $stmt->close();
            echo "<script>
                alert('Terjadi kesalahan saat menghapus pengguna.');
                window.location='index.php?page=user';
            </script>";
            exit;
        }
        break;

    default:
        echo '<div class="alert alert-danger">Aksi tidak dikenali. Silahkan kembali ke daftar pengguna.</div>';
        break;
}
?>
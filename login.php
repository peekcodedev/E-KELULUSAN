<?php
// login.php

// Memulai session PHP
session_start();

// Cek jika user sudah login, arahkan ke dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: admin/dashboard.php");
    exit;
}

// Memasukkan file koneksi database
require_once "config/db.php";

// Mendefinisikan variabel dan menginisialisasi dengan nilai kosong
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Memproses data form saat form disubmit
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Mengecek apakah username kosong
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else{
        $username = trim($_POST["username"]);
    }

    // Mengecek apakah password kosong
    if(empty(trim($_POST["password"]))){
        $password_err = "Mohon masukkan password Anda.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Memvalidasi kredensial
    if(empty($username_err) && empty($password_err)){
        // Menyiapkan statement select
        $sql = "SELECT id, username, password FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Mengikat variabel ke parameter statement yang sudah disiapkan
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Mengatur parameter
            $param_username = $username;

            // Mencoba mengeksekusi statement yang sudah disiapkan
            if(mysqli_stmt_execute($stmt)){
                // Menyimpan hasil
                mysqli_stmt_store_result($stmt);

                // Mengecek jika username ada, lalu verifikasi password
                if(mysqli_stmt_num_rows($stmt) == 1){
                    // Mengikat variabel hasil
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password benar, mulai session baru
                            session_start();

                            // Menyimpan data di variabel session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;

                            // Arahkan user ke halaman dashboard
                            header("location: admin/dashboard.php");
                        } else{
                            // Password tidak valid, tampilkan pesan error
                            $login_err = "Username atau password salah.";
                        }
                    }
                } else{
                    // Username tidak ada, tampilkan pesan error
                    $login_err = "Username atau password salah.";
                }
            } else{
                echo "Ada yang salah. Mohon coba lagi nanti.";
            }

            // Menutup statement
            mysqli_stmt_close($stmt);
        }
    }

    // Menutup koneksi
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Pengumuman Kelulusan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
        }
        .container {
            max-width: 400px;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="container bg-white p-8 rounded-lg shadow-lg w-full">
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">Login Admin</h2>
        <p class="text-center text-gray-600 mb-6">Silakan masukkan kredensial Anda untuk login.</p>

        <?php
        if(!empty($login_err)){
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">' . $login_err . '</span>
                  </div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" name="username" id="username" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo (!empty($username_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $username; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $username_err; ?></span>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" name="password" id="password" class="shadow appearance-none border rounded-lg w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-300 ease-in-out w-full">
                    Login
                </button>
            </div>
        </form>
    </div>
</body>
</html>

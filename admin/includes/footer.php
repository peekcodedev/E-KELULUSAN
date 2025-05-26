<?php
// admin/includes/footer.php
?>
        </div>
        </div>
    </body>
</html>
<?php
// Menutup koneksi database di akhir setiap halaman
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>

<?php
require __DIR__.'/Backend/database/koneksi.php';
if ($conn===null) {
    echo "No connection\n";
} else {
    if ($conn->connect_error) {
        echo "Connect error: {$conn->connect_error}\n";
    } else {
        echo "Connection OK\n";
    }
}
?>

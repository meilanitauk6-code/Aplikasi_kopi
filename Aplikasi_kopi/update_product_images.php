<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Aplikasi_kopi/config/database.php';
// Maintenance script — admins only.
requireLogin('admin');

// New product images from user
$productImages = [
    // Kopi Arabika Gayo Aceh (id: 1)
    ['id' => 1, 'image' => 'https://down-my.img.susercontent.com/file/id-11134207-7r98y-lonqyxh07sqv94'],
    // Kopi Robusta Lampung Premium (id: 2)
    ['id' => 2, 'image' => 'https://down-id.img.susercontent.com/file/id-11134207-7rbkb-maz112536ezv8b'],
    // Kopi Toraja Single Origin (id: 3)
    ['id' => 3, 'image' => 'https://5.imimg.com/data5/RN/AC/IB/SELLER-5882261/arabica-green-coffee-beans-1000x1000.jpg'],
    // Kopi Arabika Flores (id: 4)
    ['id' => 4, 'image' => 'https://down-my.img.susercontent.com/file/id-11134207-7r98y-lonqyxh07sqv94'],
    // Kopi Blend Signature House (id: 5)
    ['id' => 5, 'image' => 'https://th.bing.com/th/id/R.0436f2e47740096cc5cdad95bcf76bf4?rik=gs7EaKlRSHpj8g&riu=http%3a%2f%2fsathora.or.id%2fwp-content%2fuploads%2f2020%2f10%2fkopi-pak-bona-e1603960844312-916x1024.jpg&ehk=pnw2dTtej3985QVmWt22EFs3RdIrzp9wKwucvnyWnzA%3d&risl=&pid=ImgRaw&r=0'],
    // Kopi Robusta Temanggung (id: 6)
    ['id' => 6, 'image' => 'https://down-id.img.susercontent.com/file/id-11134207-7rbkb-maz112536ezv8b'],
    // Kopi Liberika Bengkulu (id: 7)
    ['id' => 7, 'image' => 'https://th.bing.com/th/id/R.0436f2e47740096cc5cdad95bcf76bf4?rik=gs7EaKlRSHpj8g&riu=http%3a%2f%2fsathora.or.id%2fwp-content%2fuploads%2f2020%2f10%2fkopi-pak-bona-e1603960844312-916x1024.jpg&ehk=pnw2dTtej3985QVmWt22EFs3RdIrzp9wKwucvnyWnzA%3d&risl=&pid=ImgRaw&r=0'],
    // Kopi Kintamani Bali (id: 8)
    ['id' => 8, 'image' => 'https://5.imimg.com/data5/RN/AC/IB/SELLER-5882261/arabica-green-coffee-beans-1000x1000.jpg'],
];

$success = 0;
$errors = 0;

foreach ($productImages as $item) {
    $id = (int) $item['id'];
    $image = $conn->real_escape_string($item['image']);

    $sql = "UPDATE products SET image = '$image' WHERE id = $id";
    if ($conn->query($sql)) {
        echo "✅ Updated product ID $id<br>";
        $success++;
    } else {
        echo "❌ Failed to update product ID $id: " . $conn->error . "<br>";
        $errors++;
    }
}

echo "<br><strong>Total: $success updated, $errors failed</strong>";
$conn->close();
?>
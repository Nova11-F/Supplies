<?php
include __DIR__ . '/../../../config/database.php';

//  INSERT DATA
if (isset($_POST['create'])) {
    $name = isset($_POST['products_name']) ? trim($_POST['products_name']) : '';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : NULL;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if ($name === '') {
        echo "<script>alert('Nama Product harus diisi!'); history.back();</script>";
        exit;
    }

    // Escape strings to prevent SQL injection
    $name_escaped = mysqli_real_escape_string($conn, $name);
    $description_escaped = mysqli_real_escape_string($conn, $description);

    // Validasi kombinasi name + brand_id
    $brand_check = $brand_id ? "AND brand_id = $brand_id" : "AND brand_id IS NULL";
    $check_sql = "SELECT id FROM products WHERE name='$name_escaped' $brand_check";
    $res_check = mysqli_query($conn, $check_sql);
    if ($res_check && mysqli_num_rows($res_check) > 0) {
        echo "<script>alert('Produk dengan brand ini sudah ada!'); history.back();</script>";
        exit;
    }

    $brand_code = 'UNK'; // default

    if ($brand_id) {
        $res_brand = mysqli_query($conn, "SELECT brand_code FROM brands WHERE id=$brand_id");
        if ($res_brand && mysqli_num_rows($res_brand) > 0) {
            $row_brand = mysqli_fetch_assoc($res_brand);
            $full_code = $row_brand['brand_code']; // contoh: "BRN-SONY"

            // Ambil substring setelah "BRN-"
            if (strpos($full_code, 'BRN-') === 0) {
                $brand_code = substr($full_code, 4); // ambil "SONY"
            } else {
                $brand_code = $full_code; // jika tidak ada prefix BRN-
            }
        }
    }

    // Hapus semua spasi
    $name_no_space = str_replace(' ', '', $name);

    // Ambil 4 huruf pertama (uppercase)
    $prefix = strtoupper(substr($name_no_space, 0, 4));

    $product_code = "PROD-" . $prefix . "-" . $brand_code;

    // Build SQL safely
    $category_sql = $category_id ? $category_id : 'NULL';
    $brand_sql = $brand_id ? $brand_id : 'NULL';

    $insert_sql = "INSERT INTO products (product_code, name, category_id, brand_id, description)
        VALUES ('$product_code', '$name_escaped', $category_sql, $brand_sql, '$description_escaped')";
    
    mysqli_query($conn, $insert_sql) or die(mysqli_error($conn));

    echo "<script>alert('Product berhasil ditambahkan!'); location.href='index.php?page=catalog&sub=products';</script>";
    exit;
}

// UPDATE DATA FOR PRODUCTS
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $name = isset($_POST['products_name']) ? trim($_POST['products_name']) : '';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : NULL;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';

    if ($name === '') {
        echo "<script>alert('Nama Product harus diisi!'); history.back();</script>";
        exit;
    }

    // Escape strings
    $name_escaped = mysqli_real_escape_string($conn, $name);
    $description_escaped = mysqli_real_escape_string($conn, $description);

    // Check for duplicates (excluding current record)
    $brand_check = $brand_id ? "AND brand_id = $brand_id" : "AND brand_id IS NULL";
    $check_sql = "SELECT id FROM products WHERE name='$name_escaped' $brand_check AND id != $id";
    $res_check = mysqli_query($conn, $check_sql);
    if ($res_check && mysqli_num_rows($res_check) > 0) {
        echo "<script>alert('Produk dengan brand ini sudah ada!'); history.back();</script>";
        exit;
    }

    // Get brand code
    $brand_code = 'UNK';
    if ($brand_id) {
        $res_brand = mysqli_query($conn, "SELECT brand_code FROM brands WHERE id=$brand_id");
        if ($res_brand && mysqli_num_rows($res_brand) > 0) {
            $row_brand = mysqli_fetch_assoc($res_brand);
            $full_code = $row_brand['brand_code'];
            
            if (strpos($full_code, 'BRN-') === 0) {
                $brand_code = substr($full_code, 4);
            } else {
                $brand_code = $full_code;
            }
        }
    }

    // Generate new product code
    $name_no_space = str_replace(' ', '', $name);
    $prefix = strtoupper(substr($name_no_space, 0, 4));
    $product_code = "PROD-" . $prefix . "-" . $brand_code;

    // Build SQL
    $category_sql = $category_id ? $category_id : 'NULL';
    $brand_sql = $brand_id ? $brand_id : 'NULL';

    $update_sql = "UPDATE products SET
        product_code='$product_code',
        name='$name_escaped',
        category_id=$category_sql,
        brand_id=$brand_sql,
        description='$description_escaped'
        WHERE id=$id";
    
    mysqli_query($conn, $update_sql) or die(mysqli_error($conn));

    echo "<script>alert('Product berhasil diupdate!'); location.href='index.php?page=catalog&sub=products';</script>";
    exit;
}

//  DELETE DATA
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    echo "<script>alert('Products berhasil dihapus!'); location.href='index.php?page=catalog&sub=products';</script>";
    exit;
}

// GET ALL DATA
$products_result = mysqli_query($conn, "
    SELECT 
        p.*, 
        c.name AS category_name, 
        b.name AS brand_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN brands b ON b.id = p.brand_id
    ORDER BY p.id ASC
");
?>
<!-- Create Product -->
<div class="flex items-center justify-between mb-4 mt-3">
    <h1 class="text-2xl px-4 font-bold text-[#092363]">Product Management</h1>
    <?php if ($isAdmin): ?>
        <button
            onclick="openModal('productModal')"
            class="bg-[#092363] font-bold text-white px-6 py-2 rounded-lg flex items-center gap-2 hover:bg-[#e6b949] hover:text-[#092363] transform duration-300 hover:scale-105 transition-all cursor-pointer shadow-md">
            <i class='bx bx-plus text-lg'></i>
            <span>Tambah Product</span>
        </button>
    <?php endif; ?>
</div>

<!-- Search box & Filter Group -->
<div class="w-full flex justify-between gap-2 mb-6">
    <!-- Search box -->
    <div class="flex items-center gap-3 bg-white px-3 py-2 rounded-md shadow-md w-full">
        <i class='bx bx-search text-xl text-gray-500'></i>
        <input type="text" placeholder="Search item..."
            class="w-full ml-2 focus:outline-none">
    </div>

    <!-- Filter Group -->
    <div class="flex gap-2 justify-end">

        <!-- Filter Category -->
        <select class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer">
            <option value="">All Categories</option>
            <option value="electronics">Electronics</option>
            <option value="sparepart">Spare Parts</option>
            <option value="office">Office Supplies</option>
        </select>

        <!-- Filter Location -->
        <select class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer">
            <option value="">All Locations</option>
            <option value="warehouse_a">Warehouse A</option>
            <option value="warehouse_b">Warehouse B</option>
            <option value="warehouse_c">Warehouse C</option>
        </select>

        <!-- Filter Stock Status -->
        <select class="px-4 py-2 bg-white rounded-md shadow-md focus:outline-none text-gray-700 cursor-pointer">
            <option value="">All Brands</option>
            <option value="low">Low Stock</option>
            <option value="out">Out of Stock</option>
            <option value="normal">Normal</option>
        </select>
    </div>
</div>

<!-- Table Produk -->
<div class="overflow-x-auto bg-white shadow-md rounded-md mb-6">
    <table class="min-w-full">
        <thead class="bg-[#092363] text-white">
            <tr>
                <th class="px-4 py-3 text-center w-16">No</th>
                <th class="px-4 py-3 text-center">Product ID</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3 text-center">Category</th>
                <th class="px-4 py-3 text-center">Brands</th>
                <th class="px-4 py-3 text-center">Description</th>
                <?php if ($isAdmin): ?>
                    <th class="px-4 py-3 text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php if (mysqli_num_rows($products_result) > 0): ?>
                <?php $no = 1; ?>
                <?php while ($row = mysqli_fetch_assoc($products_result)): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-center text-gray-500"><?= $no++ ?></td>
                        <td class="px-4 py-3 text-gray-600 font-medium"><?= htmlspecialchars($row['product_code']) ?></td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800"><?= htmlspecialchars($row['category_name']) ?></td>
                        <td class="px-4 py-3 text-center font-bold text-gray-800"><?= htmlspecialchars($row['brand_name']) ?></td>
                        <td class="px-4 py-3 text-center text-gray-600 text-sm"><?= htmlspecialchars($row['description']) ?></td>
                        <?php if ($isAdmin): ?>
                            <td class="px-4 py-3 flex gap-2 justify-center">
                                <button
                                    onclick="openEditModal('editProductModal', this)"
                                    data-id="<?= $row['id'] ?>"
                                    data-product_code="<?= htmlspecialchars($row['product_code']) ?>"
                                    data-products_name="<?= htmlspecialchars($row['name']) ?>"
                                    data-category_id="<?= $row['category_id'] ?>"
                                    data-brand_id="<?= $row['brand_id'] ?>"
                                    data-description="<?= htmlspecialchars($row['description']) ?>"
                                    class="bg-yellow-400 text-white px-2 py-1 rounded hover:bg-yellow-500 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-edit text-lg'></i>
                                </button>
                                <button onclick="if(confirm('Are you sure to delete this product?')){ 
                                    window.location.href='index.php?page=catalog&sub=products&delete=<?= $row['id'] ?>' 
                                }"
                                    class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600 transform duration-300 hover:scale-102 transition-all cursor-pointer">
                                    <i class='bx bxs-trash text-lg'></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 7 : 6 ?>" class="px-4 py-8 text-center text-gray-500">
                        <i class='bx bx-package text-5xl mb-2 opacity-50'></i>
                        <p class="font-semibold">Tidak ada Produk yang tersedia</p>
                        <p class="text-sm mt-1">Buat Produk terlebih dahulu</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Next menu -->
<div class="flex justify-between items-center px-6 mb-10">
    <span class="text-sm text-gray-500">Showing 1 to 10 of 150 entries</span>
    <div class="flex gap-1">
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Prev</button>
        <button class="px-3 py-1 rounded border border-[#092363] bg-[#092363] text-white text-sm">1</button>
        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-[#092363] hover:text-white text-sm">2</button>
        <button class="px-3 py-1 rounded border bg-white border-gray-300 text-gray-700 hover:bg-[#e6b949] hover:text-[#092363] text-sm font-bold">Next</button>
    </div>
</div>

<!-- Modal Create Product -->
<div id="productModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('productModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Tambah Product Baru</h3>
                <button onclick="closeModal('productModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Product</label>
                    <input type="text" name="products_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="Laptop">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Category</label>
                    <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Category</option>
                        <?php
                        $cat_query = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
                        while ($cat_row = mysqli_fetch_assoc($cat_query)) {
                            echo '<option value="' . $cat_row['id'] . '">' . htmlspecialchars($cat_row['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Brands</label>
                    <select name="brand_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                        <option value="">Pilih Brands</option>
                        <?php
                        $brand_query = mysqli_query($conn, "SELECT id, name FROM brands ORDER BY name ASC");
                        while ($brand_row = mysqli_fetch_assoc($brand_query)) {
                            echo '<option value="' . $brand_row['id'] . '">' . htmlspecialchars($brand_row['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Deskripsi</label>
                    <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none resize-none" placeholder="Instruksi khusus..."></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('productModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="create"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Tambah Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Product -->
<div id="editProductModal" class="custom-modal">
    <div class="modal-backdrop" onclick="closeModal('editProductModal')"></div>
    <div class="modal-flex-container">
        <div class="modal-content-box">
            <div class="bg-[#092363] px-6 py-4 rounded-t-[16px] flex justify-between items-center">
                <h3 class="text-white text-lg font-bold tracking-wide">Edit Product</h3>
                <button onclick="closeModal('editProductModal')" class="text-white hover:text-[#e6b949] transition-colors text-2xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Product ID</label>
                    <input type="text" name="product_code" id="edit_product_code" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm  outline-none bg-gray-100 cursor-not-allowed" readonly>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Product</label>
                    <input type="text" name="products_name" id="edit_products_name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none" placeholder="Laptop">
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Category</label>
                        <select name="category_id" id="edit_category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Category</option>
                            <?php
                            $cat_query2 = mysqli_query($conn, "SELECT id, category_code, name FROM categories ORDER BY name ASC");
                            while ($cat_row2 = mysqli_fetch_assoc($cat_query2)) {
                                echo '<option value="' . $cat_row2['id'] . '">' . htmlspecialchars($cat_row2['name']) . ' (' . htmlspecialchars($cat_row2['category_code']) . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Brands</label>
                        <select name="brand_id" id="edit_brand_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#092363] outline-none cursor-pointer">
                            <option value="">Pilih Brands</option>
                            <?php
                            $brand_query2 = mysqli_query($conn, "SELECT id, brand_code, name FROM brands ORDER BY name ASC");
                            while ($brand_row2 = mysqli_fetch_assoc($brand_query2)) {
                                echo '<option value="' . $brand_row2['id'] . '">' . htmlspecialchars($brand_row2['name']) . ' (' . htmlspecialchars($brand_row2['brand_code']) . ')</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wide">Deskripsi</label>
                    <textarea name="description" id="edit_description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#092363] outline-none resize-none" placeholder="Instruksi khusus..."></textarea>
                </div>

                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="closeModal('editProductModal')" class="px-5 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100 transition-all">Batal</button>
                    <button type="submit" name="update"
                        class="px-8 py-2 rounded-lg text-sm font-bold text-white bg-[#092363] hover:bg-[#e6b949] hover:text-[#092363] shadow-lg transform hover:-translate-y-0.5 transition-all">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>


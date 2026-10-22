<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/session_check.php';

$page_title = 'Products';
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <h2>Browse Products</h2>
  <input type="text" id="search" placeholder="Search products..." onkeyup="searchProducts(this.value)">
</div>
<div class="product-grid" id="product-grid">
  <!-- Results will be loaded here -->
</div>

<script>
function searchProducts(query) {
    fetch('/api/search.php?q=' + encodeURIComponent(query))
        .then(response => response.text())
        .then(data => {
            document.getElementById('product-grid').innerHTML = data;
        });
}
// Initial load
searchProducts('');
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

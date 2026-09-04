<?php
/**
 * Redirect to Dashboard Add-Product tab
 */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    header("Location: dashboard.php#add-product?id=" . $id);
} else {
    header("Location: dashboard.php#add-product");
}
exit;

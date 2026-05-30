<?php
include 'includes/config.php';
include 'includes/dbConnection.php';
include 'includes/functions.php';
session_start();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = sanitize($_GET['id']);

    $sql = "DELETE FROM productseller WHERE productseller_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['success_message'] = "Productseller record deleted successfully.";
            } else {
                 $_SESSION['error_message'] = "No productseller record found with ID: " . htmlspecialchars($id) . " or it was already deleted.";
            }
            header("Location: productseller.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error deleting record: " . $stmt->error;
            header("Location: productseller.php");
            exit();
        }

        $stmt->close();
    } else {
         $_SESSION['error_message'] = "Database error preparing delete statement: " . $conn->error;
         header("Location: productseller.php");
         exit();
    }

    $conn->close();
} else {
    $_SESSION['error_message'] = "Invalid or missing productseller record ID specified for deletion.";
    header("Location: productseller.php");
    exit();
}
?>
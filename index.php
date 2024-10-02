<?php
require 'config/database.php';

global $pdo;

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$offset = ($page - 1) * $limit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'list') {
                $stmt = $pdo->prepare("SELECT * FROM products LIMIT :limit OFFSET :offset");
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
                $totalRecords = $countStmt->fetchColumn();
                $totalPages = ceil($totalRecords / $limit);

                echo json_encode(['status' => 'success', 'products' => $products, 'totalPages' => $totalPages]);
                exit();
            } elseif ($_POST['action'] === 'empty') {
                $stmt = $pdo->query("UPDATE products SET orderamount = 0");
                echo json_encode(['status' => 'success']);
                exit();
            } elseif ($_POST['action'] === 'add' && isset($_POST['number'])) {
                $stmt = $pdo->prepare("UPDATE products SET orderamount = orderamount + 1 WHERE number = ?");
                $stmt->execute([$_POST['number']]);
                echo json_encode(['status' => 'success']);
                exit();
            } elseif ($_POST['action'] === 'clear' && isset($_POST['number'])) {
                $stmt = $pdo->prepare("UPDATE products SET orderamount = 0 WHERE number = ?");
                $stmt->execute([$_POST['number']]);
                echo json_encode(['status' => 'success']);
                exit();
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draivi Backend Test</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<button id="listButton">List</button>
<button id="emptyButton">Empty</button>
<div id="productTable"></div>
<div id="pagination"></div>
<div id="message" style="color: red;"></div>

<script>
    let currentPage = 1;
    let totalPages = 0;

    function loadProducts(page) {
        $.post('index.php', {action: 'list', page: page}, function (data) {
            const response = JSON.parse(data);

            if (response.status === 'error') {
                $('#message').text(response.message);
                return;
            }

            totalPages = response.totalPages;
            let products = response.products;

            if (products.length === 0) {
                $('#productTable').html('<p>No products found.</p>');
                $('#pagination').empty();
                return;
            }

            let tableHtml = '<table border="1"><tr><th>Number</th><th>Name</th><th>Bottle Size</th><th>Price</th><th>Price GBP</th><th>Order Amount</th><th>Actions</th></tr>';
            products.forEach(product => {
                tableHtml += `<tr>
                            <td>${product.number}</td>
                            <td>${product.name}</td>
                            <td>${product.bottlesize}</td>
                            <td>${product.price}</td>
                            <td>${product.priceGBP}</td>
                            <td class="orderAmount" id="orderAmount_${product.number}">${product.orderamount}</td>
                            <td>
                                <button class="addButton" data-number="${product.number}">Add</button>
                                <button class="clearButton" data-number="${product.number}">Clear</button>
                            </td>
                        </tr>`;
            });

            tableHtml += '</table>';
            $('#productTable').html(tableHtml);
            updatePagination();
        });
    }

    function updatePagination() {
        let paginationHtml = '';
        if (currentPage > 1) {
            paginationHtml += `<button class="prevButton">Previous</button>`;
        }
        if (currentPage < totalPages) {
            paginationHtml += `<button class="nextButton">Next</button>`;
        }
        $('#pagination').html(paginationHtml);
    }

    $(document).ready(function () {
        $('#listButton').click(function () {
            loadProducts(currentPage);
        });

        $('#emptyButton').click(function () {
            $.post('index.php', {action: 'empty'}, function (data) {
                const response = JSON.parse(data);
                if (response.status === 'success') {
                    $('#productTable').empty();
                    $('#pagination').empty();
                    $('#message').text('Order amounts cleared successfully.');
                } else {
                    $('#message').text('Error clearing order amounts.');
                }
            });
        });

        $(document).on('click', '.addButton', function () {
            const number = $(this).data('number');
            $.post('index.php', {action: 'add', number: number}, function (data) {
                const response = JSON.parse(data);
                if (response.status === 'success') {
                    const orderAmountCell = $(`#orderAmount_${number}`);
                    orderAmountCell.text(parseInt(orderAmountCell.text()) + 1);
                    $('#message').text('Order amount added successfully.');
                } else {
                    $('#message').text('Error adding order amount.');
                }
            });
        });

        $(document).on('click', '.clearButton', function () {
            const number = $(this).data('number');
            $.post('index.php', {action: 'clear', number: number}, function (data) {
                const response = JSON.parse(data);
                if (response.status === 'success') {
                    $(`#orderAmount_${number}`).text(0);
                    $('#message').text('Order amount cleared successfully.');
                } else {
                    $('#message').text('Error clearing order amount.');
                }
            });
        });

        $(document).on('click', '.prevButton', function () {
            if (currentPage > 1) {
                currentPage--;
                loadProducts(currentPage);
            }
        });

        $(document).on('click', '.nextButton', function () {
            if (currentPage < totalPages) {
                currentPage++;
                loadProducts(currentPage);
            }
        });
    });
</script>
</body>
</html>

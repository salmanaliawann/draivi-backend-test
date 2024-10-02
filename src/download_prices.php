<?php

require '../vendor/autoload.php';
require '../config/database.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

global $pdo;

/**
 * Fetch exchange rate from the API.
 *
 * @param string $apiKey
 * @return float
 * @throws Exception
 */
function fetchExchangeRate(string $apiKey): float
{
    $exchangeRateUrl = "https://apilayer.net/api/live?access_key=$apiKey&source=EUR&currencies=GBP";
    $response = @file_get_contents($exchangeRateUrl);

    if ($response === false) {
        throw new Exception('Error fetching exchange rate');
    }

    $data = json_decode($response, true);
    if (!isset($data['quotes']['EURGBP'])) {
        throw new Exception('Exchange rate not found');
    }

    return (float)$data['quotes']['EURGBP'];
}

/**
 * Load spreadsheet data from the specified URL.
 *
 * @return array
 * @throws Exception
 */
function loadSpreadsheet(): array
{
    $fileUrl = 'https://www.alko.fi/INTERSHOP/static/WFS/Alko-OnlineShop-Site/-/Alko-OnlineShop/fi_FI/Alkon%20Hinnasto%20Tekstitiedostona/alkon-hinnasto-tekstitiedostona.xlsx';
    $file = '../assets/alkon-hinnasto-tekstitiedostona.xlsx';

    if (file_put_contents($file, file_get_contents($fileUrl)) === false) {
        throw new Exception('Error downloading the Excel file');
    }

    if (!file_exists($file)) {
        throw new Exception('Excel file not found');
    }

    return IOFactory::load($file)->getActiveSheet()->toArray(null, true, true, true);
}

/**
 * Insert or update products in the database.
 *
 * @param PDO $pdo
 * @param array $batchInsert
 * @throws Exception
 */
function insertOrUpdateProducts(PDO $pdo, array $batchInsert): void
{
    $placeholders = rtrim(str_repeat('(?, ?, ?, ?, ?), ', count($batchInsert)), ', ');
    $stmt = $pdo->prepare("INSERT INTO products (number, name, bottlesize, price, priceGBP)
                            VALUES $placeholders
                            ON DUPLICATE KEY UPDATE
                            name = VALUES(name), 
                            bottlesize = VALUES(bottlesize),
                            price = VALUES(price), 
                            priceGBP = VALUES(priceGBP), 
                            timestamp = NOW()");

    $flattenedData = array_merge(...$batchInsert);

    try {
        $stmt->execute($flattenedData);
    } catch (PDOException $e) {
        throw new Exception("Error inserting/updating products: " . $e->getMessage());
    }
}

try {
    $apiKey = 'e5adcce0a8be7b2cd79a13f7bbf78a1b';
    $exchangeRate = fetchExchangeRate($apiKey);

    $sheetData = loadSpreadsheet();

    $batchInsert = [];
    $batchSize = 1000;
    $rowCounter = 0;

    foreach ($sheetData as $row) {
        if ($rowCounter < 4) {
            $rowCounter++;
            continue;
        }

        $number = $row['A']; // 'Numero'
        $name = $row['B'];   // 'Nimi'
        $bottlesize = $row['D']; // 'Pullokoko'
        $price = (!empty($row['E']) && is_numeric($row['E']))
            ? number_format((float)$row['E'], 2, '.', '')
            : 0; // 'Hinta'
        $priceGBP = number_format($price * $exchangeRate, 2, '.', ''); // Convert to GBP

        $batchInsert[] = [$number, $name, $bottlesize, $price, $priceGBP];

        if (count($batchInsert) >= $batchSize) {
            $pdo->beginTransaction();
            try {
                insertOrUpdateProducts($pdo, $batchInsert);
                $pdo->commit();
                $batchInsert = [];
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Batch insert failed: " . $e->getMessage());
            }
        }
    }

    if (!empty($batchInsert)) {
        $pdo->beginTransaction();
        try {
            insertOrUpdateProducts($pdo, $batchInsert);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Final insert failed: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}

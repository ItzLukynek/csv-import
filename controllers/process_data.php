<?php
require_once 'pdo.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];

    // test if error from upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        logMessage("Error nahrání souboru: " . $file['error'], 'error');
        echo "Error nahrání souboru";
        exit;
    }
    logMessage("Soubor nahrán");
    // test if csv
    $mimeType = mime_content_type($file['tmp_name']);
    if ($mimeType !== 'text/csv' && $mimeType !== 'text/plain') {
        logMessage("Neplatný formát: $mimeType. Prosím nahrajte CSV", 'error');
        echo "Neplatný formát. Prosím nahrajte CSV";
        exit;
    }
    logMessage("Soubor je ve správném formátu");
    // read and validate csv
    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {

        if (fgetcsv($handle, 1000, ",") === false) {
            logMessage("Nečitelná hlavička");
        }
        $mistakes = 0;
       
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $validationResult = validateRow($row);

            if ($validationResult !== true) {
                $mistakes++;
            } else {
                try {
                    $pdo = getPDO();

                    $stmt = $pdo->prepare("
                        INSERT INTO Items
                        (Item, Carton_Qty, Price, Colour, Size, `Desc`, `Weight`, EAN13, DUN14, Image_Path)
                        VALUES 
                        (:Item, :Carton_Qty, :Price, :Colour, :Size, :Desc, :Weight, :EAN13, :DUN14, :Image_Path)
                    ");

                    $stmt->execute([
                        ':Item' => $row[0],
                        ':Carton_Qty' => $row[1],
                        ':Price' => $row[2],
                        ':Colour' => $row[3],
                        ':Size' => $row[4],
                        ':Desc' => $row[5],
                        ':Weight' => $row[6],
                        ':EAN13' => $row[7],
                        ':DUN14' => $row[8],
                        ':Image_Path' => $row[9]
                    ]);

                } catch (Exception $e) {
                    logMessage("Nepodařilo se uložit do databáze: " . implode(", ", $row) . ". Error: " . $e->getMessage(), 'error');
                }
            }
        }
        fclose($handle);
        logMessage("Dokončeno zpracování souboru");
        logMessage("Při validaci najduto: " . $mistakes . " chyb");
    } else {
        logMessage("Error při otevírání souboru");
        echo "Error při otevírání souboru";
        exit;
    }
} else {
    logMessage("Nebyl nahrán žádný soubor");
    echo "Nebyl nahrán žádný soubor";
    exit;
}

// validate each row
function validateRow($row) {
    $errors = [];

    if (empty($row[0])) {
        $errors[] = 'Neplatný item';
    }

    if (filter_var($row[1], FILTER_VALIDATE_INT) === false) {
        $errors[] = 'Neplatný Carton_Qty';
    }

    if ($row[2] === "") {
        $row[2] = 0;
    } else if (filter_var($row[2], FILTER_VALIDATE_FLOAT) === false || $row[2] <= 0) {
        $errors[] = 'Neplatný Price: ' . $row[2];
    }

    if (empty($row[3])) {
        $errors[] = 'Neplatný Colour';
    }

    if (filter_var($row[4], FILTER_VALIDATE_INT) === false) {
        $errors[] = 'Neplatný Size';
    }

    if (empty($row[5])) {
        $errors[] = 'Neplatný Desc';
    }

    if (filter_var($row[6], FILTER_VALIDATE_FLOAT) === false || $row[6] <= 0) {
        $errors[] = 'Neplatný Weight(Kg)';
    }

    if ($row[7] !== "" && !validateEAN($row[7], 13)) {
        $errors[] = 'Neplatný EAN13';
    }

    if ($row[8] !== "" && !validateEAN($row[8], 14)) {
        $errors[] = 'Neplatný DUN14';
    }

    if (filter_var($row[9], FILTER_VALIDATE_URL) === false) {
        $errors[] = 'Neplatný Image_Path';
    }

    return empty($errors) ? true : $errors;
}

// test EAN and DUN
function validateEAN($code, $length) {
    
    if (!preg_match('/^\d{' . $length . '}$/', $code)) {
        return false;
    }
    return true;
}



// log message
function logMessage($message, $type = 'info') {
    // log to file
    $logFile = '../logs/log.txt';
    $time = date('Y-m-d H:i:s');
    $logEntry = "[$time] [$type] $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND);

    // log to database
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("INSERT INTO logs (`desc`, `time`) VALUES (:desc, :time)");
        $stmt->execute([':desc' => $message, ':time' => $time]);
    } catch (Exception $e) {
        $errorMessage = "Failed to log to database: " . $e->getMessage();
        file_put_contents($logFile, "[$time] [error] $errorMessage" . PHP_EOL, FILE_APPEND);
    }
}

?>
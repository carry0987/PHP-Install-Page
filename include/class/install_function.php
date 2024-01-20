<?php
if (defined('IN_INSTALL') !== true) exit('Access Denied');

define('DIR_SEP', DIRECTORY_SEPARATOR);

function trimPath($path)
{
    return str_replace(array('/', '\\', '//', '\\\\'), DIR_SEP, $path);
}
/* Avoid Attack */
function input_filter($value)
{
    $value = str_replace("'", "\"", "$value");
    $value = trim($value);
    $value = stripslashes($value);
    $value = htmlspecialchars($value);
    return $value;
}

/* Get file list */
function get_file_list()
{
    return glob('/../../*');
}

/* Generate Session ID */
function generateSessionID(int $id_len)
{
    $id = '';
    $word = 'abcdefghijkmnpqrstuvwxyz23456789';
    $len = strlen($word);
    for ($i = 0; $i < $id_len; $i++) {
        $id = $id.$word[rand() % $len];
    }
    $a = array();
    for ($x = 0; $x < 1; $x++) {
        $b = $id;
        if (!in_array($b,$a)) {
            array_push($a,$b);
        } else {
            $x-=1;
        }
    }
    for ($x=0; $x < 1; $x++) {
        $session_id = $a[$x];
    }
    return $session_id;
}

/* Show msg */
function showMsg($error_type, $error_msg)
{
    $error_info = '<h1>'.$error_type.'</h1>'."\n";
    $error_info .= '<h2>'.$error_msg.'</h2>'."\n";
    return $error_info;
}

function generateFile($file, $content)
{
    $file = trimPath($file);
    try {
        if (file_exists($file) === true) {
            unlink($file);
        }
        file_put_contents($file, $content);
    } catch (Exception $e) {
        return $e->getMessage();
    }
    return true;
}

function createDatabaseTable(PDO $connectdb, $filePath)
{
    $templine = '';
    $lines = file($filePath);
    $error = '';
    // Loop through each line
    foreach ($lines as $line) {
        // Skip it if it's a comment
        if (substr($line, 0, 2) == '--' || $line == '') {
            continue;
        }
        // Add this line to the current segment
        $templine .= $line;
        // If it has a semicolon at the end, it's the end of the query
        if (substr(trim($line), -1, 1) == ';') {
            // Perform the query
            try {
                $connectdb->exec($templine);
            } catch (PDOException $e) {
                $error .= 'Error performing query "<b>'.$templine.'</b>": ' . $e->getMessage() . '<br /><br />';
            }
            // Reset temp variable to empty
            $templine = '';
        }
    }
    return (!empty($error)) ? $error : true;
}

function checkMroongaSupport($connectdb)
{
    $result = false;
    try {
        $stmt = $connectdb->query('SHOW STORAGE ENGINES');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row['Engine'] === 'Mroonga' && ($row['Support'] === 'YES' || $row['Support'] === 'DEFAULT')) {
                $result = true;
                break;
            }
        }
    } catch (PDOException $e) {
        showErrorMsg($e);
    }
    return $result;
}

function showErrorMsg($e)
{
    echo '<h1>Service unavailable</h1>'."\n";
    echo '<br/>';
    echo '<h2>Error Info :'.$e->getMessage().'</h2>';
    echo '<h3>Error Code :'.$e->getCode().'</h3>'."\n";
    echo '<h3>Error File :'.$e->getFile().'</h3>'."\n";
    echo '<h3>Error Line :'.$e->getLine().'</h3>'."\n";
}

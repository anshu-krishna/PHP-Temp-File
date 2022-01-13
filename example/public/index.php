<?php
namespace App;

use Krishna\TempFile;

require_once "../../vendor/autoload.php";

$tf = new TempFile(__DIR__ . '/../temp');
echo "<pre>File created: $tf->filename";

$tf->put_contents('temp info', LOCK_EX); // Write into file
echo "\n\nData written in file";

echo "\n\nDoing other things";
sleep(2); // Do some other work

echo "\n\nReading file; File contents:\n";
echo $tf->get_contents(); // Read file

echo "\n\nFile deleted";
<?php
require_once '../helper_functions_global.php';

$run_id = $_POST["run_id"];
$dir_path = "./output/$run_id/";
if (file_exists($dir_path)) {
    rrmdir($dir_path);
    rmdir($dir_path);
}

redirect_to("./");

<?php
$data = file_get_contents('php://input');
file_put_contents(__DIR__ . '/dom_log.txt', $data);
echo "OK";

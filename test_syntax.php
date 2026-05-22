<?php
$html = file_get_contents('rendered.html');
preg_match('/users:\s*\[(.*?)\]/s', $html, $matches);
if (empty($matches)) {
    echo "Could not find users array!\n";
    exit;
}
$array_str = '[' . $matches[1] . ']';
$array_str = html_entity_decode($array_str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
file_put_contents('extracted_users.js', 'const users = ' . $array_str . '; console.log("Parsed " + users.length + " users successfully!");');
echo "Extracted users to extracted_users.js\n";

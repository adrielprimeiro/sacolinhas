<?php
$dir = new RecursiveDirectoryIterator('.');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->getExtension() === 'php' && !str_contains($file->getPathname(), 'vendor')) {
        $content = file_get_contents($file->getPathname());
        $pos = strrpos($content, '?>');
        if ($pos !== false) {
            $after = substr($content, $pos + 2);
            if (trim($after) !== '') {
                if (str_contains($after, '<?php')) continue;
                echo "File: " . $file->getPathname() . " has content after LAST ?>\n";
                echo "Content (hex): [" . bin2hex(substr(trim($after), 0, 20)) . "]\n";
                echo "Content (text): [" . substr(trim($after), 0, 20) . "]\n";
            }
        }
    }
}

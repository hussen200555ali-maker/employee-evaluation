#!/usr/bin/env php
<?php
/**
 * Check application text files for valid UTF-8 encoding.
 * The bundled legacy third-party pChart source is excluded because its
 * comments/examples use a non-UTF-8 legacy charset; no application files are excluded.
 * Usage: php check-encoding.php --path=app
 */
$path = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--path=')) {
        $path = substr($arg, 7);
    }
}
if (!$path || !is_dir($path)) {
    fwrite(STDERR, "Usage: php check-encoding.php --path=<directory>\n");
    exit(2);
}

$basePath = realpath($path);
$extensions = ['php', 'html', 'htm', 'css', 'js', 'json', 'sql', 'md', 'txt', 'xml', 'yml', 'yaml', 'svg'];
$excludedPrefixes = ['Libraries/pChart/pchart/'];
$checked = 0;
$excluded = 0;
$invalid = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || !in_array(strtolower($file->getExtension()), $extensions, true)) {
        continue;
    }
    $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($basePath) + 1));
    foreach ($excludedPrefixes as $prefix) {
        if (str_starts_with($relativePath, $prefix)) {
            $excluded++;
            continue 2;
        }
    }
    $contents = file_get_contents($file->getPathname());
    if ($contents === false) {
        $invalid[] = $relativePath . ' (تعذرت القراءة)';
        continue;
    }
    $checked++;
    if (str_contains($contents, "\0") || preg_match('//u', $contents) !== 1) {
        $invalid[] = $relativePath;
    }
}

if ($invalid) {
    fwrite(STDERR, "Invalid UTF-8 encoding found in:\n" . implode("\n", $invalid) . "\n");
    exit(1);
}

echo "UTF-8 encoding check passed for {$checked} application text files under {$path}; excluded {$excluded} legacy third-party pChart files.\n";
exit(0);

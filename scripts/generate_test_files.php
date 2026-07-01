<?php

$from = __DIR__.'/../'.$argv[1];
$to = __DIR__.'/../'.$argv[2];

try {
    exec("cp -r {$from} {$to}");

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($to));

    foreach ($files as $file) {
        $fileName = $file->getFileName();
        $filePath = $file->getRealPath();

        if (! str_contains($fileName, '.php')) {
            continue;
        }

        file_put_contents($filePath, "<?php\n");

        $newName = str_replace('.php', 'Test.php', $fileName);

        $directory = dirname($filePath);
        $newFilePath = $directory.'/'.$newName;

        rename($filePath, $newFilePath);
    }
} catch (throwable $e) {
    echo $e->getMessage().PHP_EOL;
    exec("rm  -rf  {$to}");
}

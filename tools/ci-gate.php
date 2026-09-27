#!/usr/bin/env php
<?php

/**
 * Gate de CI: falla solo si el número de tests rotos supera el umbral permitido.
 *
 * Uso: php tools/ci-gate.php junit.xml [umbral]
 *   - umbral 0 (por defecto) => cualquier fallo bloquea
 *   - umbral N                => tolera hasta N fallos+errores
 *
 * Los tests de dinero/stock NO pasan por aquí: se ejecutan aparte sin tolerancia.
 */
$file = $argv[1] ?? 'junit.xml';
$threshold = (int) ($argv[2] ?? 0);

if (! is_file($file)) {
    fwrite(STDERR, "❌ No se generó el reporte JUnit: {$file}\n");
    exit(1);
}

$xml = @simplexml_load_file($file);
if ($xml === false) {
    fwrite(STDERR, "❌ El reporte JUnit no es XML válido: {$file}\n");
    exit(1);
}

$cases = $xml->xpath('//testcase') ?: [];
$tests = count($cases);
$failures = 0;
$errors = 0;
$skipped = 0;

foreach ($cases as $case) {
    foreach ($case->children() as $child) {
        match ($child->getName()) {
            'failure' => $failures++,
            'error' => $errors++,
            'skipped' => $skipped++,
            default => null,
        };
    }
}

$broken = $failures + $errors;

printf(
    "Resultado: %d tests | %d fallos | %d errores | %d omitidos | umbral permitido: %d\n",
    $tests,
    $failures,
    $errors,
    $skipped,
    $threshold
);

if ($tests === 0) {
    fwrite(STDERR, "❌ No se ejecutó ningún test. Se considera fallo.\n");
    exit(1);
}

if ($broken > $threshold) {
    fwrite(STDERR, "❌ {$broken} tests rotos superan el umbral de {$threshold}.\n");
    exit(1);
}

echo "✅ Dentro del umbral permitido.\n";
exit(0);

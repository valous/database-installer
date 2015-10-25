# Database Installer

Skript pro automatické aktualizace databáze z migrací

## Použití

```php

use Valous\DatabaseInstaller\Engine;

/**
 * $dbFilesDir - Cesta ke složce s migracemi
 * $ymlFilePath - Cesta k souboru s historií migrací
 */
$engine = new Engine(PDO $pdo, $dbFilesDir, $ymlFilePath);

// Aktualizace databáze postupným nahráváním nových migrací
$engine->update();

// Instalace pouze určitého souboru
// $sqlFilePath - Cesta k souboru
$engine->install($sqlFilePath);

```

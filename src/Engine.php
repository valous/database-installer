<?php

namespace Valous\DatabaseInstaller;

use PDO;
use PDOException;
use DirectoryIterator;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;


/**
 * @author David Valenta
 */
class Engine
{
    /** @var string */
    private $dbFilesDir;

    /** @var string */
    private $ymlFilePath;

    /** @var array */
    private $successMigration;

    /** @var PDO */
    private $pdo;


    /**
     * @param PDO $pdo
     * @param string $dbFilesDir
     * @param string $ymlFilePath
     */
    public function __construct(PDO $pdo, $dbFilesDir, $ymlFilePath)
    {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $this->pdo = $pdo;
        $this->dbFilesDir = (string) $dbFilesDir;
        $this->ymlFilePath = (string) $ymlFilePath;

        if (!file_exists($ymlFilePath)) {
            file_put_contents($ymlFilePath, '');
            $this->successMigration = [];
        } else {
            $yml = new Parser();
            $this->successMigration = $yml->parse(file_get_contents($ymlFilePath));
        }
    }


    /**
     */
    public function update()
    {
        echo 'Update Migrations: ';

        $dirIterator = new DirectoryIterator($this->dbFilesDir);
        $installed = [];
        foreach ($dirIterator as $dir) {
            $file = $dir->getFilename();
            if ($file == '.' || $file == '..' || !preg_match('/\.sql$/', $file)) {
                continue;
            }

            if (in_array($file, $this->successMigration)) {
                $installed[] = $file;
                continue;
            }

            if ($this->install($dir->getPathname())) {
                $installed[] = $file;
                echo PHP_EOL . $file . ': Success';
            } else {
                echo PHP_EOL . $file . ': Fail';
                break;
            }
        }

        $dumper = new Dumper();
        file_put_contents($this->ymlFilePath, $dumper->dump($installed));
    }


    /**
     * @param string $sqlFilePath
     * @return bool
     */
    public function install($sqlFilePath)
    {
        $sql = file_get_contents($sqlFilePath);
        $sqls = explode(';', $sql);

        try {
            $this->pdo->beginTransaction();

            foreach ($sqls as $sql) {
                if (preg_match('/^(\s)*$/', $sql)) {
                    continue;
                }
                $this->pdo->exec($sql);
            }

            if ($this->pdo->errorCode() === '00000') {
                $this->pdo->commit();
                return true;
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}

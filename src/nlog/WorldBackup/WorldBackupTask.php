<?php
/**
 * Created by PhpStorm.
 * User: nlog
 * Date: 2018-10-14
 * Time: 오후 4:03
 */

namespace nlog\WorldBackup;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class WorldBackupTask extends AsyncTask {

    const WORLD_NAME = "world";

    /** @var string */
    private $worldPath;

    public function __construct(string $worldPath) {
        $this->worldPath = $worldPath;
    }

    public function onRun(): void {
        $this->setResult(false);
        $archive = new \ZipArchive();
        if (file_exists($path = ($this->worldPath . self::WORLD_NAME . date("_Y_m_d_G") . ".zip"))) {
            @unlink($path);
        }
        if ($archive->open($path, \ZipArchive::CREATE)) {
            chdir($this->worldPath);
            $this->makeZip(self::WORLD_NAME . DIRECTORY_SEPARATOR, $archive);
            if ($archive->close()) {
                $this->setResult($path);
            }
        }
    }
    public function onCompletion(): void {
        if ($this->getResult() !== false) {
            Server::getInstance()->getLogger()->notice("백업 파일이 '{$this->getResult()}'에 저장되었습니다.");
            Loader::cleanBackupFile();
        }else{
            Server::getInstance()->getLogger()->alert("백업이 실패하였습니다.");
        }
    }

    private function makeZip(string $path, \ZipArchive $archive): void {
        foreach (scandir($path) as $index => $file) {
            if ($file === "." || $file === ".." || !file_exists($path . $file)) {
                continue;
            }
            if (is_dir($path . $file)) {
                $archive->addEmptyDir($path . $file . DIRECTORY_SEPARATOR);
                $this->makeZip($path . $file . DIRECTORY_SEPARATOR, $archive);
            } elseif (is_file($path . $file)) {
                $archive->addFile($path . $file);
            }
        }
    }

}
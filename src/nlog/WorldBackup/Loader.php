<?php
/**
 * Created by PhpStorm.
 * User: NLOG
 * Date: 2018-02-20
 * Time: 오후 1:16
 */

namespace nlog\WorldBackup;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class Loader extends PluginBase {

    public static function cleanBackupFile() {
        $files = [];
        // 월드 폴더를 스캔합니다.
        foreach (scandir($path = Server::getInstance()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR) as $file) {
            if (
                    is_file($path . $file) && //파일이면서
                    substr($file, -3) === "zip" && //학장자가 zip 이면서
                    substr($file, 0, strlen(WorldBackupTask::WORLD_NAME)) === WorldBackupTask::WORLD_NAME
                // 백업할 월드명과 일치하는 파일을 추출합니다.
            ) {
                $files[] = $path . $file;
            }
        }

        if (count($files) > ($limit = 12)) {
            foreach ($files as $file) {
                $fileList[filemtime($file)] = $file;
            }
            ksort($fileList);

            while (count($fileList) > $limit) {
                @unlink(array_shift($fileList));
            }
        }
    }

    public function onLoad() {
        date_default_timezone_set('Asia/Seoul');
        self::cleanBackupFile();
    }

    public function onEnable() {
        $this->getScheduler()->scheduleDelayedRepeatingTask(new class($path) extends Task {
            private $path;

            public function __construct($path) {
                $this->path = $path;
            }

            public function onRun(int $currentTick) {
                /*if (($level = Server::getInstance()->getLevelByName(WorldBackupTask::WORLD_NAME)) instanceof Level) {
                    $level->save(true); // zip 압축 전 월드를 저장합니다.
                }*/

                Server::getInstance()->getAsyncPool()->submitTask(new WorldBackupTask($this->path));
            }
        }, $this->nextTime() * 20, 60 * 60 * 8 * 20);
    }

    public function nextTime() {
        $hour = intval(date("G"));
        $min = intval(date("i"));
        if ($hour === 0 && $min === 0) {
            return 0;
        } elseif ($hour === 8 && $min === 0) {
            return 0;
        } elseif ($hour === 16 && $min === 0) {
            return 0;
        } elseif ($hour < 8) {
            return mktime(8) - time();
        } elseif ($hour < 16) {
            return mktime(16) - time();
        } else {
            return strtotime('tomorrow') - time();
        }
    }
}
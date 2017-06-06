<?php

namespace QuickAsync;

/**
 * Class MultiProcess
 * @package QuickAsync
 */
class MultiProcess
{
    /**
     * @var string
     */
    protected $basePath;
    protected $holdCount = 0;
    protected $goCount = 0;

    /**
     * MultiProcess constructor.
     * @param null $runPath
     */
    public function __construct($runPath = null)
    {
        if (isset($runPath)) {
            Setup::setRunPath($runPath);
        }
        $this->basePath = Setup::getBasePath();
        $this->runPath = Setup::getRunPath();
    }

    /**
     * @return array|bool
     */
    public function collect()
    {

        $files = glob($this->basePath . '*.async');

        if (count($files) == 0) {
            return false;
        }

        usort($files, function ($a, $b) {
            return filemtime($a) < filemtime($b);
        });

        return $files;
    }

    /**
     * @return array|bool
     */
    public function getFileNames()
    {
        $files = $this->collect();
        if (!$files) {
            return false;
        }
        $returnArray = array();
        $i = 0;
        foreach ($files as $file) {
            $i++;
            $fileExplode = explode('/', $file);
            $returnArray[] = end($fileExplode);
            if ($i > 3000) {
                break;
            }

        }
        return $returnArray;
    }

    /**
     * @param int $sleep
     * @param int $killTime
     * @param \Closure|null $processContinueCheck
     * @return int
     */
    public function process($sleep = 2, $killTime = 0, $processContinueCheck = null)
    {
        $this->showRunning();
        $start = time();
        $processesRun = 0;
        $i = 0;
        while (true) {
            $files = $this->getFileNames();
            if ($files) {
                foreach ($files as $file) {
                    $hold = false;
                    if (isset($processContinueCheck) && is_callable($processContinueCheck)) {
                        $hold = $this->holdProcess($processContinueCheck, $file);
                    }
                    if (!$hold) {
                        $processScript = str_replace('%%file%%', $file, $this->runPath);
                        $cron = "php {$processScript} >/dev/null 2>/dev/null &";
                        echo "\n" . $cron;
                        shell_exec($cron);
                        $processesRun++;
                        usleep(50000);
                    }
                    $now = time();
                    if ((($now - $start) >= $killTime) && $killTime != 0) {
                        die('Processing time finished!');
                    }
                }
            }
            sleep($sleep);
            $now = time();
            if ((($now - $start) >= $killTime) && $killTime != 0) {
                die('Processing time finished!');
            }

        }

        return $processesRun;

    }

    protected function holdProcess($processContinueCheck, $file)
    {

        $continue = $processContinueCheck($file);
        if (is_bool($continue) && !$continue) {
            $this->holdCount++;
            return true;
        }
        $this->goCount++;
        if ($this->goCount % 10 == 0) {
            sleep(2);
        }

        return false;
    }

    protected function showRunning()
    {
        $path = Setup::getBasePath() . 'multi-process.runningAsync';
        file_put_contents($path, '-*-');
    }

    public static function isRunning($ttl = 120)
    {
        $path = Setup::getBasePath() . 'multi-process.runningAsync';
        if (file_exists($path)) {
            $fileTime = filemtime($path);
            $time = time() - $fileTime;
            if ($time < $ttl) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getHoldCount()
    {
        return $this->holdCount;
    }

}
<?php
namespace QuickAsync;

/**
 * Class LonelyProcess
 * @package QuickAsync
 */
class LonelyProcess
{
    /**
     * @var bool
     */
    protected $started = false;
    protected $path = false;

    public function getPathFromProcessName($processName = false)
    {
        if ($processName === false) {
            return false;
        }
        $search = array('.async', '.lonelysync');
        $processName = str_replace($search, '', $processName);
        $path = setup::getBasePath() . $processName . '.lonelysync';
        return $path;
    }

    public function exists($processName)
    {
        $this->path = $this->getPathFromProcessName($processName);
        if ($this->path === false) {
            throw new AsyncException('No Path Set');
            return false;
        }
        if (file_exists($this->path)) {
            return true;
        }
        return false;
    }

    public function start($processName = false)
    {
        if (!$this->exists($processName)) {
            $put = file_put_contents($this->path, '-*-');

            if ($put) {
                $this->started = true;
                return true;
            }
        }
        $this->started = false;
        return false;

    }

    public function end()
    {
        if (!isset($this->path)) {
            return false;
        }
        if (file_exists($this->path) && $this->isStarted()) {
            $unlink = unlink($this->path);
            if ($unlink) {
                return true;
            }
        }
        return false;
    }

    public function isStarted()
    {
        return $this->started;
    }

    public function getPath()
    {
        return $this->path;
    }

}
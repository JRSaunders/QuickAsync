<?php

namespace QuickAsync;


class QueueItem
{
    /**
     * @var object
     */
    protected $asyncData;
    /**
     * @var string|bool
     */
    protected $itemProcessName;
    /**
     * @var LonelyProcess|false
     */
    protected $lonelyProcess;

    /**
     * QueueItem constructor.
     * @param $asyncProcessName
     */
    public function __construct($asyncProcessName)
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->getItem($asyncProcessName);
    }

    /**
     * @param $asyncProcessName
     * @return mixed|object
     */
    public function getItem($asyncProcessName)
    {

        if (isset($this->asyncData)) {
            return $this->getObject();
        }
        $asyncProcessName = str_replace('.async', '', $asyncProcessName) . '.async';
        $this->setItemProcessName($asyncProcessName);
        $path = Setup::getBasePath() . $asyncProcessName;
        if (file_exists($path)) {
            $data = file_get_contents($path);
            $this->asyncData = unserialize($data);
        }

        return $this->asyncData;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->asyncData;
    }

    /**
     * @param null $vars
     * @return mixed
     */
    public function run($vars = null)
    {
        if (!is_callable(array($this->asyncData, 'runAsync'))) {
            throw new AsyncException('runAsync method not present!');
            return false;
        }
        $item = $this->asyncData;
        $this->startLonelyProcess(function () use ($item, $vars) {
            return $item->runAsync($vars);
        });
        $this->endLonelyProcess();

    }

    /**
     * @param null|\Closure $runAsyncCallback
     * @return bool
     */
    public function startLonelyProcess($runAsyncCallback = null)
    {
        if (!is_callable($runAsyncCallback)) {
            return false;
        }
        if (!is_callable(array($this->asyncData, 'isProcessLonely'))) {
            throw new AsyncException('isProcessLonely method not present!');
            return false;
        }
        if ($this->asyncData->isProcessLonely()) {
            $this->lonelyProcess = new LonelyProcess();

            $this->lonelyProcess->start($this->getItemProcessName());

            if ($this->lonelyProcess->isStarted()) {
                $runAsyncCallback();
            }

            return true;
        }
        $runAsyncCallback();
        return false;
    }

    /**
     * @return bool
     */
    public function endLonelyProcess()
    {
        if ($this->getLonelyProcess()) {

            $this->getLonelyProcess()->end();

            return true;
        }
        return false;
    }

    /**
     * @return string| bool
     */
    public function getItemProcessName()
    {
        if (isset($this->itemProcessName) && is_string($this->itemProcessName)) {
            return $this->itemProcessName;
        }
        return false;
    }

    /**
     * @param string $itemProcessName
     */
    public function setItemProcessName($itemProcessName)
    {
        $this->itemProcessName = $itemProcessName;
    }

    /**
     * @return LonelyProcess|false
     */
    public function getLonelyProcess()
    {
        if ($this->lonelyProcess instanceof LonelyProcess) {
            return $this->lonelyProcess;
        }
        return false;
    }
}
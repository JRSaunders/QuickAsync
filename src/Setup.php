<?php

namespace QuickAsync;

/**
 * Class Setup
 * @package QuickAsync
 */
class Setup
{
    /**
     * @var string
     */
    protected static $basePath = '';
    /**
     * @var string
     */
    protected static $runPath = '';
    /**
     * @return string
     */
    public static function getBasePath()
    {
        return rtrim(self::$basePath, '/') . '/';
    }

    /**
     * @param string $basePath
     */
    public static function setBasePath($basePath)
    {
        self::$basePath = $basePath;
    }

    /**
     * @return string
     */
    public static function getRunPath()
    {
        return self::$runPath;
    }

    /**
     * @param string $runPath
     * use %%file%% as replacement value
     */
    public static function setRunPath($runPath)
    {
        self::$runPath = $runPath;
    }


}
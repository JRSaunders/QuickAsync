<?php

namespace QuickAsync;

/**
 * Class QuickAsync
 * @package QuickAsync
 */
class Item {
	/**
	 * @var string
	 */
	public $asyncMethod;
	/**
	 * @var array
	 */
	public $asyncArgs;
	/**
	 * @var string
	 */
	public $asyncClass;
	/**
	 * @var string
	 */
	public $asyncQueue;
	/**
	 * @var string
	 */
	public $asyncInclude;
	/**
	 * @var object
	 */
	public $asyncInstance;
	/**
	 * @var object
	 */
	public $preVals;
	/**
	 * @var bool
	 */
	public $asyncNoRepeatWhileProcessing;

	/**
	 * @var \QuickCache\Cache
	 */
	public $cache = null;
	/**
	 * @var string
	 */
	public $cacheName;
	/**
	 * @var string
	 */
	public $cachePath = '';

	public $asyncProcessName = '';

	/**
	 * QuickAsync constructor.
	 *
	 * @param $vals
	 * @param $class
	 * @param $method
	 * @param $args
	 * @param $include
	 */
	public function __construct( $vals, $class, $method, $args, $include ) {
		$this->asyncMethod  = $method;
		$this->asyncArgs    = $args;
		$this->asyncClass   = $class;
		$this->asyncInclude = $include;
		$this->setPreVals( $vals );

	}

	/**
	 * @param null $asyncProcessName
	 * @param bool $oneInTheQueue
	 * @param bool $noRepeatWhileProcessing
	 *
	 * @return int
	 */
	public function queue( $asyncProcessName = null, $oneInTheQueue = false, $noRepeatWhileProcessing = false ) {
		if ( ! isset( $asyncProcessName ) ) {
			$asyncProcessName = rand( 1234, 50000 ) . time();
		}
		if ( $oneInTheQueue == false ) {
			$asyncProcessName = $asyncProcessName . rand( 1234, 50000 ) . time();
		}
		$this->asyncNoRepeatWhileProcessing = $noRepeatWhileProcessing;
		$canQueue                           = $this->canQueue( $asyncProcessName );
		$this->setAsyncProcessName( $asyncProcessName );
		if ( ! $canQueue ) {
			return false;
		}

		$path = Setup::getBasePath() . $asyncProcessName . '.async';

		$this->asyncQueue = $path;
		$instance         =& $this;
		$a                = serialize( $instance );

		return file_put_contents( $this->asyncQueue, $a );
	}

	protected function canQueue( $asyncProcessName ) {
		if ( $this->isProcessLonely() ) {
			$lonelyProcess = new LonelyProcess();
			$exists        = $lonelyProcess->exists( $asyncProcessName );
			if ( $exists ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param $vals
	 */
	public function setPreVals( $vals ) {
		$this->preVals = new \stdClass();
		foreach ( $vals as $key => $val ) {
			if ( ! isset( $this->preVals->{$key} ) ) {
				if ( $val instanceof \stdClass || is_array( $val ) || is_string( $val ) || is_numeric( $val ) || is_null( $val ) ) {
					$this->preVals->{$key} = $val;
				}
			}
		}
	}

	/**
	 * @param null $vals
	 */
	public function setPostVals( $vals = null ) {
		require_once( $this->asyncInclude );
		$this->asyncInstance = new $this->asyncClass();
		if ( $vals !== null ) {
			foreach ( $vals as $key => $val ) {
				if ( ! isset( $this->asyncInstance->{$key} ) ) {
					$this->asyncInstance->{$key} = $val;
				}
			}
		}
	}

	/**
	 * @param null $vals
	 *
	 * @return mixed|null
	 */
	public function runAsync( $vals = null ) {
		$data = null;
		$this->setPostVals( $vals );
		if ( file_exists( $this->asyncQueue ) ) {
			unlink( $this->asyncQueue );
			$data = $this->execute();
			$this->runDataCaching( $data );
		}

		return $data;
	}

	protected function runDataCaching( $data ) {
		if ( isset( $this->cache ) ) {
			$this->cache->setCachePath( $this->getCachePath() );
			$this->cache->saveToCache( $this->cacheName, $data );
		}
	}

	/**
	 * @return mixed
	 */
	protected function execute() {
		return call_user_func_array( array( $this->asyncInstance, $this->asyncMethod ), $this->asyncArgs );
	}

	/**
	 * @param null $vals
	 *
	 * @return mixed
	 */
	public function executeNow( $vals = null ) {
		$this->setPostVals( $vals );

		return $this->execute();
	}

	/**
	 * @return bool
	 */
	public function isProcessLonely() {
		if ( is_bool( $this->asyncNoRepeatWhileProcessing ) ) {
			return $this->asyncNoRepeatWhileProcessing;
		}

		return false;
	}

	/**
	 * @param \QuickCache\Cache $cache
	 */
	public function setCache( \QuickCache\Cache $cache, $cacheName ) {
		$this->cache     = $cache;
		$this->cacheName = $cacheName;
	}

	/**
	 * @return string
	 */
	public function getCachePath() {
		return $this->cachePath;
	}

	/**
	 * @param string $cachePath
	 */
	public function setCachePath( $cachePath ) {
		$this->cachePath = $cachePath;
	}

	/**
	 * @return string
	 */
	public function getAsyncProcessName() {
		return $this->asyncProcessName;
	}

	/**
	 * @param string $asyncProcessName
	 */
	public function setAsyncProcessName( $asyncProcessName ) {
		$this->asyncProcessName = $asyncProcessName;
	}


}
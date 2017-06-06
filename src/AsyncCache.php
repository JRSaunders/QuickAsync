<?php

namespace QuickAsync;


class AsyncCache {
	/**
	 * @var Item
	 */
	protected $item = null;
	/**
	 * @var \QuickCache\Cache
	 */
	protected $cache = null;
	/**
	 * @var string
	 */
	protected $asyncName = '';
	/**
	 * @var string
	 */
	protected $cacheName = '';
	/**
	 * @var int
	 */
	protected $ttl = 1200;
	/**
	 * @var int
	 */
	protected $catchCacheSleep = 3;
	/**
	 * @var string
	 */
	protected $cachePath = '/';
	/**
	 * @var null|object
	 */
	protected $vals;
	/**
	 * @var null|array|\stdClass
	 */
	protected $data;
	/**
	 * @var bool
	 */
	protected $noDataProcessNow = true;
	/**
	 * @var int
	 */
	protected $catchCacheTimeout = 90;
	/**
	 * @var int
	 */
	protected $dataTime = 0;
	/**
	 * @var null | \Closure
	 */
	protected $catchCallback = null;

	/**
	 * AsyncCache constructor.
	 *
	 * @param $vals
	 * @param $asyncName
	 * @param $cacheName
	 * @param $ttl
	 * @param Item $item
	 * @param \QuickCache\Cache $cache
	 * @param int $catchCacheSleep
	 * @param bool $noDataProcessNow
	 * @param int $catchCacheTimeout
	 * @param null $catchCallback
	 */
	public function __construct(
		$vals,
		$asyncName, $cacheName, $ttl,
		Item $item,
		\QuickCache\Cache $cache,
		$catchCacheSleep = 3,
		$noDataProcessNow = true,
		$catchCacheTimeout = 90,
		$catchCallback = null
	) {
		$this->setVals( $vals );
		$this->setAsyncName( $asyncName );
		$this->setCacheName( $cacheName );
		$this->setTtl( $ttl );
		$this->setItem( $item );
		$this->setCache( $cache );
		$this->setCacheToItem();
		$this->setNoDataProcessNow( $noDataProcessNow );
		$this->setCatchCacheTimeout( $catchCacheTimeout );
		$this->setCatchCallback( $catchCallback );
	}

	/**
	 * @return bool
	 */
	public function isNoDataProcessNow() {
		return $this->noDataProcessNow;
	}

	/**
	 * @param bool $noDataProcessNow
	 */
	public function setNoDataProcessNow( $noDataProcessNow ) {
		$this->noDataProcessNow = $noDataProcessNow;
	}

	protected function setCacheToItem() {
		$item = $this->getItem();
		$item->setCache( $this->getCache(), $this->getCacheName() );
		$item->setCachePath( $this->getCache()->getCachePath() );
	}

	/**
	 * @param Item $item
	 */
	public function setItem( Item $item ) {
		$this->item = $item;
	}


	public function getData() {
		if ( isset( $this->data ) ) {
			return $this->data;
		}
		/**
		 * Queue Item
		 */
		$item      = $this->getItem();
		$asyncName = $this->getAsyncName();
		$queue     = $item->queue( $asyncName, true, true );
		/**
		 * get cache
		 */
		$cache = $this->getCache();
		/**
		 * get old cache
		 */
		$data = $cache->getOldCacheData( $this->getCacheName() );
		if ( $data ) {
			$this->setDataTime( $cache->getOldTime() );
		}
		if ( ! $data && $queue ) {
			if ( $this->isNoDataProcessNow() ) {
				$data = $this->executeItemNow();
			} else {
				$data = $this->catchCache( $this->getCatchCacheSleep() );
			}
			$this->setDataTime( time() );
		} elseif ( ! $queue && ! $data ) {
			$data = $this->catchCache( $this->getCatchCacheSleep() );
			$this->setDataTime( time() );
		}

		return $this->data = $data;
	}

	protected function catchCache( $sleep = 3 ) {
		$timeTakenCallback = $this->getCatchCallback();
		$asyncMultiProcess = MultiProcess::isRunning();
		if ( ! $asyncMultiProcess ) {
			return $this->executeItemNow();
		}
		$cache = $this->getCache();
		$start = time();
		while ( true ) {
			$data = $cache->getCacheData( $this->getCacheName(), $this->getTtl() );
			if ( $data ) {
				return $data;
			}
			sleep( $sleep );
			$timeTaken = time() - $start;
			if ( is_callable( $timeTakenCallback ) ) {
				$timeTakenCallback( $timeTaken );
			}
			if ( $timeTaken >= $this->catchCacheTimeout ) {
				return $this->executeItemNow();
			}
		}
	}

	public function executeItemNow( $cacheNow = true ) {
		$item = $this->getItem();
		$data = $item->executeNow( $this->getVals() );
		if ( $cacheNow ) {
			$cache = $this->getCache();
			$cache->saveToCache( $this->getCacheName(), $data );
		}

		return $data;
	}

	/**
	 * @return \QuickCache\Cache
	 */
	public function getCache() {
		return $this->cache;
	}

	/**
	 * @param \QuickCache\Cache $cache
	 */
	public function setCache( \QuickCache\Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * @return Item
	 */
	public function getItem() {
		return $this->item;
	}


	/**
	 * @return string
	 */
	public function getAsyncName() {
		return $this->asyncName;
	}

	/**
	 * @param string $asyncName
	 */
	public function setAsyncName( $asyncName ) {
		$this->asyncName = $asyncName;
	}

	/**
	 * @return string
	 */
	public function getCacheName() {
		return $this->cacheName;
	}

	/**
	 * @param string $cacheName
	 */
	public function setCacheName( $cacheName ) {
		$this->cacheName = $cacheName;
	}

	/**
	 * @return int
	 */
	public function getTtl() {
		return $this->ttl;
	}

	/**
	 * @param int $ttl
	 */
	public function setTtl( $ttl ) {
		$this->ttl = $ttl;
	}

	/**
	 * @return int
	 */
	public function getCatchCacheSleep() {
		return $this->catchCacheSleep;
	}

	/**
	 * @param int $catchCacheSleep
	 */
	public function setCatchCacheSleep( $catchCacheSleep ) {
		$this->catchCacheSleep = $catchCacheSleep;
	}

	/**
	 * @return mixed
	 */
	public function getVals() {
		return $this->vals;
	}

	/**
	 * @param mixed $vals
	 */
	public function setVals( $vals ) {
		$this->vals = $vals;
	}

	/**
	 * @return string
	 */
	public function getCachePath() {
		return $this->cachePath;
	}

	/**
	 * @return int
	 */
	public function getCatchCacheTimeout() {
		return $this->catchCacheTimeout;
	}

	/**
	 * @param int $catchCacheTimeout
	 */
	public function setCatchCacheTimeout( $catchCacheTimeout ) {
		$this->catchCacheTimeout = $catchCacheTimeout;
	}

	/**
	 * @return int
	 */
	public function getDataTime() {
		return $this->dataTime;
	}

	/**
	 * @param int $dataTime
	 */
	public function setDataTime( $dataTime ) {
		$this->dataTime = $dataTime;
	}

	/**
	 * @return \Closure|null
	 */
	public function getCatchCallback() {
		return $this->catchCallback;
	}

	/**
	 * @param \Closure|null $catchCallback
	 */
	public function setCatchCallback( $catchCallback ) {
		$this->catchCallback = $catchCallback;
	}

}
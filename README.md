# QuickAsync
**Quick Async Process Library**

[![Latest Stable Version](https://poser.pugx.org/jrsaunders/quickasync/v/stable)](https://packagist.org/packages/jrsaunders/quickasync)
[![Total Downloads](https://poser.pugx.org/jrsaunders/quickasync/downloads)](https://packagist.org/packages/jrsaunders/quickasync)
[![Latest Unstable Version](https://poser.pugx.org/jrsaunders/quickasync/v/unstable)](https://packagist.org/packages/jrsaunders/quickasync)
[![License](https://poser.pugx.org/jrsaunders/quickasync/license)](https://packagist.org/packages/jrsaunders/quickasync)

Install via composer

```$composer require jrsaunders/quickasync```

**Setup**

Put this in an index or core class.  This will tell your Application where to store its async processes.

```
<?php
use QuickAsync\Setup;

Setup::setBasePath( '/var/www/vhost/some/dir/async' );

```


*Cron Job to Process Async Jobs*

```
<?php

$multiProcess = new \QuickAsync\MultiProcess(BASEPATH . '../cron.php /async/run/%%file%%');
$sleepBetweenCollections = 1;
$killMultiProcessCollectionsAfterSeconds = 60;
$multiProcess->process($sleepBetweenCollections, $killMultiProcessCollectionsAfterSeconds);

```

*Async Job Process*
```
<?php

class Async extends MyApp {
	public function run( $asyncFile = null ) {
		if ( isset( $asyncFile ) ) {
			$queueItem = new \QuickAsync\QueueItem( $asyncFile );
			$asyncData = $queueItem->getObject();
		    $queueItem->run( $this );
		}
	}


}

```

**Simple Use**

```
<?php
use QuickAsync\Item;

class CoolClass extends MyApp(){

  public function somethingCool($name,$carName){
      
      //save this car to my name or something like that do some complicated processing.....
      return $importantData;
  }
  
  public function triggerAsyncProcess(){
      $asyncItem = new Item($this,'CoolClass','somethingCool',['John','Lamborghini'],__FILE__);
      asyncItem->queue();
  
  }


}


```

**Use with QuickCache**

https://github.com/JRSaunders/QuickCache get setup instructions for QuickCache from here.

```
<?php
use QuickAsync\Item;
use QuickAsync\AsyncCache;
use QuickCache\Cache;

class CoolClass extends MyApp(){

  public function somethingCool($name,$carName){
      
      //save this car to my name or something like that do some complicated processing.....
       return $importantData;
  }
  
  public function getData(){
  
      $cacheName = md5('MyUniqueName');
      $ttl = 120;//120 seconds
      $cache = new Cache();
      $cachedData = $cache->getCacheData( $cacheName, $ttl );
      
      //if there is no cache data go get some from an extenral process and then wait for the result and pick it up.
      //if it takes to long do something else. but job carries on none the less in the background.
      
      if(!cachedData){
        $asyncItem = new Item($this,'CoolClass','somethingCool',['John','Lamborghini'],__FILE__);
        $asyncCache = new AsyncCache(
          $this,
          $cacheName,
          $cacheName,
          $ttl,
          $asyncItem,
          $cache,
          1,
          false,
          $catchCacheTimeout,
          function ( $t ) {
            if ( $t > 20 ) {
              if ( ! defined( 'RUNNINGFROMCRON' ) ) {
                header( 'Location: /admin/dashboard?flash=' .
                        urlencode( 'Try back in a minute your car is currently building! <a href="' . $_SERVER['REQUEST_URI'] . '"> Try back </a>' )
                );
                exit();
              }
            }

          }
        );

        $cachedData = $asyncCache->getData();
      }
      
      return $cachedData;
  
  }


}


```


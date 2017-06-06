<?php

namespace QuickAsync;


class ProcessBalance {
	/**
	 * @var callable|null
	 */
	protected $StatTrackMatchFunction = null;

	/**
	 * @return mixed
	 */
	public function getStatTrackMatch() {
		return call_user_func( $this->getStatTrackMatchFunction() );
	}

	/**
	 * @return mixed
	 */
	public function getStatTrackMatchFunction() {
		return $this->StatTrackMatchFunction;
	}

	/**
	 * @param mixed $StatTrackMatchFunction
	 */
	public function setStatTrackMatchFunction( $StatTrackMatchFunction ) {
		$this->StatTrackMatchFunction = $StatTrackMatchFunction;
	}
}
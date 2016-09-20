<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Min
 * @subpackage Storages
 */
class CApiDavStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('dav', $sStorageName, $oManager);
	}
}
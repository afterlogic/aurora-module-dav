<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Min
 * @subpackage Storages
 */
class CApiDavDbStorage extends CApiDavStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiDavCommandCreatorMySQL
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiDavCommandCreatorMySQL'
			)
		);
	}
}

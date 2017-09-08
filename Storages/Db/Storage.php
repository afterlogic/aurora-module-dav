<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Dav\Storages\Db;

class Storage extends \Aurora\Modules\Dav\Storages\Storage
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
	 * 
	 * @param \Aurora\System\Managers\AbstractManager $oManager
	 */
	public function __construct(\Aurora\System\Managers\AbstractManager &$oManager)
	{
		parent::__construct($oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator = new CommandCreator\MySQL();
	}
}

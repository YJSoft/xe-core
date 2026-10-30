<?php
namespace Codeception\Module;

class DbDropTablesHelper extends \Codeception\Module\Db
{
    public function cleanup()
    {
        $driver = method_exists($this, '_getDriver') ? $this->_getDriver() : $this->driver;
        $dbh = $driver->getDbh();
        if (!$dbh) {
            throw new ModuleConfigException(
                __CLASS__,
                "No connection to database. Remove this module from config if you don't need database repopulation"
            );
        }

        try {
            $driver->cleanup();
        } catch (\Exception $e) {
            throw new ModuleException(__CLASS__, $e->getMessage());
        }
    }
}

<?php
namespace Codeception\Module;

use Codeception\TestInterface;
use Codeception\Util\FileSystem;

class InstallHelper extends \Codeception\Module
{
    public function _before(TestInterface $test)
    {
        FileSystem::deleteDir('files');
    }
}

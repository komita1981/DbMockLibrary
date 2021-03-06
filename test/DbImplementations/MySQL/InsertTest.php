<?php
namespace Test\DbImplementations\MySQL;

use DbMockLibrary\DbImplementations\MySQL;

class InsertTest extends \Test\TestCase
{
    /**
     * @var \PDO $pdo
     */
    protected $pdo;

    public function setUp()
    {
        if (is_null($this->pdo)) {
            $this->pdo = new \PDO('mysql:host=localhost;', 'root', '');
        }

        $stmt = $this->pdo->prepare('DROP DATABASE IF EXISTS `DbMockLibraryTest`');
        $stmt->execute();

        $stmt = $this->pdo->prepare('CREATE DATABASE `DbMockLibraryTest`');
        $stmt->execute();

        $stmt = $this->pdo->prepare('CREATE TABLE IF NOT EXISTS DbMockLibraryTest.testTable (`id` INT, `foo` INT, PRIMARY KEY (`id`))');
        $stmt->execute();

        MySQL::initMySQL(['testTable' => [1 => ['foo' => 0, 'id' => 0]]], 'localhost', 'DbMockLibraryTest', 'root', '', []);
    }

    public function tearDown()
    {
        $stmt = $this->pdo->prepare('DROP DATABASE IF EXISTS `DbMockLibraryTest`');
        $stmt->execute();

        $stmt = $this->pdo->prepare('DELETE FROM DbMockLibraryTest.testTable WHERE `id` = 0');
        $stmt->execute();

        MySQL::getInstance()->destroy();
    }

    /**
     * @return void
     */
    public function test_function()
    {
        // prepare
        $stmt = $this->pdo->prepare('SELECT * FROM `DbMockLibraryTest`.testTable WHERE `id` = 0');
        $stmt->execute();
        $result = $stmt->fetchAll();
        $reflection = new \ReflectionClass(MySQL::getInstance());
        $insertMethod = $reflection->getMethod('insert');
        $insertMethod->setAccessible(true);

        // test
        $this->assertCount(0, $result);

        // invoke logic
        $insertMethod->invoke(MySQL::getInstance(), 'testTable', 1);

        // prepare
        $stmt->execute();
        $result = $stmt->fetchAll();

        // test
        $this->assertCount(1, $result);
    }
}
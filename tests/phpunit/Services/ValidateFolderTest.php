<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 14.01.18
 * Time: 19:26
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\Object\FolderService;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidateFolderTest
 *
 * @package OCA\Passwords\Services
 * @covers \OCA\Passwords\Services\ValidationService
 */
class ValidateFolderTest extends TestCase {

    /**
     * @var \OCA\Passwords\Services\ValidationService
     */
    protected $validationService;

    /**
     * @throws \ReflectionException
     */
    protected function setUp() {
        $container           = $this->createMock('\OCP\AppFramework\IAppContainer');
        $this->validationService = new \OCA\Passwords\Services\ValidationService($container);
    }

    /**
     *
     * ValidateFolder Tests
     *
     */
    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderInvalidSse() {
        $mock = $this->getFolderMock();

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7b584c1e', $e->getId());
            $this->assertEquals('Invalid server side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderInvalidCse() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('4e8162e6', $e->getId());
            $this->assertEquals('Invalid client side encryption type', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderEmptyLabel() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);

        try {
            $this->validationService->validateFolder($mock);
            $this->fail("Expected exception thrown");
        } catch(ApiException $e) {
            $this->assertEquals(400, $e->getHttpCode());
            $this->assertEquals('7c31eb4d', $e->getId());
            $this->assertEquals('Field "label" can not be empty', $e->getMessage());
        }
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderSetsSseType() {
        $mock = $this->getFolderMock();

        $mock->expects($this->any())
             ->method('getSseType')
             ->will($this->onConsecutiveCalls('', EncryptionService::DEFAULT_SSE_ENCRYPTION));

        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setSseType')->with(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $this->validationService->validateFolder($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderCorrectsInvalidFolderUuid() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn('1-2-3');
        $mock->method('getEdited')->willReturn(1);

        $mock->expects($this->once())->method('setParent')->with(FolderService::BASE_FOLDER_UUID);
        $this->validationService->validateFolder($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderSetsEditedWhenEmpty() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getEdited')->willReturn(0);

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validateFolder($mock);
    }

    /**
     * @throws \Exception
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testValidateFolderSetsEditedWhenInFuture() {
        $mock = $this->getFolderMock();

        $mock->method('getSseType')->willReturn(EncryptionService::DEFAULT_SSE_ENCRYPTION);
        $mock->method('getCseType')->willReturn(EncryptionService::DEFAULT_CSE_ENCRYPTION);
        $mock->method('getLabel')->willReturn('label');
        $mock->method('getParent')->willReturn(FolderService::BASE_FOLDER_UUID);
        $mock->method('getEdited')->willReturn(strtotime('+2 hours'));

        $mock->expects($this->once())->method('setEdited');
        $this->validationService->validateFolder($mock);
    }



    /**
     * @return \OCA\Passwords\Db\FolderRevision
     */
    protected function getFolderMock() {
        $mock = $this
            ->getMockBuilder('\OCA\Passwords\Db\FolderRevision')
            ->setMethods(['getSseType', 'setSseType', 'getCseType', 'getHidden', 'getLabel', 'getParent', 'setParent', 'getEdited', 'setEdited'])
            ->getMock();

        $mock->method('getHidden')->willReturn(false);

        return $mock;
    }
}
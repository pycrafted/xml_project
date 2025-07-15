<?php

namespace WhatsApp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * Tests unitaires pour XMLManager
 * 
 * @covers \WhatsApp\Utils\XMLManager
 * @author WhatsApp Clone Team
 */
class XMLManagerTest extends TestCase
{
    private XMLManager $xmlManager;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = 'data/test_phpunit.xml';
        $this->xmlManager = new XMLManager($this->testFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    /**
     * @test
     * @group creation
     */
    public function it_creates_xml_file_automatically(): void
    {
        $this->assertFileExists($this->testFile);
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_xml_against_xsd(): void
    {
        $this->assertTrue($this->xmlManager->load());
    }

    /**
     * @test
     * @group crud
     */
    public function it_adds_element_with_correct_namespace(): void
    {
        $userData = [
            'attributes' => ['id' => 'test_user'],
            'name' => 'Test User',
            'email' => 'test@example.com',
            'status' => 'active',
            'settings' => []
        ];

        $result = $this->xmlManager->addElement('//wa:users', 'user', $userData);
        
        $this->assertTrue($result);
        
        // Vérifier que l'élément existe
        $element = $this->xmlManager->findElementById('user', 'test_user');
        $this->assertNotNull($element);
    }

    /**
     * @test
     * @group crud
     */
    public function it_finds_element_by_id(): void
    {
        // Ajouter un élément
        $userData = [
            'attributes' => ['id' => 'find_test'],
            'name' => 'Find Test',
            'email' => 'find@test.com',
            'status' => 'active',
            'settings' => []
        ];
        $this->xmlManager->addElement('//wa:users', 'user', $userData);

        // Le trouver
        $element = $this->xmlManager->findElementById('user', 'find_test');
        
        $this->assertNotNull($element);
        $this->assertEquals('find_test', $element->getAttribute('id'));
    }

    /**
     * @test
     * @group crud
     */
    public function it_deletes_element_by_id(): void
    {
        // Ajouter un élément
        $userData = [
            'attributes' => ['id' => 'delete_test'],
            'name' => 'Delete Test',
            'email' => 'delete@test.com',
            'status' => 'active',
            'settings' => []
        ];
        $this->xmlManager->addElement('//wa:users', 'user', $userData);

        // Le supprimer
        $result = $this->xmlManager->deleteElementById('user', 'delete_test');
        
        $this->assertTrue($result);
        
        // Vérifier qu'il n'existe plus
        $element = $this->xmlManager->findElementById('user', 'delete_test');
        $this->assertNull($element);
    }

    /**
     * @test
     * @group error_handling
     */
    public function it_throws_exception_for_invalid_parent_path(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Parent node non trouvé');

        $userData = ['attributes' => ['id' => 'test']];
        $this->xmlManager->addElement('//invalid:path', 'user', $userData);
    }

    /**
     * @test
     * @group simplexml
     */
    public function it_returns_simple_xml_element(): void
    {
        $simpleXML = $this->xmlManager->getSimpleXML();
        
        $this->assertInstanceOf(\SimpleXMLElement::class, $simpleXML);
        $this->assertEquals('whatsapp_data', $simpleXML->getName());
    }
} 
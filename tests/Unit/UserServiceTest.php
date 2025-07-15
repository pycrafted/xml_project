<?php

namespace WhatsApp\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WhatsApp\Services\UserService;
use WhatsApp\Utils\XMLManager;
use Exception;

/**
 * Tests unitaires pour UserService
 * 
 * @covers \WhatsApp\Services\UserService
 */
class UserServiceTest extends TestCase
{
    private UserService $userService;
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = 'data/test_user_service.xml';
        $xmlManager = new XMLManager($this->testFile);
        $this->userService = new UserService($xmlManager);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    /**
     * @test
     * @group user_creation
     */
    public function it_creates_user_with_valid_data(): void
    {
        $user = $this->userService->createUser(
            'test_user',
            'Test User',
            'test@example.com',
            ['theme' => 'dark']
        );

        $this->assertEquals('test_user', $user->getId());
        $this->assertEquals('Test User', $user->getName());
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals(['theme' => 'dark'], $user->getSettings());
    }

    /**
     * @test
     * @group validation
     */
    public function it_throws_exception_for_duplicate_id(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Un utilisateur avec l'ID 'duplicate' existe déjà");

        // Créer un utilisateur
        $this->userService->createUser('duplicate', 'User 1', 'user1@test.com');
        
        // Tenter de créer un autre avec le même ID
        $this->userService->createUser('duplicate', 'User 2', 'user2@test.com');
    }

    /**
     * @test
     * @group validation
     */
    public function it_throws_exception_for_duplicate_email(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Un utilisateur avec l'email 'same@test.com' existe déjà");

        // Créer un utilisateur
        $this->userService->createUser('user1', 'User 1', 'same@test.com');
        
        // Tenter de créer un autre avec le même email
        $this->userService->createUser('user2', 'User 2', 'same@test.com');
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_email_format(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Format d\'email invalide');

        $this->userService->createUser('test', 'Test', 'invalid-email');
    }

    /**
     * @test
     * @group validation
     */
    public function it_validates_name_length(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Le nom doit contenir au moins 2 caractères');

        $this->userService->createUser('test', 'A', 'test@example.com');
    }

    /**
     * @test
     * @group update
     */
    public function it_updates_existing_user(): void
    {
        // Créer un utilisateur
        $this->userService->createUser('update_test', 'Original Name', 'original@test.com');

        // Le mettre à jour
        $updatedUser = $this->userService->updateUser('update_test', [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'settings' => ['theme' => 'light']
        ]);

        $this->assertEquals('Updated Name', $updatedUser->getName());
        $this->assertEquals('updated@test.com', $updatedUser->getEmail());
        $this->assertEquals(['theme' => 'light'], $updatedUser->getSettings());
    }

    /**
     * @test
     * @group search
     */
    public function it_searches_users_by_criteria(): void
    {
        // Créer des utilisateurs de test
        $this->userService->createUser('search1', 'Alice Test', 'alice@test.com');
        $this->userService->createUser('search2', 'Bob Test', 'bob@test.com');
        $this->userService->createUser('search3', 'Alice Smith', 'alice2@test.com');

        // Recherche par nom
        $results = $this->userService->searchUsers(['name' => 'Alice']);
        $this->assertCount(2, $results);

        // Recherche par email
        $results = $this->userService->searchUsers(['email' => 'bob@test.com']);
        $this->assertCount(1, $results);
        $this->assertEquals('Bob Test', $results[0]->getName());
    }

    /**
     * @test
     * @group statistics
     */
    public function it_provides_user_statistics(): void
    {
        // Créer des utilisateurs avec différents statuts
        $user1 = $this->userService->createUser('stat1', 'User 1', 'user1@test.com');
        $user2 = $this->userService->createUser('stat2', 'User 2', 'user2@test.com');
        
        // Modifier le statut d'un utilisateur
        $this->userService->updateUser('stat2', ['status' => 'inactive']);

        $stats = $this->userService->getUserStats();
        
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['active']);
        $this->assertEquals(1, $stats['inactive']);
    }
} 
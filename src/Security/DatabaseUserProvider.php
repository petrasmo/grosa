<?php
// src/Security/DatabaseUserProvider.php
namespace App\Security;

use App\Security\User; // arba App\Security\User, priklausomai nuo jūsų konfigūracijos
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class DatabaseUserProvider implements UserProviderInterface
{
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function loadUserByIdentifier(string $username): UserInterface
{
    try {
        // Pasiruošiame iškvietimui
        $conn = $this->db->getWrappedConnection();
        
        // Iškviečiame procedūrą
        $stmt = $conn->prepare('CALL get_user_info(?, ?, @p_user_id, @p_first_name, @p_last_name, @p_email, @p_role)');
        $stmt->bindParam(1, $username, \PDO::PARAM_STR);
        $stmt->bindValue(2, '', \PDO::PARAM_STR); // Slaptažodis neperduodamas čia
        $stmt->execute();
        
        // Gauname OUT parametrų reikšmes
        $result = $this->db->executeQuery('SELECT @p_user_id as user_id, @p_first_name as first_name, @p_last_name as last_name, @p_email as email, @p_role as role')->fetchAssociative();
        
        if (!$result || empty($result['user_id'])) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $username));
        }
        
        // Sukuriame User objektą su visais duomenimis
        return new User(
            username: $username,
            password: '', // Slaptažodžio nereikia čia saugoti
            roles: [$result['role']],
            id: (int) $result['user_id'],
            firstName: $result['first_name'] ?? null,
            lastName: $result['last_name'] ?? null,
            email: $result['email'] ?? null
        );
    } catch (\Exception $e) {
        throw new UserNotFoundException('Error loading user: ' . $e->getMessage());
    }
}

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
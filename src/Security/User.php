<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id;
    private string $username;
    private string $password;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $email;
    private array $roles = [];

    public function __construct(
        string $username,
        string $password = '',
        array $roles = ['ROLE_USER'],
        ?int $id = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
        $this->roles = array_unique(array_merge($roles, ['ROLE_USER'])); // Užtikriname, kad ROLE_USER visada bus
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password ?? ''; // Jei slaptažodis `null`, grąžiname tuščią eilutę
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        return array_unique($this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_unique(array_merge($roles, ['ROLE_USER']));
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->password = ''; // Užtikriname, kad slaptažodis nebūtų laikomas atmintyje
    }
}

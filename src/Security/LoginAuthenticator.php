<?php
// src/Security/LoginAuthenticator.php
// src/Security/LoginAuthenticator.php
namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
// Pakeiskite šią eilutę:
// use Symfony\Component\Security\Core\Security;
// Į šią:
use Symfony\Bundle\SecurityBundle\Security;
// Arba jei naudojate senesnę Symfony versiją:
// use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private $urlGenerator;
    private $db;

    public function __construct(UrlGeneratorInterface $urlGenerator, Connection $db)
    {
        $this->urlGenerator = $urlGenerator;
        $this->db = $db;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');

        $request->getSession()->set('_security.last_username', $username);

        return new Passport(
            new UserBadge($username),
            new CustomCredentials(function ($credentials, $user) {
                // Prisijungiame prie duomenų bazės per Doctrine DBAL
                $conn = $this->db;
        
                // Paruošiame užklausą su slaptažodžio tikrinimu
                $query = 'SELECT COUNT(*) as count FROM ord_users WHERE username = :username AND password = SHA2(:password, 256) AND deleted = 0 AND status = 1';
                
                // Vykdome užklausą per `executeQuery()`, kuris palaiko `fetchAssociative()`
                $result = $conn->executeQuery($query, [
                    'username' => $user->getUserIdentifier(),
                    'password' => $credentials
                ])->fetchAssociative();
        
                return $result !== false && $result['count'] > 0;
            }, $password)
        );
        
        
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            //return new RedirectResponse($targetPath);
            return new RedirectResponse($this->urlGenerator->generate('uzsakymai'));

        }


        return new RedirectResponse($this->urlGenerator->generate('uzsakymai')); // Nukreipimas po sėkmingo prisijungimo
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
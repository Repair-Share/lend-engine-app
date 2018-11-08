<?php
namespace UserBundle\Security\Core\User;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider as BaseClass;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class OAuthUserProvider
 * @package AppBundle\Security\Core\User
 */
class OAuthUserProvider extends BaseClass
{

    /** @var Session */
    private $session;

    private $security;

    public function __construct(\FOS\UserBundle\Doctrine\UserManager $userManager, array $properties, Session $session, $security) {
        $this->session = $session;
        $this->security = $security;
        parent::__construct($userManager, $properties);
    }

    /**
     * {@inheritDoc}
     */
    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        $property = $this->getProperty($response);
        $username = $response->getUsername();

        //on connect - get the access token and the user ID
        $service = $response->getResourceOwner()->getName();
        $setter = 'set'.ucfirst($service);
        $setter_id = $setter.'Id';
        $setter_token = $setter.'AccessToken';

        //we "disconnect" previously connected users
        if (null !== $previousUser = $this->userManager->findUserBy(array($property => $username))) {
            $previousUser->$setter_id(null);
            $previousUser->$setter_token(null);
            $this->userManager->updateUser($previousUser);
        }

        //we connect current user
        $user->$setter_id($username);
        $user->$setter_token($response->getAccessToken());

        $this->userManager->updateUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {

        $f = $this->session->getFlashBag();

        // eg "twitter_id" from services.yml and config.yml
        $prop = $this->getProperty($response);
        $serviceName = $response->getResourceOwner()->getName();

        if (!$socialId = $response->getUsername()) {
            throw new \RuntimeException('Unable to authenticate. An error occurred during OAuth authentication.');
        }

        // Self
        if (!$user = $this->security->getToken()->getUser()) {
            throw new \RuntimeException('Cannot link your social account, you are not logged in.');
        }

        if ($this->userManager->findUserBy([$prop => $socialId])) {
            // Already connected
            return $user;
        } else {

            $email = $response->getEmail();
            if ($user->getEmail() != $email) {
                $f->add('error', "The email address for your social account ({$email}) does not match ".$user->getEmail().". Please update your account email address first.");
                return $user;
            }

            // Email does match, add social details
            $f->add('success', "Great stuff! You're now connected.");

            // Update the user's access token and ID
            $setterId = 'set' . ucfirst($serviceName) . 'Id';
            $user->$setterId($socialId);

            $setterToken = 'set' . ucfirst($serviceName) . 'AccessToken';
            $user->$setterToken($response->getAccessToken());

            if ($serviceName == 'twitter') {
                $setterToken = 'set' . ucfirst($serviceName) . 'AccessTokenSecret';
                $user->$setterToken($response->getTokenSecret());
            }

            return $user;
        }


//        $firstName = $response->getFirstName();
//        $lastName  = $response->getLastName();

        // No user found with this social ID
//        if (null === $user) {
//
//            // Link it to current user if email matches
//
//            if ($user->getEmail() != $email) {
//                $f->add('error', "The email address for your social account ({$email}) does not match ".$user->getEmail().". Please update your account email address first.");
//                return $user;
//            }
//
//            // See if it matches the logged in user email
//            $user = $this->userManager->findUserByEmail($email);
//
//            if (null === $user || !$user instanceof UserInterface) {
//
//                // No user found, create one
//                $service = $response->getResourceOwner()->getName();
//                $setter = 'set'.ucfirst($service);
//                $setter_id = $setter.'Id';
//                $setter_token = $setter.'AccessToken';
//
//                /** @var \AppBundle\Entity\Contact $user */
//                $user = $this->userManager->createUser();
//                $user->$setter_id($socialId);
//                $user->$setter_token($response->getAccessToken());
//
//                $user->setUsername($email);
//                $user->setEmail($email);
//                $user->setPlainPassword(md5(uniqid()));
//                $user->setEnabled(true);
//
//                if ($serviceName == 'twitter') {
//                    $n = explode(' ', $firstName);
//                    $firstName = $n[0];
//                    if (isset($n[1])) {
//                        $lastName = $n[1];
//                    }
//                }
//
//                $user->setFirstName($firstName);
//                $user->setLastName($lastName);
//
//                $this->userManager->updateUser($user);
//                return $user;
//
//            } else {
//                return $user;
//            }
//
//        }


    }

}
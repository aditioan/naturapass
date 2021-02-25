<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 07/07/14
 * Time: 14:42
 */

namespace NaturaPass\UserBundle\Security\Core\User;

use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\Exception\AccountNotLinkedException;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\UserMedia;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class FOSUBUserProvider extends \HWI\Bundle\OAuthBundle\Security\Core\User\FOSUBUserProvider
{
    protected $connection = array();

    public function __construct($userManager, array $properties, array $connection)
    {
        parent::__construct($userManager, $properties);
        $this->connection = $connection;
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername($username)
    {
        // Compatibility with FOSUserBundle < 2.0
        if (class_exists('FOS\UserBundle\Form\Handler\RegistrationFormHandler')) {
            return $this->userManager->loadUserByUsername($username);
        }

        return $this->userManager->findUserByUsername($username);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $data = $response->getResponse();
        if (isset($data['error'], $data['error']['message'])) {
            throw new \Exception("Une erreur s'est produite lors de la connexion avec Facebook");
        }

        try {
            $username = $response->getUsername();
            $user = $this->userManager->findUserBy(array($this->getProperty($response) => $username));
            if (null === $user || null === $username) {
                throw new AccountNotLinkedException(sprintf("User '%s' not found.", $username));
            }

            return $user;
        } catch (AccountNotLinkedException $e) {
            $service = $response->getResourceOwner()->getName();
            $data = $response->getResponse();
            if (null === $user = $this->userManager->findUserByEmail($response->getEmail())) {
                $user = $this->userManager->createUser();
                $add = true;
            } else {
                $add = false;
            }

            if (ucfirst($service) === 'Facebook') {
                FacebookSession::setDefaultApplication($this->connection["id"], $this->connection["secret"]);
                $session = new FacebookSession($response->getAccessToken());
                $request = new FacebookRequest(
                    $session, 'GET', '/me/picture', array(
                        'redirect' => false,
                        'type' => 'large'
                    )
                );

                $profilePicture = $request->execute()->getGraphObject()->asArray();
                if (!$profilePicture['is_silhouette']) {
                    $file = file_get_contents($profilePicture['url']);

                    $path = tempnam(sys_get_temp_dir(), 'PHP');

                    $handle = fopen($path, "w");
                    fwrite($handle, $file);
                    fclose($handle);

                    $media = new UserMedia();
                    $media->setOwner($user)
                        ->setFile(new UploadedFile($path, basename($path), 'image/jpeg'))
                        ->setState(UserMedia::STATE_PROFILE_PICTURE);

                    $user->addMedia($media);
                }
            }

            $setter = 'set' . ucfirst($service) . 'Data';
            $user->$setter($data, $add);

            return $user;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function connect(UserInterface $user, UserResponseInterface $response)
    {
        $property = $this->getProperty($response);
        $setter = 'set' . ucfirst($property);

        if (!method_exists($user, $setter)) {
            throw new \RuntimeException(sprintf("Class '%s' should have a method '%s'.", get_class($user), $setter));
        }

        $username = $response->getUsername();

        if (null !== $previousUser = $this->userManager->findUserBy(array($property => $username))) {
            $previousUser->$setter(null);
            $this->userManager->updateUser($previousUser);
        }

        $user->$setter($username);

        $this->userManager->updateUser($user);
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        // Compatibility with FOSUserBundle < 2.0
        if (class_exists('FOS\UserBundle\Form\Handler\RegistrationFormHandler')) {
            return $this->userManager->refreshUser($user);
        }

        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf(
                'Expected an instance of FOS\UserBundle\Model\User, but got "%s".', get_class($user)
            ));
        }

        if (null === $reloadedUser = $this->userManager->findUserBy(array('id' => $user->getId()))) {
            throw new UsernameNotFoundException(sprintf('User with ID "%d" could not be reloaded.', $user->getId()));
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    /**
     * Gets the property for the response.
     *
     * @param UserResponseInterface $response
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getProperty(UserResponseInterface $response)
    {
        $resourceOwnerName = $response->getResourceOwner()->getName();

        if (!isset($this->properties[$resourceOwnerName])) {
            throw new \RuntimeException(sprintf(
                "No property defined for entity for resource owner '%s'.", $resourceOwnerName
            ));
        }

        return $this->properties[$resourceOwnerName];
    }

}

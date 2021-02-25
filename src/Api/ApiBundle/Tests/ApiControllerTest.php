<?php

namespace Api\ApiBundle\Tests;

use NaturaPass\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Doctrine\Common\Collections\ArrayCollection;

abstract class ApiControllerTest extends WebTestCase
{

    /**
     * @var ArrayCollection|ApiClientTest[]
     */
    protected $clients;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected $router;

    protected static $names = array('Vincent', 'Nicolas', 'Sylvain');

    public function setUp()
    {
        static::bootKernel();

        $this->clients = new ArrayCollection();

        $this->router = self::$kernel->getContainer()->get('router');

        for ($i = 0; $i < 3; $i++) {
            $name = isset(static::$names[$i]) ? static::$names[$i] : $i;

            $client = new ApiClientTest(self::$kernel);

            $user = self::$kernel->getContainer()->get('doctrine')->getRepository(
                'NaturaPassUserBundle:User'
            )->findOneByEmail('phpunit' . $i . '@naturapass.com');

            if ($user instanceof User) {
                $user->addRole('ROLE_ADMIN');

                $manager = self::$kernel->getContainer()->get('doctrine')->getManager();

                $manager->persist($user);
                $manager->flush();

                $client->setUser($user);
            }

            $client->setId($i);

            $this->clients->set($name, $client);
        }

        parent::setUp();
    }

    /**
     * Retourne le message d'erreur d'une exception
     *
     * @param Response $response
     * @return string
     */
    public function getErrorMessage(Response $response)
    {
        $message = '';

        if ($response->getStatusCode() - 400 >= 0) {
            $content = $this->decodeResponseContent($response);

            var_dump($content);

            if (isset($content[0], $content[0]['message'])) {
                $message = 'Erreur: ' . $content[0]['message'];
            }

        }

        return $message;
    }

    /**
     * Assertion testant le code de retour d'un client
     *
     * @param $code
     * @param Client $client
     * @return array
     */
    public function assertStatusCodeEquals($code, Client $client)
    {
        $this->assertEquals(
            $code,
            $client->getResponse()->getStatusCode(),
            $this->getErrorMessage($client->getResponse())
        );

        return $this->decodeResponseContent($client->getResponse());
    }


    public function tearDown()
    {
        self::$kernel->getContainer()->get('doctrine')->getConnection()->close();
        parent::tearDown();
    }

    protected function getRootDir()
    {
        return self::$kernel->getRootDir() . '/../';
    }

    /**
     * @param Response $response
     * @return array
     */
    protected function decodeResponseContent(Response $response)
    {
        $this->assertJson($response->getContent());

        return json_decode($response->getContent(), true);
    }

    protected function logClientIn(ApiClientTest $client)
    {
        if (!$client->getUser() instanceof User) {
            $user = self::$kernel->getContainer()->get('doctrine')->getRepository(
                'NaturaPassUserBundle:User'
            )->findOneByEmail('phpunit' . $client->getId() . '@naturapass.com');

            if ($user instanceof User) {
                $client->setUser($user);
            }
        }

        if ($client->getUser() instanceof User) {
            $session = self::$kernel->getContainer()->get('session');

            $token = new UsernamePasswordToken($client->getUser(), null, "main", $client->getUser()->getRoles());

            $session->set('_security_main', serialize($token));
            $session->save();

            $cookie = new Cookie($session->getName(), $session->getId());

            $client->getCookieJar()->set($cookie);

            $this->clients->set(
                isset(static::$names[$client->getId()]) ? static::$names[$client->getId()] : $client->getId(),
                $client
            );
        }
    }
}

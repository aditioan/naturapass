<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 13/05/14
 * Time: 16:54
 */

namespace NaturaPass\EmailBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\UserBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class EmailService
{

    protected $logger;
    protected $manager;
    protected $translator;
    protected $mailer;
    protected $templating;
    protected $securityContext;
    protected $environment;
    protected $translation_name;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $manager, TranslatorInterface $translator, \Swift_Mailer $mailer, \Twig_Environment $templating, TokenStorageInterface $securityContext, $environment, $translation_name)
    {
        $this->logger = $logger;
        $this->manager = $manager;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->securityContext = $securityContext;
        $this->environment = $environment->getEnvironment();
        $this->translation_name = $translation_name;
    }

    public function generate(
        $type,
        array $subject,
        array $receivers,
        $twig,
        array $renderVars
    )
    {
        $model = $this->manager->getRepository('NaturaPassEmailBundle:EmailModel')->findOneBy(array(
            'type' => $type
        ));

        if (empty($receivers)) {
            return;
        }
        if (!isset($renderVars['email_body'])) $renderVars['email_body'] = '';
        $subject = $this->translator->trans($type . '.subject', $subject, $this->translation_name . 'email');
        $from = $this->translator->trans($type . '.from', array(), $this->translation_name . 'email');
        $body = $this->templating->render($twig, $renderVars);

        try {
            if (is_object($model)) {
                foreach ($receivers as $receiver) {
                    if (!$this->isConnectedUser($receiver)) {
                        $message = $this->getMessage($subject, $from, $body);

                        if ($receiver instanceof User) {
                            $param = $receiver->getParameters()->getEmailByType($type);

                            if (($param && $param->getWanted()) || !$param) {
                                $message->setTo($receiver->getEmail(), $receiver->getFullname());
                                $this->mailer->send($message);
                            }
                        } else if (filter_var($receiver, FILTER_VALIDATE_EMAIL)) {
                            $message->setTo($receiver);
                            $this->mailer->send($message);
                        }
                    }
                }
            } else {
                foreach ($receivers as $receiver) {
                    if (!$this->isConnectedUser($receiver)) {
                        $message = $this->getMessage($subject, $from, $body);

                        if ($receiver instanceof User) {
                            $message->setTo($receiver->getEmail(), $receiver->getFullname());
                            $this->mailer->send($message);
                        } else if (filter_var($receiver, FILTER_VALIDATE_EMAIL)) {
                            $message->setTo($receiver);
                            $this->mailer->send($message);
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * @param $subject
     * @param $from
     * @param $body
     * @return \Swift_Message
     */
    protected function getMessage($subject, $from, $body)
    {
        $message = \Swift_Message::newInstance()
            ->setContentType("text/html")
            ->setSubject($subject)
            ->setFrom($from)
            ->setBody($body)
            ->setReturnPath('noreply@naturapass.com');

        if ($this->environment === 'prod') {
            $message->addBcc('suivi@naturapass.com');
        }

        return $message;
    }

    protected function isConnectedUser($receiver)
    {
        if ($this->securityContext->getToken()->getUser() instanceof User) {
            $email = $this->securityContext->getToken()->getUser()->getEmail();

            return ($receiver instanceof User && $receiver->getEmail() === $email) || ($receiver === $email);
        }

        return false;
    }
}
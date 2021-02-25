<?php

/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 15/04/14
 * Time: 23:48
 */

namespace NaturaPass\NotificationBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\NotificationBundle\Entity\AbstractNotification;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use NaturaPass\NotificationBundle\Entity\NotificationReceiver;
use NaturaPass\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class NotificationService
{

    protected $router;
    protected $manager;
    protected $translator;
    protected $tokenStorage;
    protected $pool;
    protected $translation_name;
    protected $projectID;

    public function __construct(TokenStorageInterface $tokenStorage, EntityManagerInterface $manager, Router $router, TranslatorInterface $translator, NotificationMemoryPool $pool, $translation_name, $projectID)
    {
        $this->router = $router;
        $this->manager = $manager;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->translation_name = $translation_name;
        $this->projectID = $projectID;

        $this->pool = $pool;
    }

    /**
     * @param AbstractNotification $notification
     * @param mixed $receivers
     * @param User|null $overridedSender
     */
    public function queue(AbstractNotification $notification, $receivers, $overridedSender = null)
    {
        if (!is_array($receivers)) {
            $receivers = array($receivers);
        }

        $notification
            ->setSender($overridedSender instanceof User ? $overridedSender : $this->tokenStorage->getToken()->getUser());

        if (!empty($notification->getRoute())) {
            $notification->setLink(str_replace("app_dev.php/", "", $this->router->generate($notification->getRoute(), $notification->getLinkData(), Router::ABSOLUTE_URL)));
        }

        if ($notification->isMultiple() && count($receivers) == 1 && $notification->isPersistable()) {
            $notification->setContent($this->getMultipleMessage($notification, $receivers[0], $notification->getContentData()));
        } else {
            $notification->setContent(
                $this->translator->trans(
                    $notification->getType(), $this->formatTranslationsArray($notification->getContentData()), $this->translation_name . 'notifications'
                )
            );
        }

        $getPushData = $notification->getPushData();
        if($notification->getType() == 'lounge.publication.new' || $notification->getType() == 'group.publication.new'){
            $notification->setPublicationID($getPushData['publication_id']);
        }
        
        if ($notification->isPersistable()) {
            $this->manager->persist($notification);
            $this->manager->flush();
        }

        $this->buildReceivers($notification, $receivers);

        $this->pool->queueNotification($notification, $this->projectID);
    }

    /**
     * Construit l'ensemble des récepteurs de la notifications
     *
     * @param AbstractNotification $notification
     * @param User[] $users
     */
    protected function buildReceivers(AbstractNotification $notification, array $users)
    {
        foreach ($users as $user) {
            $receiver = new NotificationReceiver();

            $receiver->setReceiver($user)
                ->setNotification($notification)
                ->setState(NotificationReceiver::STATE_UNREAD);

            if ($notification->isPersistable())
                $this->manager->persist($receiver);

            $notification->addReceiver($receiver);
        }

        if ($notification->isPersistable())
            $this->manager->flush();
    }

    /**
     * Transforme un tableau de données de traductions en un tableau valide
     *
     * @param array $translations
     *
     * @return array
     */
    protected function formatTranslationsArray($translations)
    {
        if (is_array($translations)) {
            foreach ($translations as $key => $value) {
                unset($translations[$key]);

                if ($key[0] !== '%') {
                    $key = '%' . $key;
                }

                if ($key[strlen($key) - 1] !== '%') {
                    $key = $key . '%';
                }

                $translations[$key] = $value;
            }
        } else {
            $translations = array($translations);
        }

        return $translations;
    }

    /**
     * Ecris à la suite plusieurs chaines de caractères, séparées par des virgules
     *
     * @param ArrayCollection $values
     *
     * @return string $linked
     */
    public function getLinkedValues(ArrayCollection $values)
    {
        if (count($values) > 1 && count($values) <= 3) {
            $linked = join(', ', $values->slice(0, count($values) - 1));

            $linked .= ' ' . $this->translator->trans('words.and', array(), $this->translation_name . 'global') . ' ';
            $linked .= $values->last();
        } else {
            if (count($values) > 3) {
                $linked = join(', ', $values->slice(0, 3));
                $linked .= ' ' . $this->translator->trans('words.and', array(), $this->translation_name . 'global') . ' ';
                $linked .= $this->translator->transChoice(
                    'plural.persons', count($values) - 3, array('%count%' => count($values) - 3), $this->translation_name . 'notifications'
                );
            } else {
                $linked = join(', ', $values->slice(0, count($values)));
            }
        }

        return $linked;
    }

    /**
     * Retourne un message selon les notifications précédentes éventuellement présentes, si la notificarion pour être envoyée
     * de plusieurs personnes, mais peut avoir un seul et même récepteur
     *
     * @param AbstractNotification $notification
     * @param User $receiver
     * @param array $data
     *
     * @return string
     */
    protected function getMultipleMessage(AbstractNotification $notification, User $receiver, array $data)
    {
        $qb = $this->manager->getRepository('NaturaPassNotificationBundle:AbstractNotification')
            ->createQueryBuilder('n');

        $updated = new \DateTime();
        $updated->sub(new \DateInterval('P7D'));

        /**
         * @var AbstractNotification[] $similars
         */
        $similars = $qb
            ->select('n')
            ->where('n INSTANCE OF :type')
            ->andWhere('n.objectID = :objectID')
            ->andWhere('n.updated >= :updated')
            ->orderBy('n.created', 'DESC')
            ->setParameter('type', get_class($notification))
            ->setParameter('objectID', $notification->getObjectID())
            ->setParameter('updated', $updated)
            ->getQuery()
            ->getResult();

        if (count($similars)) {
            $names = new ArrayCollection();
            $senders = new ArrayCollection();

            $names->add($notification->getSender()->getFullName());
            $senders->add($notification->getSender());

            foreach ($similars as $similar) {
                if (count($similar->getReceivers()) === 1 && $similar->hasReceiver($receiver)) {
                    $arrayName = $names->toArray();
                    if (!in_array($similar->getSender()->getFullname(), $arrayName)) {
                        $senders->add($similar->getSender());
                        $names->add($similar->getSender()->getFullname());
                    }

                    $similar->setVisible(false);
                    $this->manager->persist($similar);
                }
            }

            if (count($names) > 1) {
                $data['%senders%'] = $this->getLinkedValues($names);

                return $this->translator->transChoice($notification->getType(), count($senders), $data, $this->translation_name . 'notifications');
            }
        }

        return $this->translator->transChoice($notification->getType(), 1, $data, $this->translation_name . 'notifications');
    }

}

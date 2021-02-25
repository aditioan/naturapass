<?php

namespace NaturaPass\GroupBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use NaturaPass\GroupBundle\Entity\GroupMedia;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use NaturaPass\GroupBundle\Entity\Group;
use NaturaPass\GroupBundle\Entity\GroupUser;

/**
 * Description of GroupHandler
 *
 * @author vincentvalot
 */
class GroupHandler
{

    protected $request;
    protected $form;
    protected $manager;

    public function __construct(Form $form, Request $request, EntityManagerInterface $manager)
    {
        $this->request = $request;
        $this->form = $form;
        $this->manager = $manager;
    }

    /**
     * @return \NaturaPass\GroupBundle\Entity\Group
     */
    public function process()
    {
        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'PUT') {
            $this->form->handleRequest($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($this->form->getData());
            }
        }

        return false;
    }

    /**
     * @param \NaturaPass\GroupBundle\Entity\Group $group
     * @return \NaturaPass\GroupBundle\Entity\Group $group
     */
    public function onSuccess(Group $group)
    {
        $edit = $group->getId();

        if ($photo = $this->request->files->get('group[photo][file]', false, true)) {
            $media = new GroupMedia();
            $media->setFile($photo);

            $this->manager->remove($group->getPhoto());

            $group->setPhoto($media);
        }

        $this->manager->persist($group);
        $this->manager->flush();

        if (is_null($edit) || $edit == "") {
            $groupUser = new GroupUser();
            $groupUser->setUser($group->getOwner())
                ->setGroup($group)
                ->setAccess(GroupUser::ACCESS_ADMIN);

            $this->manager->persist($groupUser);
            $this->manager->flush();
        } else {
            foreach ($group->getNotifications() as $groupNotification) {
                $paramNotifications = $this->manager->getRepository("NaturaPassUserBundle:ParametersNotification")->findBy(array("type" => $groupNotification->getType(), "objectID" => $group->getId()));
                if (!is_null($paramNotifications)) {
                    foreach ($paramNotifications as $paramNotification) {
                        $paramNotification->setWanted($groupNotification->getWanted());
                    }
                }
            }
            foreach ($group->getEmails() as $groupEmail) {
                $emailModel = $this->manager->getRepository("NaturaPassEmailBundle:EmailModel")->findOneBy(array("type" => $groupEmail->getType()));
                if (!is_null($emailModel)) {
                    $paramEmails = $this->manager->getRepository("NaturaPassUserBundle:ParametersEmail")->findBy(array("email" => $emailModel->getId()));
                    if (!is_null($paramEmails)) {
                        foreach ($paramEmails as $paramEmail) {
                            $paramEmail->setWanted($groupEmail->getWanted());
                        }
                    }
                }
            }

            $this->manager->persist($group);
            $this->manager->flush();
        }
        return $group;
    }

}

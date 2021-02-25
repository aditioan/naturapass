<?php

namespace Api\ApiBundle\Controller\Backoffice;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Util\Codes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Admin\SentinelleBundle\Entity\Receiver;
use Api\ApiBundle\Controller\v1\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\GroupSerialization;

/**
 * Description of GroupsController
 *
 */
class GroupController extends ApiRestController
{

    /**
     * FR : Retourne tous les groupes matchant le paramètre
     * EN : Returns all groups according to the parameters
     *
     * GET /backoffice/groups/search?name=Blabla&select=false
     *
     * search contient le nom recherché encodé en format URL
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     */
    public function getGroupsSearchAction(Request $request)
    {
//        $this->authorize(null, 'ROLE_FDC');
        $search = urldecode($request->query->get('name', ''));

        $receiver = $this->getUser()->getFirstReceiver();
        $groups = array();
        $utf8 = array(
            '/[áàâãªä]/u' => 'a',
            '/[ÁÀÂÃÄ]/u' => 'A',
            '/[ÍÌÎÏ]/u' => 'I',
            '/[íìîï]/u' => 'i',
            '/[éèêë]/u' => 'e',
            '/[ÉÈÊË]/u' => 'E',
            '/[óòôõºö]/u' => 'o',
            '/[ÓÒÔÕÖ]/u' => 'O',
            '/[úùûü]/u' => 'u',
            '/[ÚÙÛÜ]/u' => 'U',
            '/ç/' => 'c',
            '/Ç/' => 'C',
            '/ñ/' => 'n',
            '/Ñ/' => 'N',
            '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => ' ', // Literally a single quote
            '/[“”«»„]/u' => ' ', // Double quote
            '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
        );
        foreach ($receiver->getGroups() as $group) {
            if (strpos(strtolower(preg_replace(array_keys($utf8), array_values($utf8), $group->getName())), $search) !== false || strpos(strtolower(preg_replace(array_keys($utf8), array_values($utf8), $group->getGrouptag())), $search)) {
                $groups[] = $group;
            }
        }

        return $this->view(array('groups' => GroupSerialization::serializeGroupSearchs($groups)), Codes::HTTP_OK);
    }

    /**
     * FR : Retourne tous les groupes matchant le paramètre
     * EN : Returns all groups according to the parameters
     *
     * GET /backoffice/groups/search?name=Blabla&select=false
     *
     * search contient le nom recherché encodé en format URL
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     * @View(serializerGroups={"GroupLess", "UserLess"})
     */
    public function getGroupsAdminSearchAction(Request $request)
    {
//        $this->authorize(null, 'ROLE_FDC');
        $search = urldecode($request->query->get('name', ''));

        $user = $this->getUser();
        $groups = array();
        $utf8 = array(
            '/[áàâãªä]/u' => 'a',
            '/[ÁÀÂÃÄ]/u' => 'A',
            '/[ÍÌÎÏ]/u' => 'I',
            '/[íìîï]/u' => 'i',
            '/[éèêë]/u' => 'e',
            '/[ÉÈÊË]/u' => 'E',
            '/[óòôõºö]/u' => 'o',
            '/[ÓÒÔÕÖ]/u' => 'O',
            '/[úùûü]/u' => 'u',
            '/[ÚÙÛÜ]/u' => 'U',
            '/ç/' => 'c',
            '/Ç/' => 'C',
            '/ñ/' => 'n',
            '/Ñ/' => 'N',
            '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => ' ', // Literally a single quote
            '/[“”«»„]/u' => ' ', // Double quote
            '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
        );
        foreach ($user->getAllGroups() as $group) {
            if (strpos(strtolower(preg_replace(array_keys($utf8), array_values($utf8), $group->getName())), $search) !== false || strpos(strtolower(preg_replace(array_keys($utf8), array_values($utf8), $group->getGrouptag())), $search)) {
                $groups[] = $group;
            }
        }

        return $this->view(array('groups' => GroupSerialization::serializeGroupSearchs($groups)), Codes::HTTP_OK);
    }

}

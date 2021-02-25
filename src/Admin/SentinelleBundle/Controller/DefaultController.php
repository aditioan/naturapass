<?php

namespace Admin\SentinelleBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{

    public function treeAction(Request $request)
    {
        $arrayTree = json_decode($request->get('tree'), true);
        $arrayTreeToRemove = json_decode($request->get('remove'), true);
        $em = $this->getDoctrine()->getManager();
        $repoCategory = $em->getRepository('AdminSentinelleBundle:Category');
//        echo "<pre>";
//        print_r($arrayTree);
//        echo "</pre>";
//        echo "--------------------------";
//        die();

        if (count($arrayTreeToRemove)) {
            $repoCategory->setTreeToRemoveModel($arrayTreeToRemove);
        }
        if (count($arrayTree)) {
            $repoCategory->setAllParentModelNull($arrayTree);
        }
        if (count($arrayTree)) {
            $repoCategory->setAllTreeModel($arrayTree);
//            $repoCategory->recover();
        }
        return $this->render('AdminSentinelleBundle:Default:angular.tree.html.twig');
    }

}

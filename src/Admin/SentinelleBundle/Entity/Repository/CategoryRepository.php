<?php

namespace Admin\SentinelleBundle\Entity\Repository;

use Api\ApiBundle\Controller\v2\Serialization\CategorySerialization;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Admin\SentinelleBundle\Entity\Category;
use Admin\SentinelleBundle\Entity\CardCategoryZone;
use Admin\SentinelleBundle\Entity\ReceiverRight;

class CategoryRepository extends NestedTreeRepository
{

    public function isLeaf(Category $category)
    {
        $arrayLeaf = $this->getLeafs($category);
        foreach ($arrayLeaf as $categoryLeaf) {
            if ($category->getId() == $categoryLeaf->getId()) {
                return true;
            }
        }
        return false;
    }

    public function setAllParentModelNull($model)
    {
//        $trees = $this->getAllCategoryOfTree($model);
        $categories = $this->findAll();
//        $trees = $this->findAll($model);
//        $q = $this->_em->createQueryBuilder()
//            ->update('AdminSentinelleBundle:Category', 'c')
//            ->set('c.parent', '?1')
////            ->set('c.root', '?2')
////            ->set('c.lft', '?3')
////            ->set('c.rgt', '?4')
////            ->set('c.lvl', '?5')
//            ->setParameter(1, null)
////            ->setParameter(2, 0)
////            ->setParameter(3, 0)
////            ->setParameter(4, 0)
////            ->setParameter(5, 0)
//            ->getQuery()
//            ->execute();
        foreach ($categories as $category) {
            $category->setParent(null);
            $this->_em->persist($category);
            $this->_em->flush();
        }
    }

    public function getAllCategoryOfTree($model)
    {
        $categories = array();
        foreach ($model as $category) {
            if (isset($category["id"]) && $category["id"] != "new") {
                $idCategory = intval($category["id"]);
                if (!in_array($idCategory, $categories)) {
                    $categories[] = $idCategory;
                    if (isset($category['nodes']) && count($category['nodes'])) {
                        foreach ($category['nodes'] as $node) {
                            $categories = array_merge($categories, $this->getAllCategoryOfTree(array($node)));
                        }
                    }
                }
            }
        }
        return $categories;
    }

    public function setTreeModel(&$model, $parent = null)
    {
        foreach ($model as $indCat => $category) {
            if (isset($category["id"])) {
                if ($category["id"] == "new") {
                    $categoryObject = new Category();
                } else {
                    $categoryObject = $this->find($category["id"]);
                }
                $categoryObject->setName($category["title"]);
                if (intval($category["visible"]) == 0) {
                    $categoryObject->setVisible(Category::VISIBLE_OFF);
                } else {
                    $categoryObject->setVisible(Category::VISIBLE_ON);
                }
                if (intval($category["search"]) == 1) {
                    $categoryObject->setSearch(1);
                } else {
                    $categoryObject->setSearch(0);
                }
                if (!is_null($parent)) {
                    $categoryObject->setParent($parent);
                }
                $this->_em->persist($categoryObject);
                $this->_em->flush();
                if ($category["id"] == "new") {
                    $model[$indCat]["id"] = $categoryObject->getId();
                    $category["id"] = $categoryObject->getId();
                }
                if (!is_null($parent)) {
                    $this->moveDown($categoryObject, true);
                }
                if (isset($category['nodes']) && count($category['nodes'])) {
                    $this->setTreeModel($model[$indCat]['nodes'], $categoryObject);
                }
            }
        }
    }

    public function setAllTreeModel(&$model, $parent = null)
    {
        foreach ($model as $indCat => $category) {
            if (isset($category["id"])) {
                if ($category["id"] == "new") {
                    $categoryObject = new Category();
                } else {
                    $categoryObject = $this->find($category["id"]);
                }
                $categoryObject->setName($category["title"]);
                if (intval($category["visible"]) == 0) {
                    $categoryObject->setVisible(Category::VISIBLE_OFF);
                } else {
                    $categoryObject->setVisible(Category::VISIBLE_ON);
                }
                if (intval($category["search"]) == 1) {
                    $categoryObject->setSearch(1);
                } else {
                    $categoryObject->setSearch(0);
                }
                if (!is_null($parent)) {
                    $categoryObject->setParent($parent);
                }
//                else {
//                    $qb = $this->_em->createQueryBuilder()->select('MAX(c.root) AS max_res')
//                        ->from('AdminSentinelleBundle:Category', 'c');
//                    $root = intval($qb->getQuery()->getResult()) + 1;
//                    $categoryObject->setRoot($root);
//                    $update = $this->_em->createQueryBuilder()
//                        ->update('AdminSentinelleBundle:Category', 'c')
//                        ->set('c.root', '?1')
//                        ->where('c.id = :id')
//                        ->setParameter(1, $root)
//                        ->setParameter("id", $categoryObject->getId())
//                        ->getQuery()
//                        ->execute();
//                }

                if (isset($category['card']) && isset($category['card']['id']) && isset($category['nodes']) && count($category['nodes']) == 0) {
                    $card = $this->_em->getRepository('AdminSentinelleBundle:Card')->find($category['card']['id']);
                    if (is_object($card)) {
                        $categoryObject->setCard($card);
                    }
                } else {
                    $categoryObject->setCard(null);
                }
                $categoryObject->removeAllGroups();
                if (isset($category['groups'])) {
                    foreach ($category['groups'] as $groupModel) {
                        if (isset($groupModel['id'])) {
                            $group = $this->_em->getRepository('NaturaPassGroupBundle:Group')->find($groupModel['id']);
                            if (is_object($group)) {
                                $categoryObject->addGroup($group);
                            }
                        }
                    }
                }
                $tree = CategorySerialization::serializeCategoryTree($categoryObject);
                $categoryObject->setPath(join('/', $tree));
                $this->_em->persist($categoryObject);
                $this->_em->flush();
                if ($category["id"] == "new") {
                    $model[$indCat]["id"] = $categoryObject->getId();
                    $category["id"] = $categoryObject->getId();
                }
                if (!is_null($parent)) {
//                    $this->recover();
                    $this->moveDown($categoryObject, true);
                }
            }
            if (isset($category['nodes']) && count($category['nodes'])) {
                $this->setAllTreeModel($model[$indCat]['nodes'], $categoryObject);
            }
        }
    }

    public function setTreeToRemoveModel($model)
    {
        foreach ($model as $category) {
            if (isset($category["id"]) && intval($category["remove"]) == 1) {
                $categoryObject = $this->find($category["id"]);
                if (is_object($categoryObject)) {
                    $this->_em->remove($categoryObject);
                    $this->_em->flush();
                }

            }
        }
        $this->recover();
    }

    public function setTreeGroupModel($model)
    {
        foreach ($model as $category) {
            if (isset($category["id"])) {
                $categoryObject = $this->find($category["id"]);
                $categoryObject->removeAllGroups();
                if (isset($category['groups'])) {
                    foreach ($category['groups'] as $groupModel) {
                        if (isset($groupModel['id'])) {
                            $group = $this->_em->getRepository('NaturaPassGroupBundle:Group')->find($groupModel['id']);
                            if (is_object($group)) {
                                $categoryObject->addGroup($group);
                            }
                        }
                    }
                }
                $this->_em->persist($categoryObject);
                $this->_em->flush();
                if (isset($category['nodes']) && count($category['nodes'])) {
                    foreach ($category['nodes'] as $node) {
                        $this->setTreeGroupModel(array($node));
                    }
                }
            }
        }
    }

    public function recursiveTree($categories)
    {
        $aCategorie = array();
        foreach ($categories as $category) {
            $aCategorie[] = $category->getId();
            $childrens = $this->children($category, true, 'name');
            foreach ($childrens as $node) {
                $aCategorie = array_merge($aCategorie, $this->recursiveTree(array($node)));
            }
        }
        return $aCategorie;
    }

    public function recursiveModel($model)
    {
        $aCategorie = array();
        foreach ($model as $category) {
            if (isset($category["id"])) {
                $categoryObject = $this->find($category["id"]);
                $aCategorie[] = $categoryObject->getId();
                if (isset($category['nodes']) && count($category['nodes'])) {
                    foreach ($category['nodes'] as $node) {
                        $aCategorie = array_merge($aCategorie, $this->recursiveModel(array($node)));
                    }
                }
            }
        }
        return $aCategorie;
    }

    public function setZoneRightModel($zone, $model)
    {
        $aCategory = $this->recursiveModel($model);
        return $aCategory;
    }

    public function setReceiverRightModel($receiver, $model)
    {
        foreach ($model as $category) {
            if (intval($category["visible"]) == 1) {
                if (isset($category["id"])) {
                    $categoryObject = $this->find($category["id"]);
                    $receiverRight = new ReceiverRight();
                    $receiverRight->setReceiver($receiver);
                    $receiverRight->setCategory($categoryObject);
                    $this->_em->persist($receiverRight);
                    $this->_em->flush();
                    if (isset($category['nodes']) && count($category['nodes'])) {
                        foreach ($category['nodes'] as $node) {
                            $array = array($node);
                            $this->setReceiverRightModel($receiver, $array);
                        }
                    }
                }
            }
        }
    }

    public function setTreeZoneCardModel($zone, $model)
    {
        foreach ($model as $category) {
            if (isset($category["id"])) {
                $categoryObject = $this->find($category["id"]);
                if (isset($category['card']) && isset($category['card']['id']) && isset($category['nodes']) && count($category['nodes']) == 0) {
                    $card = $this->_em->getRepository('AdminSentinelleBundle:Card')->find($category['card']['id']);
                    $CardCategoryZone = new CardCategoryZone();
                    $CardCategoryZone->setCard($card);
                    $CardCategoryZone->setZone($zone);
                    $CardCategoryZone->setCategory($categoryObject);
                    $CardCategoryZone->setVisible(CardCategoryZone::VISIBLE_ON);
                    $this->_em->persist($CardCategoryZone);
                    $this->_em->flush();
                }
                if (isset($category['nodes']) && count($category['nodes'])) {
                    foreach ($category['nodes'] as $node) {
                        $this->setTreeZoneCardModel($zone, array($node));
                    }
                }
            }
        }
    }

    public function setTreeCardModel($model)
    {
        foreach ($model as $category) {
            if (isset($category["id"])) {
                $categoryObject = $this->find($category["id"]);
                if (isset($category['card']) && isset($category['card']['id']) && isset($category['nodes']) && count($category['nodes']) == 0) {
                    $card = $this->_em->getRepository('AdminSentinelleBundle:Card')->find($category['card']['id']);
                    if (is_object($card)) {
                        $categoryObject->setCard($card);
                    }
                } else {
                    $categoryObject->setCard(null);
                }
                $this->_em->persist($categoryObject);
                $this->_em->flush();
                if (isset($category['nodes']) && count($category['nodes'])) {
                    foreach ($category['nodes'] as $node) {
                        $this->setTreeCardModel(array($node));
                    }
                }
            }
        }
    }


    /**
     * {@inheritDoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $qb = $this->getQueryBuilder();
        $qb
            ->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->isNull('node.' . $config['parent']));

        if ($sortByField !== null) {
            $qb->orderBy('node.' . $sortByField . ',node.root', strtolower($direction) === 'asc' ? 'asc' : 'desc');
        } else {
            $qb->orderBy('node.' . $config['left'], 'ASC');
        }

        return $qb;
    }

}

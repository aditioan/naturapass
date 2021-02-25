<?php

namespace Admin\AnimalBundle\Entity\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Admin\AnimalBundle\Entity\Animal;

class AnimalRepository extends NestedTreeRepository {

    public function isLeaf(Animal $animal) {
        $arrayLeaf = $this->getLeafs($animal);
        foreach ($arrayLeaf as $animalLeaf) {
            if ($animal->getId() == $animalLeaf->getId()) {
                return true;
            }
        }
        return false;
    }

    public function deleteTreeModel() {
        $qb = $this->getQueryBuilder();
        $results = $qb->select('a')
                ->from('AdminAnimalBundle:Animal', 'a')
                ->getQuery()
                ->getResult();
        foreach ($results as $animal) {
            $this->_em->remove($animal);
            $this->_em->flush();
        }
    }

    public function setTreeModel($model, $parent = null) {
        foreach ($model as $animal) {
            if (isset($animal["id"])) {
                $animalObject = new Animal();
                $animalObject->setName_fr($animal["title"]);
                if (!is_null($parent)) {
                    $animalObject->setParent($parent);
                }
                $this->_em->persist($animalObject);
                $this->_em->flush();
                if (!is_null($parent)) {
                    $this->moveDown($animalObject, true);
                }
                if (isset($animal['nodes']) && count($animal['nodes'])) {
                    foreach ($animal['nodes'] as $node) {
                        $array = array($node);
                        $this->setTreeModel($array, $animalObject);
                    }
                }
            }
        }
    }

}

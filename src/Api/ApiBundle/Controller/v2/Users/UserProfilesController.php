<?php

/**
 * Created by PhpStorm.
 * Date: 29/07/15
 * Time: 09:11
 */

namespace Api\ApiBundle\Controller\v2\Users;

use Admin\SentinelleBundle\Entity\Locality;
use Api\ApiBundle\Controller\v2\ApiRestController;
use Api\ApiBundle\Controller\v2\Serialization\DogSerialization;
use Api\ApiBundle\Controller\v2\Serialization\HuntLocationSerialization;
use Api\ApiBundle\Controller\v2\Serialization\HuntTypeSerialization;
use Api\ApiBundle\Controller\v2\Serialization\UserSerialization;
use Api\ApiBundle\Controller\v2\Serialization\WeaponSerialization;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use FOS\RestBundle\Util\Codes;
use NaturaPass\MainBundle\Entity\Country;
use NaturaPass\UserBundle\Entity\DogMedia;
use NaturaPass\UserBundle\Entity\DogParameter;
use NaturaPass\UserBundle\Entity\HuntCityParameter;
use NaturaPass\UserBundle\Entity\HuntCountryParameter;
use NaturaPass\UserBundle\Entity\HuntType;
use NaturaPass\UserBundle\Entity\HuntTypeParameter;
use NaturaPass\UserBundle\Entity\PaperMedia;
use NaturaPass\UserBundle\Entity\PaperModel;
use NaturaPass\UserBundle\Entity\PaperParameter;
use NaturaPass\UserBundle\Entity\User;
use NaturaPass\UserBundle\Entity\WeaponMedia;
use NaturaPass\UserBundle\Entity\WeaponParameter;
use NaturaPass\UserBundle\Form\Handler\DogHandler;
use NaturaPass\UserBundle\Form\Handler\PaperHandler;
use NaturaPass\UserBundle\Form\Handler\WeaponHandler;
use NaturaPass\UserBundle\Form\Type\DogParameterType;
use NaturaPass\UserBundle\Form\Type\PaperParameterType;
use NaturaPass\UserBundle\Form\Type\WeaponType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class UserProfilesController extends ApiRestController
{


    /**
     * Get user calibres (weapon)
     *
     * GET /v2/user/profile/weapons/calibre?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileWeaponsCalibreAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('c')
            ->from('NaturaPassUserBundle:WeaponCalibre', 'c')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $weaponCalibres = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($weaponCalibres as $weaponCalibre) {
            $returns[] = WeaponSerialization::serializeWeaponCalibre($weaponCalibre);
        }

        return $this->view(array('calibres' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user brands (weapon)
     *
     * GET /v2/user/profile/weapons/brand?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileWeaponsBrandAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('NaturaPassUserBundle:WeaponBrand', 'b')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $weaponBrands = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($weaponBrands as $weaponBrand) {
            $returns[] = WeaponSerialization::serializeWeaponBrand($weaponBrand);
        }

        return $this->view(array('brands' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user weapons
     *
     * GET /v2/user/profile/weapons?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileWeaponsAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('w')
            ->from('NaturaPassUserBundle:WeaponParameter', 'w')
            ->where('w.owner = :owner')
            ->setParameter('owner', $this->getUser())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $weapons = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($weapons as $weapon) {
            $returns[] = WeaponSerialization::serializeWeapon($weapon);
        }

        return $this->view(array('weapons' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user weapon
     *
     * GET /v2/users/{ID_WEAPON}/profile/weapon?limit=20&offset=0
     *
     * @param WeaponParameter $weapon
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileWeaponAction(WeaponParameter $weapon)
    {
        $this->authorize();
        $this->authorizeWeapon($weapon);

        return $this->view(array('weapon' => WeaponSerialization::serializeWeapon($weapon)), Codes::HTTP_OK);
    }

    /**
     * add a new weapon
     *
     * POST /v2/users/profiles/weapons
     *
     * Content-Type: form-data
     *      weapon[name] = "my gun"
     *      weapon[calibre] = 1
     *      weapon[brand] = 3
     *      weapon[type] = [1 => CARABINE, 0 => SHOTGUN]
     *      weapon[photo][file] = Données de photo
     *      weapon[medias][1000][file] = Document
     *      weapon[medias][1001][file] = Document
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfileWeaponAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new WeaponType($this->getUser(), $this->container), new WeaponParameter(), array('csrf_protection' => false));
        $handler = new WeaponHandler($form, $request, $this->getDoctrine()->getManager());
        if ($weapon = $handler->process()) {
            return $this->view(
                array(
                    'weapon' => WeaponSerialization::serializeWeapon($weapon),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a weapon
     *
     * POST /v2/users/{ID_WEAPON]/profiles/weapons/media
     *
     * Content-Type: form-data
     *      weapon[name] = "my gun"
     *      weapon[calibre] = 1
     *      weapon[brand] = 3
     *      weapon[type] = [1 => CARABINE, 0 => SHOTGUN]
     *      weapon[photo][file] = Données de photo
     *      weapon[medias][1000][file] = Document
     *      weapon[medias][1001][file] = Document
     *
     * @param Request $request
     *
     * @ParamConverter("weapon", class="NaturaPassUserBundle:WeaponParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfileWeaponMediaAction(WeaponParameter $weapon, Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new WeaponType($this->getUser(), $this->container), $weapon, array('csrf_protection' => false));
        $handler = new WeaponHandler($form, $request, $this->getDoctrine()->getManager());
        if ($weapon = $handler->process()) {
            return $this->view(
                array(
                    'weapon' => WeaponSerialization::serializeWeapon($weapon),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a weapon
     *
     * PUT /v2/users/{ID_WEAPON]/profile/weapon
     *
     * Content-Type: form-data
     *      weapon[name] = "my gun"
     *      weapon[calibre] = 1
     *      weapon[brand] = 3
     *      weapon[type] = [1 => CARABINE, 0 => SHOTGUN]
     *      weapon[photo][file] = Données de photo
     *      weapon[medias][1000][file] = Document
     *      weapon[medias][1001][file] = Document
     *
     * @param WeaponParameter $weapon
     * @param Request $request
     *
     * @throws HttpException
     *
     * @ParamConverter("weapon", class="NaturaPassUserBundle:WeaponParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putProfileWeaponAction(WeaponParameter $weapon, Request $request)
    {
        $this->authorize();
        $this->authorizeWeapon($weapon);
        $form = $this->createForm(new WeaponType($this->getUser(), $this->container), $weapon, array('csrf_protection' => false, 'method' => 'PUT'));
        $handler = new WeaponHandler($form, $request, $this->getDoctrine()->getManager());
        if ($weapon = $handler->process()) {
            return $this->view(
                array(
                    'weapon' => WeaponSerialization::serializeWeapon($weapon),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * delete a weapon
     *
     * DELETE /v2/users/{ID_WEAPON}/profile/weapon
     *
     * @param WeaponParameter $weapon
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("weapon", class="NaturaPassUserBundle:WeaponParameter")
     */
    public function deleteProfileWeaponAction(WeaponParameter $weapon)
    {
        $this->authorize();
        $this->authorizeWeapon($weapon);

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($weapon);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * delete a weapon media
     *
     * DELETE /v2/users/{ID_WEAPON_MEDIA}/profile/weapon/media
     *
     * @param WeaponMedia $weaponmedia
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("weaponmedia", class="NaturaPassUserBundle:WeaponMedia")
     */
    public function deleteProfileWeaponMediaAction(WeaponMedia $weaponmedia)
    {
        $this->authorize();
        $this->authorizeWeapon($weaponmedia->getWeapon());

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($weaponmedia);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Get user breed (dog)
     *
     * GET /v2/user/profile/dogs/breed?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileDogsBreedAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('b')
            ->from('NaturaPassUserBundle:DogBreed', 'b')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $dogBreeds = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($dogBreeds as $dogBreed) {
            $returns[] = DogSerialization::serializeDogBreed($dogBreed);
        }

        return $this->view(array('breeds' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user type (dog)
     *
     * GET /v2/user/profile/dogs/type?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileDogsTypeAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('t')
            ->from('NaturaPassUserBundle:DogType', 't')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $dogTypes = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($dogTypes as $dogType) {
            $returns[] = DogSerialization::serializeDogType($dogType);
        }

        return $this->view(array('types' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user dogs
     *
     * GET /v2/user/profile/dogs?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileDogsAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('d')
            ->from('NaturaPassUserBundle:DogParameter', 'd')
            ->where('d.owner = :owner')
            ->setParameter('owner', $this->getUser())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $dogs = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($dogs as $dog) {
            $returns[] = DogSerialization::serializeDog($dog);
        }

        return $this->view(array('dogs' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user dog
     *
     * GET /v2/users/{ID_DOG}/profile/dog
     *
     * @param DogParameter $dog
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileDogAction(DogParameter $dog)
    {
        $this->authorize();
        $this->authorizeDog($dog);

        return $this->view(array('dog' => DogSerialization::serializeDog($dog)), Codes::HTTP_OK);
    }

    /**
     * add a new dog
     *
     * POST /v2/users/profiles/dogs
     *
     * Content-Type: form-data
     *      dog[name] = "my gun"
     *      dog[breed] = 1
     *      dog[type] = 3
     *      dog[birthday] = "2015-10-21T13:55:27+02:00"
     *      dog[sex] = [1 => FEMALE, 0 => MALE]
     *      dog[photo][file] = Photo
     *      dog[medias][1000][file] = Document
     *      dog[medias][1001][file] = Document
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfileDogAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new DogParameterType($this->getUser(), $this->container), new DogParameter(), array('csrf_protection' => false));
        $handler = new DogHandler($form, $request, $this->getDoctrine()->getManager());
        if ($dog = $handler->process()) {
            return $this->view(
                array(
                    'dog' => DogSerialization::serializeDog($dog),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a dog
     *
     * POST /v2/users/{ID_DOG]/profiles/dogs/media
     *
     * Content-Type: form-data
     *      dog[name] = "my gun"
     *      dog[breed] = 1
     *      dog[type] = 3
     *      dog[birthday] = "2015-10-21T13:55:27+02:00"
     *      dog[sex] = [1 => FEMALE, 0 => MALE]
     *      dog[photo][file] = Photo
     *      dog[medias][1000][file] = Document
     *      dog[medias][1001][file] = Document
     *
     * @param Request $request
     *
     * @ParamConverter("dog", class="NaturaPassUserBundle:DogParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfileDogMediaAction(DogParameter $dog, Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new DogParameterType($this->getUser(), $this->container), $dog, array('csrf_protection' => false));
        $handler = new DogHandler($form, $request, $this->getDoctrine()->getManager());
        if ($dog = $handler->process()) {
            return $this->view(
                array(
                    'dog' => DogSerialization::serializeDog($dog),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a dog
     *
     * PUT /v2/users/{ID_DOG]/profile/dog
     *
     * Content-Type: form-data
     *      dog[name] = "my gun"
     *      dog[breed] = 1
     *      dog[type] = 3
     *      dog[birthday] = "2015-10-21T13:55:27+02:00"
     *      dog[sex] = [1 => FEMALE, 0 => MALE]
     *      dog[photo][file] = Photo
     *      dog[medias][1000][file] = Document
     *      dog[medias][1001][file] = Document
     *
     * @param DogParameter $dog
     * @param Request $request
     *
     * @throws HttpException
     *
     * @ParamConverter("dog", class="NaturaPassUserBundle:DogParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putProfileDogAction(DogParameter $dog, Request $request)
    {
        $this->authorize();
        $this->authorizeDog($dog);
        $form = $this->createForm(new DogParameterType($this->getUser(), $this->container), $dog, array('csrf_protection' => false, 'method' => 'PUT'));
        $handler = new DogHandler($form, $request, $this->getDoctrine()->getManager());
        if ($dog = $handler->process()) {
            return $this->view(
                array(
                    'dog' => DogSerialization::serializeDog($dog),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * delete a dog
     *
     * DELETE /v2/users/{ID_DOG}/profile/dog
     *
     * @param DogParameter $dog
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("dog", class="NaturaPassUserBundle:DogParameter")
     */
    public function deleteProfileDogAction(DogParameter $dog)
    {
        $this->authorize();
        $this->authorizeDog($dog);

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($dog);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * delete a dog media
     *
     * DELETE /v2/users/{ID_DOG_MEDIA}/profile/dog/media
     *
     * @param int $dogmedia
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("dogmedia", class="NaturaPassUserBundle:DogMedia")
     */
    public function deleteProfileDogMediaAction($dogmedia)
    {
        $this->authorize();
        $dog = $dogmedia->getDog();
        $this->authorizeDog($dog);

        $manager = $this->getDoctrine()->getManager();
        $dog->removeMedia($dogmedia);
        $manager->persist($dogmedia);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Get user hunt practiced
     *
     * GET /v2/user/profile/hunttype/practiced
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileHunttypePracticedAction(Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $hunttype = $manager->getRepository('NaturaPassUserBundle:HuntTypeParameter')->findOneBy(array('owner' => $this->getUser(), 'type' => HuntTypeParameter::TYPE_PRACTICED));
        $returns = array();
        if (!is_null($hunttype)) {
            $returns = HuntTypeSerialization::serializeHuntType($hunttype);
        }
        return $this->view(array('practiced' => $returns), Codes::HTTP_OK);
    }

    /**
     * add an hunt practiced
     *
     * POST /v2/users/{ID_HUNTTYPE}/profile/hunttypes/practiceds
     *
     * @param HuntType $hunttype_sub
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("hunttype_sub", class="NaturaPassUserBundle:HuntType")
     */
    public function postProfileHunttypePracticedAction(HuntType $hunttype_sub)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $hunttype = $manager->getRepository('NaturaPassUserBundle:HuntTypeParameter')->findOneBy(array('owner' => $this->getUser(), 'type' => HuntTypeParameter::TYPE_PRACTICED));
        if (is_null($hunttype)) {
            $hunttype = new HuntTypeParameter();
            $hunttype->setOwner($this->getUser());
            $hunttype->setType(HuntTypeParameter::TYPE_PRACTICED);
            $manager->persist($hunttype);
            $manager->flush();
        }
        if (!is_null($hunttype)) {
            if (!$hunttype->getHunttypes()->contains($hunttype_sub)) {
                $hunttype->addHunttype($hunttype_sub);
                $manager->persist($hunttype);
                $manager->flush();
            }
            return $this->view(array('practiced' => HuntTypeSerialization::serializeHuntType($hunttype)), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * delete an hunt practiced
     *
     * DELETE /v2/users/{ID_HUNTTYPE}/profile/hunttype/practiced
     *
     * @param HuntType $hunttype_sub
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("hunttype_sub", class="NaturaPassUserBundle:HuntType")
     */
    public function deleteProfileHunttypePracticedAction(HuntType $hunttype_sub)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $hunttype = $manager->getRepository('NaturaPassUserBundle:HuntTypeParameter')->findOneBy(array('owner' => $this->getUser(), 'type' => HuntTypeParameter::TYPE_PRACTICED));
        if (!is_null($hunttype)) {
            if ($hunttype->getHunttypes()->contains($hunttype_sub)) {
                $hunttype->removeHunttype($hunttype_sub);
                $manager->persist($hunttype);
                $manager->flush();
                return $this->view($this->success(), Codes::HTTP_OK);
            }
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Get user hunt liked
     *
     * GET /v2/user/profile/hunttype/liked
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileHunttypeLikedAction(Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $hunttype = $manager->getRepository('NaturaPassUserBundle:HuntTypeParameter')->findOneBy(array('owner' => $this->getUser(), 'type' => HuntTypeParameter::TYPE_LIKED));
        $returns = array();
        if (!is_null($hunttype)) {
            $returns = HuntTypeSerialization::serializeHuntType($hunttype);
        }

        return $this->view(array('liked' => $returns), Codes::HTTP_OK);
    }

    /**
     * add an hunt liked
     *
     * POST /v2/users/{ID_HUNTTYPE}/profiles/hunttypes/likeds
     *
     * @param HuntType $hunttype_sub
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("hunttype_sub", class="NaturaPassUserBundle:HuntType")
     */
    public function postProfileHunttypeLikedAction(HuntType $hunttype_sub)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $hunttype = $manager->getRepository('NaturaPassUserBundle:HuntTypeParameter')->findOneBy(array('owner' => $this->getUser(), 'type' => HuntTypeParameter::TYPE_LIKED));
        if (is_null($hunttype)) {
            $hunttype = new HuntTypeParameter();
            $hunttype->setOwner($this->getUser());
            $hunttype->setType(HuntTypeParameter::TYPE_LIKED);
            $manager->persist($hunttype);
            $manager->flush();
        }
        if (!is_null($hunttype)) {
            if (!$hunttype->getHunttypes()->contains($hunttype_sub)) {
                $hunttype->addHunttype($hunttype_sub);
                $manager->persist($hunttype);
                $manager->flush();
            }
            return $this->view(array('liked' => HuntTypeSerialization::serializeHuntType($hunttype)), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * delete an hunt liked
     *
     * DELETE /v2/users/{ID_HUNTTYPE}/profile/hunttype/liked
     *
     * @param HuntType $hunttype_sub
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("hunttype_sub", class="NaturaPassUserBundle:HuntType")
     */
    public function deleteProfileHunttypeLikedAction(HuntType $hunttype_sub)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $hunttype = $manager->getRepository('NaturaPassUserBundle:HuntTypeParameter')->findOneBy(array('owner' => $this->getUser(), 'type' => HuntTypeParameter::TYPE_LIKED));
        if (!is_null($hunttype)) {
            if ($hunttype->getHunttypes()->contains($hunttype_sub)) {
                $hunttype->removeHunttype($hunttype_sub);
                $manager->persist($hunttype);
                $manager->flush();
                return $this->view($this->success(), Codes::HTTP_OK);
            }
        }
        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * Get user hunt city location
     *
     * GET /v2/user/profile/huntlocation/city
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileHuntlocationCityAction(Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $huntlocation = $manager->getRepository('NaturaPassUserBundle:HuntCityParameter')->findBy(array('owner' => $this->getUser()));
        $returns = HuntLocationSerialization::serializeHuntCitys($huntlocation);
        return $this->view(array('cities' => $returns), Codes::HTTP_OK);
    }

    /**
     * add an hunt city location
     *
     * POST /v2/users/{ID_LOCALITY}/profiles/huntlocations/cities
     *
     * @param Locality $locality
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("locality", class="AdminSentinelleBundle:Locality")
     */
    public function postProfileHuntlocationCitieAction(Locality $locality)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $huntlocation = $manager->getRepository('NaturaPassUserBundle:HuntCityParameter')->findOneBy(array('owner' => $this->getUser(), 'city' => $locality));
        if (is_null($huntlocation)) {
            $huntlocation = new HuntCityParameter();
            $huntlocation->setCity($locality);
            $huntlocation->setOwner($this->getUser());
            $manager->persist($huntlocation);
            $manager->flush();
            return $this->view(array('city' => HuntLocationSerialization::serializeHuntCity($huntlocation)), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * delete an hunt city location
     *
     * DELETE /v2/users/{ID_LOCALITY}/profile/huntlocation/city
     *
     * @param Locality $locality
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("locality", class="AdminSentinelleBundle:Locality")
     */
    public function deleteProfileHuntlocationCityAction(Locality $locality)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $huntlocation = $manager->getRepository('NaturaPassUserBundle:HuntCityParameter')->findOneBy(array('owner' => $this->getUser(), 'city' => $locality));
        if (!is_null($huntlocation)) {
            $manager->remove($huntlocation);
            $manager->flush();
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Get user hunt country location
     *
     * GET /v2/user/profile/huntlocation/country
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileHuntlocationCountryAction(Request $request)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $huntlocation = $manager->getRepository('NaturaPassUserBundle:HuntCountryParameter')->findBy(array('owner' => $this->getUser()));
        $returns = HuntLocationSerialization::serializeHuntCountrys($huntlocation);
        return $this->view(array('countries' => $returns), Codes::HTTP_OK);
    }

    /**
     * add an hunt country location
     *
     * POST /v2/users/{ID_COUNTRY}/profiles/huntlocations/countries
     *
     * @param Country $country
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("country", class="NaturaPassMainBundle:Country")
     */
    public function postProfileHuntlocationCountryAction(Country $country)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $huntlocation = $manager->getRepository('NaturaPassUserBundle:HuntCountryParameter')->findOneBy(array('owner' => $this->getUser(), 'country' => $country));
        if (is_null($huntlocation)) {
            $huntlocation = new HuntCountryParameter();
            $huntlocation->setCountry($country);
            $huntlocation->setOwner($this->getUser());
            $manager->persist($huntlocation);
            $manager->flush();
            return $this->view(array('country' => HuntLocationSerialization::serializeHuntCountry($huntlocation)), Codes::HTTP_OK);
        }

        throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.parameters'));
    }

    /**
     * delete an hunt country location
     *
     * DELETE /v2/users/{ID_COUNTRY}/profile/huntlocation/country
     *
     * @param Country $country
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("country", class="NaturaPassMainBundle:Country")
     */
    public function deleteProfileHuntlocationCountryAction(Country $country)
    {
        $this->authorize();
        $manager = $this->getDoctrine()->getManager();
        $huntlocation = $manager->getRepository('NaturaPassUserBundle:HuntCountryParameter')->findOneBy(array('owner' => $this->getUser(), 'country' => $country));
        if (!is_null($huntlocation)) {
            $manager->remove($huntlocation);
            $manager->flush();
        }
        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Get user papers
     *
     * GET /v2/user/profile/papers?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfilePapersAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $models = $manager->getRepository('NaturaPassUserBundle:PaperModel')->findAll();
        foreach ($models as $model) {
            $paperExist = $manager->getRepository('NaturaPassUserBundle:PaperParameter')->findOneBy(array("owner" => $this->getUser(), "name" => $model->getName()));
            if (is_null($paperExist)) {
                $paperExist = new PaperParameter();
                $paperExist->setOwner($this->getUser());
                $paperExist->setType($model->getType());
                $paperExist->setDeletable(PaperParameter::NO_DELETABLE);
                $paperExist->setName($model->getName());
                $manager->persist($paperExist);
                $manager->flush();
            }
        }

        $qb = $manager->createQueryBuilder()->select('p')
            ->from('NaturaPassUserBundle:PaperParameter', 'p')
            ->where('p.owner = :owner')
            ->setParameter('owner', $this->getUser())
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $papers = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($papers as $paper) {
            $title = "Description";
            if (!$paper->getDeletable()) {
                $model = $manager->getRepository("NaturaPassUserBundle:PaperModel")->findOneBy(array("name" => $paper->getName()));
                if (!is_null($model) && $model instanceof PaperModel) {
                    $title = $model->getTitle();
                }
            }
            $returns[] = UserSerialization::serializePaper($paper, $title);
        }

        return $this->view(array('papers' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get user paper
     *
     * GET /v2/users/{ID_PAPER}/profile/paper
     *
     * @param PaperParameter $paper
     *
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfilePaperAction(PaperParameter $paper)
    {
        $this->authorize();
        $this->authorizePaper($paper);
        $title = "Description";
        if (!$paper->getDeletable()) {
            $model = $this->getDoctrine()->getManager()->getRepository("NaturaPassUserBundle:PaperModel")->findOneBy(array("name" => $paper->getName()));
            if (!is_null($model) && $model instanceof PaperModel) {
                $title = $model->getTitle();
            }
        }
        return $this->view(array('paper' => UserSerialization::serializePaper($paper, $title)), Codes::HTTP_OK);
    }

    /**
     * add a new paper
     *
     * POST /v2/users/profiles/papers
     *
     * Content-Type: form-data
     *      paper[name] = "paper"
     *      paper[text] = "description"
     *      paper[medias][1000][file] = Document
     *
     * @param Request $request
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfilePaperAction(Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new PaperParameterType($this->getUser(), $this->container), new PaperParameter(), array('csrf_protection' => false));
        $handler = new PaperHandler($form, $request, $this->getDoctrine()->getManager());
        if ($paper = $handler->process()) {
            $title = "Description";
            if (!$paper->getDeletable()) {
                $model = $this->getDoctrine()->getManager()->getRepository("NaturaPassUserBundle:PaperModel")->findOneBy(array("name" => $paper->getName()));
                if (!is_null($model) && $model instanceof PaperModel) {
                    $title = $model->getTitle();
                }
            }
            return $this->view(
                array(
                    'paper' => UserSerialization::serializePaper($paper, $title),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a paper
     *
     * POST /v2/users/{ID_PAPER}/profiles/papers/media
     *
     * Content-Type: form-data
     *      paper[name] = "paper"
     *      paper[text] = "description"
     *      paper[medias][1000][file] = Document
     *
     * @param Request $request
     *
     * @ParamConverter("paper", class="NaturaPassUserBundle:PaperParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfilePaperMediaAction(PaperParameter $paper, Request $request)
    {
        $this->authorize();
        $this->authorizePaper($paper);
        $mediaToDelete = null;
        if (!empty($_FILES)) {
            $mediaToDelete = $paper->getMedias()->first();
        }
        $form = $this->createForm(new PaperParameterType($this->getUser(), $this->container), $paper, array('csrf_protection' => false));
        $handler = new PaperHandler($form, $request, $this->getDoctrine()->getManager());
        if ($paper = $handler->process()) {
            if (!is_null($mediaToDelete) && $mediaToDelete instanceof PaperMedia) {
                $manager = $this->getDoctrine()->getManager();
                $manager->remove($mediaToDelete);
                $manager->flush();
            }
            $title = "Description";
            if (!$paper->getDeletable()) {
                $model = $this->getDoctrine()->getManager()->getRepository("NaturaPassUserBundle:PaperModel")->findOneBy(array("name" => $paper->getName()));
                if (!is_null($model) && $model instanceof PaperModel) {
                    $title = $model->getTitle();
                }
            }
            return $this->view(
                array(
                    'paper' => UserSerialization::serializePaper($paper, $title),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * edit a paper
     *
     * PUT /v2/users/{ID_PAPER}/profile/paper
     *
     * Content-Type: form-data
     *      paper[name] = "paper"
     *      paper[text] = "description"
     *      paper[medias][1000][file] = Document
     *
     * @param PaperParameter $paper
     * @param Request $request
     *
     * @throws HttpException
     *
     * @ParamConverter("paper", class="NaturaPassUserBundle:PaperParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function putProfilePaperAction(PaperParameter $paper, Request $request)
    {
        $this->authorize();
        $this->authorizePaper($paper);
        $mediaToDelete = null;
        if (!empty($_FILES)) {
            $mediaToDelete = $paper->getMedias()->first();
        }
        $form = $this->createForm(new PaperParameterType($this->getUser(), $this->container), $paper, array('csrf_protection' => false, 'method' => 'PUT'));
        $handler = new PaperHandler($form, $request, $this->getDoctrine()->getManager());
        if ($dog = $handler->process()) {
            if (!is_null($mediaToDelete) && $mediaToDelete instanceof PaperMedia) {
                $manager = $this->getDoctrine()->getManager();
                $manager->remove($mediaToDelete);
                $manager->flush();
            }
            $title = "Description";
            if (!$paper->getDeletable()) {
                $model = $this->getDoctrine()->getManager()->getRepository("NaturaPassUserBundle:PaperModel")->findOneBy(array("name" => $paper->getName()));
                if (!is_null($model) && $model instanceof PaperModel) {
                    $title = $model->getTitle();
                }
            }
            return $this->view(
                array(
                    'paper' => UserSerialization::serializePaper($paper, $title),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

    /**
     * delete a paper
     *
     * DELETE /v2/users/{ID_PAPER}/profile/paper
     *
     * @param PaperParameter $paper
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("paper", class="NaturaPassUserBundle:PaperParameter")
     */
    public function deleteProfilePaperAction(PaperParameter $paper)
    {
        $this->authorize();
        $this->authorizePaper($paper);

        $manager = $this->getDoctrine()->getManager();
        if ($paper->getDeletable() == PaperParameter::DELETABLE) {
            $manager->remove($paper);
            $manager->flush();
            return $this->view($this->success(), Codes::HTTP_OK);
        } else {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, $this->message('errors.undeletable'));
        }

    }

    /**
     * delete a paper media
     *
     * DELETE /v2/users/{ID_PAPER_MEDIA}/profile/paper/media
     *
     * @param PaperMedia $papermedia
     * @return \FOS\RestBundle\View\View
     *
     * @ParamConverter("papermedia", class="NaturaPassUserBundle:PaperMedia")
     */
    public function deleteProfilePaperMediaAction(PaperMedia $papermedia)
    {
        $this->authorize();
        $this->authorizePaper($papermedia->getPaper());

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($papermedia);
        $manager->flush();

        return $this->view($this->success(), Codes::HTTP_OK);
    }

    /**
     * Get Hunt type
     *
     * GET /v2/user/profile/hunttype?limit=20&offset=0
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileHunttypeAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('h')
            ->from('NaturaPassUserBundle:HuntType', 'h')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $hunttypes = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($hunttypes as $hunttype) {
            $returns[] = UserSerialization::serializeHunttype($hunttype, $this->getUser());
        }

        return $this->view(array('hunttypes' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get cities
     *
     * GET /v2/user/profile/city?limit=20&offset=0&filter=charnoz
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileCityAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('l')
            ->from('AdminSentinelleBundle:locality', 'l');
        if ($filter) {
            $qb->where('l.name LIKE :filter')
                ->setParameter('filter', "%" . $filter . "%");
        }
        $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->distinct('l.name')
            ->getQuery();

        $returns = array();
        $cities = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($cities as $city) {
            $array = array(
                "id" => $city->getId(),
                "name" => $city->getName(),
                "added" => !is_null($manager->getRepository('NaturaPassUserBundle:HuntCityParameter')->findOneBy(array('owner' => $this->getUser(), 'city' => $city))) ? 1 : 0
            );
            $returns[] = $array;
        }

        return $this->view(array('cities' => $returns), Codes::HTTP_OK);
    }

    /**
     * Get countries
     *
     * GET /v2/user/profile/country?limit=20&offset=0&filter=france
     *
     *
     * @param Request $request
     * @return \FOS\RestBundle\View\View
     *
     */
    public function getProfileCountryAction(Request $request)
    {
        $this->authorize();
        $limit = $request->query->get('limit', 10);
        $offset = $request->query->get('offset', 0);
        $filter = $request->query->get('filter', false);

        $manager = $this->getDoctrine()->getManager();
        $qb = $manager->createQueryBuilder()->select('c')
            ->from('NaturaPassMainBundle:Country', 'c');
        if ($filter) {
            $qb->where('c.name LIKE :filter')
                ->setParameter('filter', "%" . $filter . "%");
        }
        $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $returns = array();
        $countries = new Paginator($qb, $fetchJoinCollection = true);
        foreach ($countries as $country) {
            $returns[] = array(
                "id" => $country->getId(),
                "name" => $country->getName(),
                "added" => !is_null($manager->getRepository('NaturaPassUserBundle:HuntCountryParameter')->findOneBy(array('owner' => $this->getUser(), 'country' => $country))) ? 1 : 0
            );
        }

        return $this->view(array('countries' => $returns), Codes::HTTP_OK);
    }

    /**
     * add pic a dog
     *
     * POST /v2/users/{ID_DOG]/profiles/addpicdogs/media
     *
     * Content-Type: form-data
     *      dog[medias][1000][file] = Document
     *      dog[medias][1001][file] = Document
     *
     * @param Request $request
     *
     * @ParamConverter("dog", class="NaturaPassUserBundle:DogParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfileAddpicdogsMediaAction(DogParameter $dog, Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new DogParameterType($this->getUser(), $this->container), $dog, array('csrf_protection' => false));
        $handler = new DogHandler($form, $request, $this->getDoctrine()->getManager());
        if ($dog = $handler->process()) {
            return $this->view(
                array(
                    'dog' => DogSerialization::serializeLastpicDog($dog),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }

/**
     * edit a weapon
     *
     * POST /v2/users/{ID_WEAPON]/profiles/addpicweapons/media
     *
     * Content-Type: form-data
     *      weapon[name] = "my gun"
     *      weapon[calibre] = 1
     *      weapon[brand] = 3
     *      weapon[type] = [1 => CARABINE, 0 => SHOTGUN]
     *      weapon[photo][file] = Données de photo
     *      weapon[medias][1000][file] = Document
     *      weapon[medias][1001][file] = Document
     *
     * @param Request $request
     *
     * @ParamConverter("weapon", class="NaturaPassUserBundle:WeaponParameter")
     *
     * @return \FOS\RestBundle\View\View
     */
    public function postProfileAddpicweaponsMediaAction(WeaponParameter $weapon, Request $request)
    {
        $this->authorize();
        $form = $this->createForm(new WeaponType($this->getUser(), $this->container), $weapon, array('csrf_protection' => false));
        $handler = new WeaponHandler($form, $request, $this->getDoctrine()->getManager());
        if ($weapon = $handler->process()) {
            return $this->view(
                array(
                    'weapon' => WeaponSerialization::serializeLastWeapon($weapon),
                ), Codes::HTTP_OK
            );
        }

        return $this->view(array($form->getErrors(true)), Codes::HTTP_BAD_REQUEST);
    }
}

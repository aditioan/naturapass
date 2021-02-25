<?php

namespace NaturaPass\MainBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class NaturaPassExtension extends \Twig_Extension
{

    protected $container;
    protected $icons = array(
        'sharing' => array(
            0 => 'icon-user',
            1 => 'icon-users',
            2 => 'icon-users2',
            3 => 'icon-leaf',
            4 => 'icon-earth',
            'perso' => 'icon-cog2',
            'group' => 'icon-bubble-link'
        ),
        'lounge' => array(
            0 => 'icon-sad2',
            1 => 'icon-smiley2',
            2 => 'icon-neutral2'
        ),
        'publication' => array(
            'publication' => 'icon-bubble-dots3',
            'media' => 'icon-camera',
            'send' => 'icon-paperplane',
            'like' => 'icon-thumbs-up3',
            'comment' => 'icon-bubble-dots3',
            'other' => 'icon-bubble-dots3'
        ),
        'dropdown_publication' => array(
            'button' => 'icon-cog2',
            'edit' => 'icon-pencil6',
            'delete' => 'icon-remove2'
        ),
        'dropdown_search' => array(
            'button' => 'icon-user-plus2',
            'valid' => 'icon-users',
            'reject' => 'icon-user-minus'
        )
    );

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            'ceil' => new \Twig_SimpleFilter('ceil', array($this, 'ceil')),
            'bProfilePict' => new \Twig_SimpleFilter('bProfilePict', array($this, 'profilePictureBehavior')),
            'relativeTime' => new \Twig_SimpleFilter('relativeTime', array($this, 'relativeTime')),
            'isCurrentRoute' => new \Twig_SimpleFilter('isCurrentRoute', array($this, 'isCurrentRoute')),
            'isCurrentRouteGroup' => new \Twig_SimpleFilter('isCurrentRouteGroup', array($this, 'isCurrentRouteGroup')),
            'icomoon' => new \Twig_SimpleFilter('icomoon', array($this, 'icomoon')),
        );
    }

    public function isCurrentRoute($route)
    {
        return $this->container->get('request')->get('_route') === $route;
    }

    public function isCurrentRouteGroup($group)
    {
        return preg_match("#" . $group . "#", $this->container->get('request')->get('_route'));
    }

    /**
     * Retourne un temp relatif par rapport au temps actuel
     *
     * @param \DateTime $date
     * @return string
     */
    public function relativeTime(\DateTime $date)
    {
        $iTimeDifference = time() - $date->getTimestamp();

        if ($iTimeDifference < 0) {
            return false;
        }

        $iSeconds = $iTimeDifference;
        $iMinutes = round($iTimeDifference / 60);
        $iHours = round($iTimeDifference / 3600);
        $iDays = round($iTimeDifference / 86400);
        $iWeeks = round($iTimeDifference / 604800);
        $iMonths = round($iTimeDifference / 2419200);
        $iYears = round($iTimeDifference / 29030400);

        if ($iSeconds < 60)
            return "moins d'une minute";
        elseif ($iMinutes < 60)
            return $iMinutes . ' minute' . ($iMinutes > 1 ? 's' : "");
        elseif ($iHours < 24)
            return $iHours . ' heure' . ($iHours > 1 ? 's' : "");
        elseif ($iDays < 7)
            return $iDays . ' jour' . ($iDays > 1 ? 's' : "");
        elseif ($iWeeks < 4)
            return $iWeeks . ' semaine' . ($iWeeks > 1 ? 's' : "");
        elseif ($iMonths < 12)
            return $iMonths . ' mois';
        else
            return $iYears . ' an' . ($iYears > 1 ? 's' : "");
    }

    /**
     * Teste si un media passé en paramètre est correct
     *
     * @param mixed $media
     * @param string $type
     *
     * @return string
     */
    public function profilePictureBehavior($media, $type = 'thumb')
    {
        $request = $this->container->get('request');
        $base = $request->getBasePath();

        if (!$media) {
            return $base . $this->container->get('templating.helper.assets')->getUrl('img/interface/default-avatar.jpg');
        }

        $function = 'get' . ucfirst($type);

        if (method_exists($media, $function)) {
            return $base . $this->container->get('templating.helper.assets')->getUrl($media->$function());
        }

        return $base . $this->container->get('templating.helper.assets')->getUrl($media->getWebPath());
    }

    /**
     * Voir doc ceil
     *
     * @param mixed $number
     * @return float
     */
    public function ceil($number)
    {
        return ceil($number);
    }

    /**
     * Retourne un nom de classe selon les paramètres
     *
     * @param string $type
     * @param mixed $id
     *
     * @return string
     */
    public function icomoon($type, $id)
    {
        return $this->icons[$type][$id];
    }

    public function getName()
    {
        return 'naturapass_extension';
    }

}

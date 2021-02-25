<?php

namespace NaturaPass\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class NaturaPassUserBundle extends Bundle {

    public function getParent() {
        return 'FOSUserBundle';
    }

}

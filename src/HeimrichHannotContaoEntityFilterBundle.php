<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle;

use HeimrichHannot\EntityFilterBundle\DependencyInjection\EntityFilterExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoEntityFilterBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new EntityFilterExtension();
    }
}

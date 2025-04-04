<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EntityFilterBundle;

use HeimrichHannot\EntityFilterBundle\DependencyInjection\EntityFilterExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeimrichHannotContaoEntityFilterBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new EntityFilterExtension();
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}

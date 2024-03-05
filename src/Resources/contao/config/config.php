<?php

use Contao\System;

/**
 * ## CSS
 */
(function($scopeMatcher, $requestStack)
{
    $request = $requestStack->getCurrentRequest();
    if ($request && $scopeMatcher->isBackendRequest($request))
    {
        $GLOBALS['TL_CSS']['entity_filter'] = '/bundles/heimrichhannotcontaoentityfilter/css/entity_filter.css';
    }
})(
    System::getContainer()->get('contao.routing.scope_matcher'),
    System::getContainer()->get('request_stack')
);

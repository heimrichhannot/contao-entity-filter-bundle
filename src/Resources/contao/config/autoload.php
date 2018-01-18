<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(
    [
	'HeimrichHannot',]
);


/**
 * Register the classes
 */
ClassLoader::addClasses(
    [
	// Classes
	'HeimrichHannot\EntityFilter\Backend\EntityFilter' => 'system/modules/entity_filter/classes/backend/EntityFilter.php',
	'HeimrichHannot\EntityFilter\EntityFilter'         => 'system/modules/entity_filter/classes/EntityFilter.php',]
);

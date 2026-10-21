<?php
/* Copyright (C) XEHub <https://www.xehub.io> */

// Keep the legacy include path and class name available to extensions.
require_once dirname(__FILE__) . '/XeXmlParser.class.php';

// PHP 8 provides an internal XMLParser class; never replace it.
if(!class_exists('XmlParser', false))
{
	class_alias('XeXmlParser', 'XmlParser');
}

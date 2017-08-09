<?php

//if we don't mean to uninstall, leave this:
if( !defined("WP_UNINSTALL_PLUGIN") ) exit();

//include the settings class:
require_once("includes/settings.php");

//run the bundled uninstall function to remove settings in the db:
ThirdLightBrowserSettings::uninstall();
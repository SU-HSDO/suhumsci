<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Saml login doesn't work on gitpod. So disable it.
$config['simplesamlphp_auth.settings']['activate'] = FALSE;

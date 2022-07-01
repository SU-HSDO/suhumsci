<?php

$config['devel.settings']['devel_dumper'] = 'var_dumper';
// Prevent errors from showing in the UI for prod & qa environments.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

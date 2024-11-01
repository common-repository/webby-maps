<?php

namespace WebbyMaps\Admin;

use WebbyMaps\Core\DbStructure;

class HooksRegistration
{
    public function __construct()
    {
        $this->registerActivationHook();
    }

    protected function registerActivationHook()
    {
        register_activation_hook(webbymaps_mainfile(), function () {
            // do nothing.
        });
    }
}

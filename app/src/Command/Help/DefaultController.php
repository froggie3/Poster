<?php

declare(strict_types=1);

namespace App\Command\Help;

use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle(): void
    {       
        $this->display('Help command called');
    }
}

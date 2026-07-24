<?php

declare(strict_types=1);

namespace Fc\Admin\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}

<?php

namespace Likipe\PimUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LikipePimUserBundle extends Bundle
{
    public function getParent()
    {
        return 'PimUserBundle';
    }
}

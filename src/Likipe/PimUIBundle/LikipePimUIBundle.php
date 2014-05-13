<?php

namespace Likipe\PimUIBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LikipePimUIBundle extends Bundle
{
    public function getParent()
    {
        return 'PimUIBundle';
    }
}

<?php

namespace App\Controller;

class IndexController extends AbstractController
{
    public function index()
    {
        $wd = 'world !';
        $this->display([
            'wd' => $wd
        ]);
    }
}

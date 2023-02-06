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

    public function test()
    {
        $data = [
            getRandStr(mt_rand(10, 100)) => encrypt(getRandStr(mt_rand(10, 100)), getRandStr(mt_rand(10, 100)))
        ];

        $this->json($data);
    }
}

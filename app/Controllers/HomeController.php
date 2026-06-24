<?php
namespace App\Controllers;

class HomeController extends BaseController
{
    public function index() {
        return \App\Core\Response::redirect('/compose');
    }
}
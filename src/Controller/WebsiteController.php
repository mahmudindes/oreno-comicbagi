<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute as HttpKernel;
use Symfony\Component\Routing\Attribute as Routing;

#[Routing\Route('/websites', name: 'app_website_')]
class WebsiteController extends AbstractController
{
    #[Routing\Route('/', name: 'index')]
    #[HttpKernel\Cache(public: true, maxage: 1800, mustRevalidate: true)]
    public function index(): Response
    {
        return $this->render('website/index.html.twig', []);
    }
}

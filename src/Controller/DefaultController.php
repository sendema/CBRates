<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return new Response(
            '<!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Курсы валют ЦБ РФ</title>
                <link rel="stylesheet" href="/css/exchange-rates.css">
            </head>
            <body>
                <main>
                    <h1>Курсы валют Центрального Банка РФ</h1>
                    <div id="exchange-rates" class="exchange-rates-container"></div>
                </main>

                <script src="/js/exchange-rate-widget.js" defer></script>
                <script>
                    document.addEventListener("DOMContentLoaded", () => {
                        new ExchangeRateWidget("exchange-rates", {
                            updateInterval: 300000,
                            apiUrl: "/api/rates/current"
                        });
                    });
                </script>
            </body>
            </html>'
        );
    }
}
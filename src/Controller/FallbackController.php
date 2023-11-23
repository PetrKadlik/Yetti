<?php
// src/Controller/FallbackController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class FallbackController extends AbstractController
{
    public function index($catchall)
    {
        /* zobrazení chybové stránky */
        $titulek = 'Yetti: Chybová stránka';
		$h1 = 'Yetti v Krkonoších';
		$h2 = 'Někde se stala chyba :-(';

		$vystup = 'Požadovaná stránka buď neexistuje, nebo došlo k pokusu o volání stránky s neplatnými parametry.<p>Vraťte se prosím na <a href="/">hlavní stránku</a>.';	
		return $this->render('base.html.twig', [
            'titulek' => $titulek,
            'h1' => $h1,
            'h2' => $h2,
			'obsah' => $vystup,
		]);
    }
}
?>
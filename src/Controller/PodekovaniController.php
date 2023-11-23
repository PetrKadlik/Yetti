<?php
// src/Controller/PodekovaniController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;


class PodekovaniController extends AbstractController
{
	#[Route('/podekovani', name: 'podekovani')]
    public function index(Connection $connection): Response
    {


        $titulek = 'Yetti: databáze pozorování v Krkonoších';
		$h1 = 'Yetti v Krkonoších';
		$h2 = 'Hodnocení pravosti pozorování dokončeno';
		
		$vystup = 'Děkujeme za vaše hodnocení pravosti pozorování Yettiho v Krkonoších.<p>';
		$vystup.= 'Pravda je někde venku a díky vám se nám jí jistě podaří odhalit!';
		
		$vystup.= '<p><div class="spodninavigace">';
		$vystup.= '<a href="/statistika/">Zobrazit statistiku důvěryhodnosti pozorování</a><p>';
		$vystup.= '<a href="/">Zpět na hlavní stránku</a></div>';
		
		return $this->render('base.html.twig', [
            'titulek' => $titulek,
            'h1' => $h1,
            'h2' => $h2,
			'obsah' => $vystup,
		]);
		
    }
}
?>
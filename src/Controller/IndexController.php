<?php
// src/Controller/IndexController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends AbstractController
{
    public function index(Connection $connection): Response
    {
        $titulek = 'Yetti: databáze pozorování v Krkonoších';
		$h1 = 'Yetti v Krkonoších';
		$h2 = 'Žije v Krkonoších bájný Yetti?';
		$vystup = '<img src="/pic/yetti-'.round(mt_rand(1,5)).'.jpg" alt="Yetti v Krkonoších" class="obrazekYetti">
		<div class="uvodniText">
		<p>Yettiho v Krkonoších pokládá mnoho lidí za pouhou fámu. Jak vám dokáží tuto stránky, tak jsou na velkém omylu, protože Yetti v Krkonoších je prostě realita!
		</p>
		<p>
		Existenci Yettiho v Krkonoších potvrzuje celá řada <a href="/vypis"><b>pozorování</b></a>, která si můžete sami prohlédnout.
		</p>
		<p> 
		Tato pozorování jsou přitom <a href="/hodnoceni"><b>velmi důvěryhodná</b></a>!
		</p> 
		<p>
		Také byste se chtěli do pátrání po Yettim v Krkonoších 
		zapojit?</p>
		<p>Inu, možností máte několik. Můžete se podívat na výpis pozorování Yettiho a 
		<a href="/vypis"><b>jednotlivá pozorování ohodnotit</b></a>, nebo se jako pravý průzkumník 
		zapojit do celého výzkumu a pomoci nám vyhodnotit několik pozorování najednou.
		</p>
		<p>Aby byla zajištěna objektivita hodnocení, tak vám náš výzkumný systém nabídne <a href="/hodnoceni"><b>náhodně zvolenou dávku pozorování</b></a> k hodnocení.
		</p>
		<p>
		Nebo jste snad sami Yettiho v Krkonoších osobně viděli? Skvělé! V tom případě neváhejte a sami <a href="/zapis"><b>zapište své pozorování</b></a> do naší databáze!
		</p>
		<p>Opravdu, zapojit se do hledání Yettiho v Krkonoších je snadné.</div> <h3>Pravda je někde venku a spolu jí jistě odhalíme!</h3>
		</div>
		';

		return $this->render('base.html.twig', [
            'titulek' => $titulek,
            'h1' => $h1,
            'h2' => $h2,
			'obsah' => $vystup,
		]);
		
    }
}
?>
<?php
// src/Controller/DetailController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;


class DetailController extends AbstractController
{
	#[Route('/detail', name: 'detail')]
    public function index(Connection $connection, $detail): Response
    {
	
		$connection->connect();
		$queryBuilder = $connection->createQueryBuilder();


		
		$queryBuilder->select('p.id', 'p.datum_pozorovani', 'p.pozorovatel_jmeno', 'v1.nazev AS pozorovatel_typ', 
			'p.lokalita', 'v2.nazev AS pohlavi', 'v3.nazev AS chovani', 'p.vyska', 'p.vaha',
			'count(h.vysledek_hodnoceni) AS pocet_hodnoceni', 
			'((sum(h.vysledek_hodnoceni) / count(h.vysledek_hodnoceni)) * 50)+50 AS duveryhodnost')
		    ->from('pozorovani', 'p')
			->leftJoin('p', 'hodnoceni', 'h', 'p.id = h.pozorovani_id')
		    ->leftJoin('p', 'varianty', 'v1', 'v1.id = p.pozorovatel_typ_id')
		    ->leftJoin('p', 'varianty', 'v2', 'v2.id = p.pohlavi_id')
			->leftJoin('p', 'varianty', 'v3', 'v3.id = p.chovani_id')
			->groupBy('p.id')
			->where('p.id = :id')
		    ->setParameter("id", $detail);
		
		$resultSet = $queryBuilder->executeQuery();

		$row = $resultSet->fetchAssociative();

		$titulek = 'Yetti: Detail pozorování '.date("j.n. Y",$row["datum_pozorovani"]).' v lokalitě '.$row['lokalita'];
		$h1 = 'Yetti v Krkonoších';
		$h2 = 'Yetti pozorovaný '.date("j. n. Y",$row["datum_pozorovani"]).' v lokalitě '.$row['lokalita'];
		
		
		$vystup = '<div class="boxProTabulku"><table class="tabulkaDetail">';
		$vystup.= '<tr><th>Datum pozorování</th><td>'.date("j. n. Y",$row['datum_pozorovani']).'</td></tr>';
		$vystup.= '<tr><th>Čas pozorování</th><td>'.date("G:i",$row['datum_pozorovani']).'</td></tr>';
		$vystup.= '<tr><th>Lokalita pozorování</th><td>'.$row['lokalita'].'</td></tr>';

		$vystup.= '<tr><th>&nbsp;</th></tr>';
		
		/* vypočet průměru dosavadních hodnocení a detailu jednotlivých stavů, případně zobrazení informace o nehodnocení */
		if ($row['pocet_hodnoceni']>0) {
			$vystup.= '<tr><th>Důvěryhodnost pozorování</th><td>'.strtr(round($row['duveryhodnost'],2),'.',',').' %</td></tr>';
			$vystup.= '<tr><th>Celkem hodnocení pravdivosti</th><td>'.$row['pocet_hodnoceni'].'x</td></tr>';
			$queryBuilder1 = $connection->createQueryBuilder();
			$queryBuilder1->select('count(vysledek_hodnoceni) AS kolikrat', 'vysledek_hodnoceni')
			    ->from('hodnoceni')
				->groupBy('concat_ws(pozorovani_id,vysledek_hodnoceni,"x")')
				->where('pozorovani_id = :pozorovani_id')
				->OrderBy('vysledek_hodnoceni', 'DESC')
			    ->setParameter("pozorovani_id", $detail);
			$resultSet1 = $queryBuilder1->executeQuery();
			$nazvyVoleb=array(-1 => "Ne", 0 => "Možná",1 => "Ano");
			while (($row1 = $resultSet1->fetchAssociative()) !== false) {
				$vystup.= '<tr><th>Volba: '.$nazvyVoleb[$row1['vysledek_hodnoceni']].'</th><td>'.$row1['kolikrat'].'x</td></tr>';
			}
			
		} else {
			$vystup.= '<tr><th>Důvěryhodnost</th><td><i>zatím nehodnoceno</i></td></tr>';
			$vystup.= '<tr><th>Počet hodnocení</th><td>0x</td></tr>';
		}				
		$vystup.= '<tr><th>&nbsp;</th></tr>';
		
		$vystup.= '<tr><th>Pohlaví Yettiho</th><td>'.$row['pohlavi'].'</td></tr>';
		$vystup.= '<tr><th>Chování</th><td>'.$row['chovani'].'</td></tr>';
		$vystup.= '<tr><th>Výška</th><td>'.strtr($row['vyska'],'.',',').' cm</td></tr>';
		$vystup.= '<tr><th>Váha</th><td>'.strtr($row['vaha'],'.',',').' kg</td></tr>';

		$vystup.= '<tr><th>&nbsp;</th></tr>';
		
    	$vystup.= '<tr><th>Pozorovatel</th><td>'.$row['pozorovatel_jmeno'].'</td></tr>';
    	$vystup.= '<tr><th>Typ pozorovatele</th><td>'.$row['pozorovatel_typ'].'</td></tr>';
		$vystup.= '</table></div>';
			
        $vystup.= '<p><div class="spodninavigace">';
		$vystup.= '<a href="/hodnoceni/'.$row['id'].'">Hodnocení pravdivosti záznamu</a><p>';
		$vystup.= '<a href="/vypis/">Zpět na výpis</a></div>';

		return $this->render('base.html.twig', [
            'titulek' => $titulek,
            'h1' => $h1,
            'h2' => $h2,
			'obsah' => $vystup,
		]);
		
    }
}
?>
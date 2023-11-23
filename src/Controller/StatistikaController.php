<?php
// src/Controller/StatistikaController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;

class StatistikaController extends AbstractController
{
	#[Route('/statistika', name: 'statistika')]
    public function index(Connection $connection, $strana, $razeni = null): Response
    {
		$titulek = 'Yetti: Statistika důvěryhodnosti pozorování';
		$h1 = 'Yetti v Krkonoších';
        $h2 = 'Statistika důvěryhodnosti pozorování Yettiho v Krkonoších';
		$na_stranu=20; /* počet záznamů na jednu stránku, pro větší přehlednost se jich ukazuje víc najednou */
		 
		$request = Request::createFromGlobals();

		$connection->connect();
		$queryBuilder = $connection->createQueryBuilder();

		/* nastavení řazení výpisu */
		if ($razeni == "nejhorsi") {
			$order = 'duveryhodnost';
			$order_smer = 'ASC';
		} elseif ($razeni == "nejcastejsi") {
			$order = 'pocet_hodnoceni';
			$order_smer = 'DESC';		
		} elseif ($razeni == "nejmene") {
			$order = 'pocet_hodnoceni';
			$order_smer = 'ASC';
		} else {
			$order = 'duveryhodnost';
			$order_smer = 'DESC';
			$razeni = "nejlepsi";
		}



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
			->OrderBy($order, $order_smer)
			->AddOrderBy('pocet_hodnoceni');
						
		$resultSet = $queryBuilder->executeQuery();

		$pocet_zaznamu = $resultSet->rowCount();
		$pocet_stran = ceil($pocet_zaznamu/$na_stranu);
		if ($strana>$pocet_stran) {  /* kontrola, zda není zadaná příliš vysoké číslo stránky. Pokud ano, zobrazí se poslední stránka */
			$strana=$pocet_stran;
		}
						
		if ($pocet_zaznamu > $na_stranu) {
				/* pokud je záznamů víc než je limit na stránku, tak vybrat z databáze jen část.
				Zároveň se do nadpisu doplní číslo stránky */
				$queryBuilder->setFirstResult($na_stranu*($strana-1));
				$queryBuilder->setMaxResults($na_stranu);
				$resultSet = $queryBuilder->executeQuery();
			
				$listovani='Listování: ';
				if ($razeni!="abeceda") {
					$listovani_razeni='/'.$razeni;
				} else {
					$listovani_razeni='';
				}
				
				if ($strana>1) {
					$listovani.= '<a href="/vypis/'.($strana-1).$listovani_razeni.'">«</a>';				
					$h2.= " / ".$strana;
				} else {
					$listovani.= '<a href="/vypis/'.$listovani_razeni.'" onclick="return false;" class="nefunkcni">«</a>';
				}
                $listovani.= ' zobrazen list '.$strana.'/'.$pocet_stran.' ';
				
				if ($strana<$pocet_stran) {
					$listovani.= '<a href="/vypis/'.($strana+1).$listovani_razeni.'">»</a>';				
				} else {
					$listovani.= '<a href="/vypis/'.($strana).$listovani_razeni.'" onclick="return false;" class="nefunkcni">»</a>';				
				}
				$listovani.='<p>';
				
		} else {
			$listovani = '';									
		}
 
		$vystup = 'Celkový počet záznamů: '.$pocet_zaznamu.'<p>';
		$vystup.= '<div class="boxProTabulku"><table class="tabulka">';
		$vystup.= '<tr><th></th><th>Datum</th><th>Lokalita</th><th>Pozorovatel</th><th>Počet hodnocení</th><th>Důvěryhodnost</th><th></th></tr>';
		$cislo_radku = $na_stranu*($strana-1);
		while (($row = $resultSet->fetchAssociative()) !== false) {
			$cislo_radku++;
    		$vystup.= '<tr><td class="doprostred bezPozadi"><small>'.$cislo_radku.')</small></td><td>'.date("j. n. Y",$row['datum_pozorovani']).'</td><td>'.$row['lokalita'].'</td><td>'.$row['pozorovatel_typ'].'</td>';
			if ($row['pocet_hodnoceni']>0) {
				$vystup.= '<td class="doprostred">'.$row['pocet_hodnoceni'].'x</td><td class="doprostred">'.strtr(round($row['duveryhodnost'],2),'.',',').' %</td>';
			} else {
				$vystup.= '<td colspan=2 class="doprostred"><i>zatím nebylo nehodnoceno</i></td>';
			}
			$vystup.= '<td class="spodninavigace bezPozadi">';
			$vystup.= '<a href="/detail/'.$row['id'].'">Detail</a><a href="/hodnoceni/'.$row['id'].'">Pravda?</a></td></tr>';
		}
        $vystup.= '</table></div>';
		
	    $vystup.='<p>';
		 
		/* doplnění tlačítek na listování a řazení */
		$vystup.='<div class="spodninavigace">';
		$vystup.= $listovani;
		$vystup.='Řazení výpisu: ';
		$vystup.='<a href="/statistika/1" '.($razeni == 'nejlepsi' ? ' onclick="return false;" class="nefunkcni"' : '').'>Nejdůvěryhodnější</a>';
		$vystup.='<a href="/statistika/1/nejhorsi" '.($razeni == 'nejhorsi' ? ' onclick="return false;" class="nefunkcni"' : '').'>Nejméně důvěryhodné</a>';
		$vystup.='<a href="/statistika/1/nejcastejsi" '.($razeni == 'nejcastejsi' ? ' onclick="return false;" class="nefunkcni"' : '').'>Nejvíce hodnocení</a>';
		$vystup.='<a href="/statistika/1/nejmene" '.($razeni == 'nejmene' ? ' onclick="return false;" class="nefunkcni"' : '').'>Nejméně hodnocení</a>';
		$vystup.='</div>';
	 	
		
		return $this->render('base.html.twig', [
            'titulek' => $titulek,
            'h1' => $h1,
            'h2' => $h2,
			'obsah' => $vystup,
		]);
	
    }
}
?>
<?php
// src/Controller/VypisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;


class VypisController extends AbstractController
{
	#[Route('/vypis', name: 'vypis')]
    public function index(Connection $connection, $strana, $razeni = null): Response
    {
		$titulek = 'Yetti: Výpis pozorování v Krkonoších';
		$h1 = 'Yetti v Krkonoších';
        $h2 = 'Přehled pozorování Yettiho v Krkonoších';
		$na_stranu=10; /* počet záznamů na jednu stránku */
		 
		$request = Request::createFromGlobals();

		$connection->connect();
		$queryBuilder = $connection->createQueryBuilder();

		/* nastavení řazení výpisu */
		if ($razeni == "lokalita") {
			$order = "lokalita";
		} elseif ($razeni == "pohlavi") {
			$order = "pohlavi";
		} elseif ($razeni == "vyska") {
			$order = "vyska";		
		} elseif ($razeni == "vaha") {
			$order = "vaha";
		} elseif ($razeni == "datum") {
			$order = "datum_pozorovani";
		} else {
			$order = "pozorovatel_jmeno";
			$razeni = "abeceda";
		}

		$queryBuilder->select('p.id', 'p.datum_pozorovani', 'p.pozorovatel_jmeno', 'v1.nazev AS pozorovatel_typ', 
			'p.lokalita', 'v2.nazev AS pohlavi', 'v3.nazev AS chovani', 'p.vyska', 'p.vaha')
		    ->from('pozorovani', 'p')
		    ->leftJoin('p', 'varianty', 'v1', 'v1.id = p.pozorovatel_typ_id')
		    ->leftJoin('p', 'varianty', 'v2', 'v2.id = p.pohlavi_id')
			->leftJoin('p', 'varianty', 'v3', 'v3.id = p.chovani_id')
			->groupBy('id')
			->OrderBy($order);
			
		$resultSet = $queryBuilder->executeQuery();

		$pocet_zaznamu = $resultSet->rowCount();
		$pocet_stran = ceil($pocet_zaznamu/$na_stranu);
		if ($strana>$pocet_stran) { /* kontrola, zda není zadaná příliš vysoké číslo stránky. Pokud ano, zobrazí se poslední stránka */
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
		$vystup.= '<tr><th></th><th>Datum</th><th>Pozorovatel</th><th>Lokalita</th><th>Pohlaví</th><th>Chování</th><th>Výška</th><th>Váha</th><th></th></tr>';
		$cislo_radku = $na_stranu*($strana-1);
		while (($row = $resultSet->fetchAssociative()) !== false) {
    		$cislo_radku++;
			$vystup.= '<tr><td class="doprostred bezPozadi"><small>'.$cislo_radku.')</small></td><td>'.date("j. n. Y",$row['datum_pozorovani']).'</td><td>'.$row['pozorovatel_jmeno'].'</td><td>'.$row['lokalita'].'</td>';
			$vystup.= '<td>'.$row['pohlavi'].'</td><td>'.$row['chovani'].'</td>';
			$vystup.= '<td class="doprostred">'.strtr($row['vyska'],'.',',').' cm</td><td class="doprostred">'.strtr($row['vaha'],'.',',').' kg</td><td class="spodninavigace bezPozadi">';
			$vystup.= '<a href="/detail/'.$row['id'].'">Detail</a><a href="/hodnoceni/'.$row['id'].'">Pravda?</a></td></tr>';
		}
        $vystup.= '</table></div>';
		
	     $vystup.='<p>';
		 
		/* doplnění tlačítek na listování a řazení */
		 $vystup.='<div class="spodninavigace">';
		 $vystup.= $listovani;
		 $vystup.='Řazení výpisu: ';
		 $vystup.='<a href="/vypis/1" '.($razeni == 'abeceda' ? ' onclick="return false;" class="nefunkcni"' : '').'>Abeceda</a>';
		 $vystup.='<a href="/vypis/1/lokalita" '.($razeni == 'lokalita' ? ' onclick="return false;" class="nefunkcni"' : '').'>Lokalita</a>';
		 $vystup.='<a href="/vypis/1/datum" '.($razeni == 'datum' ? ' onclick="return false;" class="nefunkcni"' : '').'>Datum</a>';
		 $vystup.='<a href="/vypis/1/pohlavi" '.($razeni == 'pohlavi' ? ' onclick="return false;" class="nefunkcni"' : '').'>Pohlaví</a>';
		 $vystup.='<a href="/vypis/1/vyska" '.($razeni == 'vyska' ? ' onclick="return false;" class="nefunkcni"' : '').'>Výška</a>';
		 $vystup.='<a href="/vypis/1/vaha" '.($razeni == 'vaha' ? ' onclick="return false;" class="nefunkcni"' : '').'>Váha</a>';
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
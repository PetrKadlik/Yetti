<?php
// src/Controller/HodnoceniController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;


class HodnoceniController extends AbstractController
{
	#[Route('/hodnoceni', name: 'hodnoceni')]
    public function index(Connection $connection, $detail = null): Response
    {
		
		$request = Request::createFromGlobals();

		$connection->connect();
 
 		$titulek = 'Yetti: hodnocení pozorování v Krkonoších';
		$h1 = 'Yetti v Krkonoších';       
		
		$queryBuilder = $connection->createQueryBuilder();
		$queryBuilder->select('p.id', 'p.datum_pozorovani', 'p.pozorovatel_jmeno', 'v1.nazev AS pozorovatel_typ', 
			'p.lokalita', 'v2.nazev AS pohlavi', 'v3.nazev AS chovani', 'p.vyska', 'p.vaha')
		    ->from('pozorovani', 'p')
		    ->leftJoin('p', 'varianty', 'v1', 'v1.id = p.pozorovatel_typ_id')
		    ->leftJoin('p', 'varianty', 'v2', 'v2.id = p.pohlavi_id')
			->leftJoin('p', 'varianty', 'v3', 'v3.id = p.chovani_id')
			->setFirstResult(0)
			->setMaxResults(1)
			->OrderBy('rand()');
		

		if (isset($detail)) { /* bude se hodnotit jen jediný zvolený záznam */
			$queryBuilder->where('p.id = :id')
		    	->setParameter("id", $detail);
			$hlasovani_maximum = 1; /* kolik má proběhnout kol hlasování - jenom jedno */
			$hlasovani_historie_pocet=0; /* kolik kol hlasování už proběhlo - zatím žádné */
			
		} elseif ($request->isXmlHttpRequest() && $request->query->get('historie') !== NULL) { /* další hodnocení přes Ajax */

			/* kontrola, zda je zadaný klíč a ověření, kolik kol hlasování má proběhnout */
			if ($request->query->get('overeni') !== NULL && strlen($request->query->get('overeni')) == 130) {
				$hlasovani_maximum = substr($request->query->get('overeni'),-2)-0;	
			}
			
			/* aby při náhodné volbě záznamů nedocházelo k opakování, tak se posílají ID minulých hlasování a vylučují se z dalšího výběru */
			$hlasovani_historie = $request->query->get('historie');
            if (preg_match("/^[0-9]+(x[0-9]+)*$/", $hlasovani_historie)) {
				$hlasovani_historie_id = explode("x",$hlasovani_historie);
			    $hlasovani_historie_pocet = count($hlasovani_historie_id);
				if ($hlasovani_historie_pocet == $hlasovani_maximum){
					$hotovo = TRUE;
				}
				$filtr_jiz_zobrazene_zaznamy = 'NOT(p.id = '.implode(' OR p.id = ',$hlasovani_historie_id).')';
				$queryBuilder->where($filtr_jiz_zobrazene_zaznamy);	
			} else {
				$chyba = TRUE;	
				$hlasovani_historie_pocet = 1;	
			};
		
		} else {   /* definování proměnných pro první hodnocení */
			$hlasovani_maximum = 5;
			$hlasovani_historie_pocet=0;				
		}	

		
		if (!isset($chyba)) {
			if (!isset($hotovo)) {
				$resultSet = $queryBuilder->executeQuery();

		        if ($resultSet->rowCount()==1) { // zaznam v databazi nalezen
					$row = $resultSet->fetchAssociative();
					$id = $row['id'];
					
					/* Klíč pro ověření, zda hlasování přijde ze stejné IP a na správnou otázky. 
					Zároveň je v kódu zaznamenáno, kolik otázek se má odpovídat */
		            $overeni = hash('sha256',$_SERVER['REMOTE_ADDR']).hash('sha256',$id).sprintf("%'.02d", $hlasovani_maximum);
					if ($hlasovani_maximum==1){
						$h2 = 'Hodnocení pozorování Yettiho v lokalitě '.$row['lokalita'];
					} else {
						$h2 = 'Hodnocení náhodně zvoleného pozorování Yettiho v lokalitě '.$row['lokalita']." (hodnocení ".($hlasovani_historie_pocet+1)." z ".$hlasovani_maximum.")";

					}
					$vystup = '<div class="boxProTabulku"><table class="tabulkaDetail">';
					$vystup.= '<tr><th>Datum pozorování</th><td>'.date("j. n. Y",$row['datum_pozorovani']).'</td></tr>';
					$vystup.= '<tr><th>Čas pozorování</th><td>'.date("G:i",$row['datum_pozorovani']).'</td></tr>';
					$vystup.= '<tr><th>Lokalita pozorování</th><td>'.$row['lokalita'].'</td></tr>';
					
					$vystup.= '<tr><th>&nbsp;</th></tr>';
					
					$vystup.= '<tr><th>Pohlaví Yettiho</th><td>'.$row['pohlavi'].'</td></tr>';
					$vystup.= '<tr><th>Chování</th><td>'.$row['chovani'].'</td></tr>';
					$vystup.= '<tr><th>Výška</th><td>'.strtr($row['vyska'],'.',',').' cm</td></tr>';
					$vystup.= '<tr><th>Váha</th><td>'.strtr($row['vaha'],'.',',').' kg</td></tr>';

					$vystup.= '<tr><th>&nbsp;</th></tr>';
					
			    	$vystup.= '<tr><th>Pozorovatel</th><td>'.$row['pozorovatel_jmeno'].'</td></tr>';
			    	$vystup.= '<tr><th>Typ pozorovatele</th><td>'.$row['pozorovatel_typ'].'</td></tr>';
					$vystup.= '</table></div>';
					
				} else { /* zadání neodpovídá žádný záznam */
					$hotovo = TRUE;
				}
		    }
		}

		if (!isset($overeni)) $overeni = hash('sha256',$_SERVER['REMOTE_ADDR']).hash('sha256',srand(1,100)).sprintf("%'.02d", $hlasovani_maximum);

		if (!isset($chyba) && $request->isXmlHttpRequest()) {  /* zpracování dat z Ajaxu */
				
			if (($request->query->get('overeni') !== NULL) && 
				($request->query->get('id') !== NULL) && 
				($request->query->get('volba') !== NULL)) {  /* z formuláře jsou předány všechny potřebné údaje */
			    
				$hlasovani_id = $request->query->get('id')-0;	
				$hlasovani_volba = $request->query->get('volba')-0;
				$hlasovani_overeni = $request->query->get('overeni');

				if (strlen($hlasovani_overeni) == 130 && 
					(substr($hlasovani_overeni,0,64) == substr($overeni,0,64)) && 
					(substr($hlasovani_overeni,64,64) == hash('sha256',$hlasovani_id)) &&
				    ($hlasovani_id > 0) &&
					($hlasovani_volba>=-1 && $hlasovani_volba<=1)) {  /* klíč i volba mají správnou hodnotu, může se to zapsat do databáze */
										
					$queryBuilder = $connection->createQueryBuilder();
						$queryBuilder
						    ->insert('hodnoceni')
						    ->values(
						        [
						            'pozorovani_id' => ':hlasovani_id',
									'vysledek_hodnoceni' => ':hlasovani_volba',
						            'datum_hodnoceni' => ':datum_hodnoceni',
									'ip' => ':ip',
						        ]
						    )
						    ->setParameter('hlasovani_id', $hlasovani_id)
						    ->setParameter('hlasovani_volba', $hlasovani_volba)
						    ->setParameter('datum_hodnoceni', time())
							->setParameter('ip', $_SERVER['REMOTE_ADDR']);
					    $resultSet = $queryBuilder->executeQuery();
						
				} else {
					$chyba = TRUE;
				}

			} else { /* zadani už neodpovídá další záznam, zobrazit hlášku a vypnout tlačítka */
				$chyba = TRUE;
			}
		}		

		if (isset($hotovo)) { /* už proběhl náležitý počet opakování hlasování */ 
			$id = 0;
			$h2 = $vystup = ' ';			
		}
			
			
		if ($hlasovani_historie_pocet>0) { /* odeslání další stránky přes Ajax */
			return $this->json([
				'h2' => $h2,
	            'obsah' => $vystup,
				'id' => $id,
				'overeni' => $overeni,
	          ]);
			  
		} else { /* prvnotní zobrazení stránky */

			if ($id > 0) {
				return $this->render('hodnoceni.html.twig', [
		            'titulek' => $titulek,
		            'h1' => $h1,
		            'h2' => $h2,
					'obsah' => $vystup,
					'id' => $id,
					'overeni' => $overeni,
				]);
			
			} else {
			    $titulek = 'Yetti: Chybová stránka';
				$h1 = 'Yetti v Krkonoších';
				$h2 = 'Někde se stala chyba :-(';

				$vystup = 'Požadovaná stránka neexistuje, vraťte se prosím na <a href="/">hlavní stránku</a>.';	
				return $this->render('base.html.twig', [
		            'titulek' => $titulek,
		            'h1' => $h1,
		            'h2' => $h2,
					'obsah' => $vystup,
				]);
			}
		}
	}		
}
?>
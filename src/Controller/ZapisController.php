<?php
// src/Controller/ZapisController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;



class ZapisController extends AbstractController
{
    #[Route('/zapis', name: 'zapis')]
    public function index(Connection $connection, Request $request): Response
    {
        $titulek = 'Yetti: Zápis nového pozorování';
		$h1 = 'Yetti v Krkonoších';
		$h2 = 'Zápis nového pozorování Yettiho v Krkonoších';

		$connection->connect();
		$queryBuilder = $connection->createQueryBuilder();				
		$queryBuilder->select('id', 'nazev', 'typ', 'priorita')
			    ->from('varianty')
				->OrderBy('priorita DESC, nazev');
		$result = $queryBuilder->executeQuery();
		$resultSet = $result->fetchAllAssociative();
		
		/* načtení všech variant do polí se správným názvem */
		foreach ($resultSet as $radek) {
		    ${$radek['typ']}[$radek['nazev']] = $radek['id'];
		}


		$form = $this->createFormBuilder()
            ->add('datum', DateTimeType::class, [
			    'label' => 'Datum pozorování',
			    'widget' => 'single_text',
			    'html5' => true,
  			    ])
            ->add('lokalita', TextareaType::class, [
				'label' => 'Místo pozorování'
			    ])
            ->add('pohlavi', ChoiceType::class, [
				'label' => 'Pravděpodobné pohlaví Yettiho',
                'choices' => $pohlavi,
            ])
            ->add('chovani', ChoiceType::class, [
				'label' => 'Chování Yettiho',
                'choices' => $chovani,
                ])
			->add('vyska', NumberType::class, [
				'label' => 'Odhadovaná výška Yettiho',
                'invalid_message' => 'Zadejte výšku v cm.',
			    ])
			->add('vaha', NumberType::class, [
				'label' => 'Odhadovaná váha Yettiho',
                'invalid_message' => 'Zadejte váhu v kg.',
			    ])
			->add('pozorovatel', TextType::class, [
				'label' => 'Jméno pozorovatele',
                'invalid_message' => 'Zadejte jméno pozororovatele',
			    ])
			->add('pozorovatel_typ', ChoiceType::class, [
				'label' => 'Kdo byl pozorovatel',
                'choices' => $pozorovatel_typ,
            ])
            ->add('submit', SubmitType::class, [
				'label' => 'Uložit záznam o pozorování',
				'attr' => [
	       			'class' => 'odeslat'
				    ]
			])
            ->getForm();

        $form->handleRequest($request);
		$chyba = NULL;
        if ($form->isSubmitted()) {
		
			if ($form->isValid()) {
	            $data = $form->getData();

                $datum_int = $form->get('datum')->getData()->getTimestamp();
	
				/* kontrola údajů, které nekontroluje už sám formulář při zadání */
				if ($datum_int > time()) {
					$chyba = "Datum pozorování nemůže být v budoucnosti.";
				}  elseif ($form->get('vyska')->getData() <= 0) {
					$chyba = "Výška nemůže být záporné číslo, musí být větší než nula.";
				}  elseif ($form->get('vaha')->getData() <= 0) {
					$chyba = "Váha nemůže být záporné číslo, musí být větší než nula.";
				}  elseif (mb_strlen($form->get('pozorovatel')->getData()) < 3) {
					$chyba = "Jméno pozorovatele musí být dlouhé alespoň tři znaky.";
				}
				
				if ($chyba === NULL) {
				
					/* oveření, zda nejde o duplicitu */
					$queryBuilder = $connection->createQueryBuilder();
					$queryBuilder->select('datum_pozorovani')
					    ->from('pozorovani')
						->where('datum_pozorovani = :datum')
						->where('pozorovatel_jmeno like :jmeno')
						->andWhere('pohlavi_id = :pohlavi')
						->andWhere('vyska = :vyska')
						->andWhere('vaha = :vaha')
						->andWhere('chovani_id = :chovani')
						->setParameter('datum', $datum_int)
		                ->setParameter('jmeno', $form->get('pozorovatel')->getData())
						->setParameter('pohlavi', $form->get('pohlavi')->getData())
						->setParameter('vyska', $form->get('vyska')->getData())
						->setParameter('vaha', $form->get('vaha')->getData())
						->setParameter('chovani', $form->get('chovani')->getData());
					$resultSet = $queryBuilder->executeQuery();
					$h1.= $resultSet->rowCount();
					if ($resultSet->rowCount() == 0) {
					
						$row = $resultSet->fetchAssociative();
					
					    /* záznam nového pozorování */
						$queryBuilder = $connection->createQueryBuilder();
						$queryBuilder
						    ->insert('pozorovani')
						    ->values(
						        [
						            'datum_pozorovani' => ':datum',
									'pozorovatel_typ_id' => ':pozorovatel_typ',
						            'pozorovatel_jmeno' => ':jmeno',
									'lokalita' => ':lokalita',
									'pohlavi_id' => ':pohlavi',
									'vyska' => ':vyska',
									'vaha' => ':vaha',
									'chovani_id' => ':chovani',
									'ip' => ':ip',
						        ]
						    )
						    ->setParameter('datum', $datum_int)
						    ->setParameter('pozorovatel_typ', $form->get('pozorovatel_typ')->getData())
						    ->setParameter('jmeno', htmlspecialchars(trim($form->get('pozorovatel')->getData())))
							->setParameter('lokalita', htmlspecialchars(trim($form->get('lokalita')->getData())))
							->setParameter('pohlavi', $form->get('pohlavi')->getData())
							->setParameter('vyska', $form->get('vyska')->getData())
							->setParameter('vaha', $form->get('vaha')->getData())
							->setParameter('chovani', $form->get('chovani')->getData())
							->setParameter('ip', $_SERVER['REMOTE_ADDR']);
					    $resultSet = $queryBuilder->executeQuery();
					
						$obsah = 'Záznam o pozorování Yettiho byl úspěšně zapsán do databáze.<p>Chcete vložit <a href="/zapis">další záznam</a>?';
						
						return $this->render('base.html.twig', [
				            'titulek' => $titulek,
				            'h1' => $h1,
				            'h2' => $h2,
							'obsah' => $obsah,
						]);
					} else {
						$chyba = "Tento záznam byl již do databáze vložen!";
					}
			
				}

        	} else {
				$chyba = "Opravte prosím chyby v označených polích!";
			}
		}

		/* načtení šablony pro stránku s formulářem */
		return $this->render('form.html.twig', [
            'titulek' => $titulek,
            'h1' => $h1,
            'h2' => $h2,
			'chyba' => $chyba,
		    'form' => $form->createView(),
		]);

    }
}
?>
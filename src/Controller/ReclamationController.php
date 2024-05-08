<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\Reponsereclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use App\Form\ReponsereclamationType;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use MercurySeries\FlashyBundle\FlashyNotifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
class ReclamationController extends AbstractController
{


    #[Route('/chart', name: 'chart')]
    public function reclamationParCategorie()
    {
        $reclamation = $this->getDoctrine()->getRepository(Reclamation::class)->findAll();
        $data = array();
        $data[] = ['Categorie', 'Nombre de reclamation'];
        foreach ($reclamation as $rec) {
            $categorie = $rec->getType();
            if (!isset($data[$categorie])) {
                $data[$categorie] = 1;
            } else {
                $data[$categorie]++;
            }
        }
        $dataArray = array();
        foreach ($data as $categorie => $nombre) {
            $dataArray[] = array((string)$categorie, $nombre);
        }

        $dataArray = array_values($dataArray); // Réindexe le tableau numériquement
        array_unshift($dataArray);
        //  dd($dataArray);
        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable($dataArray);
        $pieChart->getOptions()->setTitle('Nombre de reclamation par categorie');
        $pieChart->getOptions()->setHeight(400);
        $pieChart->getOptions()->setWidth(600);
        $pieChart->setElementID('my');
        //dd($pieChart);
        return $this->render('/reclamation/chart.html.twig', array('piechart' => $pieChart));
    }
    #[Route('/editrep/{idReponse}/{idr}', name: 'editrep', methods: ['GET', 'POST'])]
    public function editrep(Request $request, Reponsereclamation $reponsereclamation,$idr, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReponsereclamationType::class, $reponsereclamation);
        $form->handleRequest($request);
        $reclamation= $entityManager
            ->getRepository(Reclamation::class)
            ->find($idr);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_showRecbyId', ['id'=>$idr], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reponsereclamation/edit.html.twig', [
            'reponsereclamation' => $reponsereclamation,
            'form' => $form,
            'r' => $reclamation,

        ]);
    }

    #[Route('/showReclamation/{id}', name: 'app_reclamation_showRecbyId', methods: ['GET', 'POST'])]
    public function showReclamation(FlashyNotifier $flashy,MailerInterface $mailer,Request $request,EntityManagerInterface $entityManager,$id): Response
    {

        $reponsereclamation = new Reponsereclamation();
        $form = $this->createForm(ReponsereclamationType::class, $reponsereclamation);
        $form->handleRequest($request);
        $reps= $entityManager
            ->getRepository(Reponsereclamation::class)
            ->findOneBy(array('idReclamation'=>$id));
        $reponsereclamation->setDate(new \DateTime());

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation= $entityManager
                ->getRepository(Reclamation::class)
                ->find($id);
            $reponsereclamation->setIdReclamation($reclamation);

          /*  $email = (new Email())
                ->from('hello@example.com')
                ->to('you@example.com')
                ->subject('Test Email')
                ->text('Sending emails is fun again!')
                ->html('<p>See Twig integration for better HTML integration!</p>');


            $mailer->send($email);*/
            $reclamation->setEtat("traitée");
            $entityManager->persist($reclamation);
            $entityManager->persist($reponsereclamation);
            $flashy->success('Ajout Avec succes');

            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_showRecbyId', ['id'=>$id], Response::HTTP_SEE_OTHER);
        }

        $reclamations = $entityManager
            ->getRepository(Reclamation::class)
            ->find($id);

        return $this->render('reclamation/showreclamtion.html.twig', [
            'r' => $reclamations,
            'form' => $form->createView(),
            'reps' =>$reps
        ]);
    }


    #[Route('/reclamtionAdmin', name: 'app_reclamation_admin', methods: ['GET'])]
    public function indexAdmin(EntityManagerInterface $entityManager): Response
    {
        $reclamations = $entityManager
            ->getRepository(Reclamation::class)
            ->findAll();

        return $this->render('reclamation/indexAdmin.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }


    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(FlashyNotifier $flashy,Request $request, EntityManagerInterface $entityManager): Response
    {

        $badWords = ["badword", "inapproprié", "vulgaire"];

        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setIdUser($entityManager
                ->getRepository(User::class)
                ->find(1));
            $reclamation->setDateenv(new \DateTime());
            $reclamation->getUploadFile();
            $reclamation->setContenue($this->replaceBadWords($reclamation->getContenue(), $badWords));
            $reclamation->setEtat("en attente");
            $entityManager->persist($reclamation);
            $flashy->success('Reclamation Ajout Avec succes');

            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }




    #[Route('/', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $reclamations = $entityManager
            ->getRepository(Reclamation::class)
            ->findBy(array("idUser"=>1));

        return $this->render(   'reclamation/index.html.twig', [
            'reclamations' => $reclamations,
        ]);
    }



    #[Route('/{idReclamation}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{idReclamation}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }
    #[Route('/deleterec/{id}', name: 'deleteReclamation')]
    public function delete(EntityManagerInterface $entityManager,$id): Response
    {
        $em = $this->getDoctrine()->getManager();
        $h = $entityManager->getRepository(Reclamation::class)->find($id);
        $em->remove($h);
        $em->flush();
        return $this->redirectToRoute('app_reclamation_index');
    }

    #[Route('/{id}/pdf', name: 'pdf')]

    public function pdf($id, EntityManagerInterface $entityManager)
    {
        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');

        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);
        $reps= $entityManager
            ->getRepository(Reponsereclamation::class)
            ->findOneBy(array('idReclamation'=>$id));
        $reclamation= $entityManager
            ->getRepository(Reclamation::class)
            ->find($id);
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('reclamation/pdf.html.twig', [
            'title' => "Welcome to our PDF Test",
            'r'=>$reclamation,
            'reps'=>$reps
        ]);

        // Load HTML to Dompdf
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (force download)
        $dompdf->stream("reclamation.pdf", [
            "Attachment" => true
        ]);
    }

    function replaceBadWords($text, $badWords) {
        // Convertir le texte en minuscules pour une correspondance insensible à la casse
        $text = strtolower($text);

        // Séparer le texte en mots individuels
        $words = explode(' ', $text);

        // Parcourir chaque mot
        foreach ($words as $key => $word) {
            // Vérifier si le mot est présent dans la liste des mots interdits
            if (in_array($word, $badWords)) {
                // Remplacer le mot interdit par des astérisques de la même longueur
                $words[$key] = str_repeat('*', strlen($word));
            }
        }

        // Reconstruire le texte avec les mots modifiés
        $censoredText = implode(' ', $words);

        return $censoredText;
    }
}

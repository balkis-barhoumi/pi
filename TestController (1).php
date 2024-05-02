<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/notify', name: 'notify')]
    public function notify(HubInterface $hub): Response
    {
        // Publish the update to the Mercure hub
        $update = new Update(
            'https://example.com/notifications',
            json_encode(['message' => 'Button clicked!'])
        );
        $hub->publish($update);

        // Render the HTML content with the button
        return $this->render('test/index.html.twig');
    }
    #[Route('/statistics', name: 'app_post_statistics', methods: ['GET'])]
    public function statistics(CommentRepository $commentRepository): Response

    {
     
        
    
       
        $data = $commentRepository->getMostCommentedPosts();

        // Extract titles and comments count from the data
        $titles = array_column($data, 'titre');
        $commentsCount = array_column($data, 'commentCount');
    
        return $this->render('test/statistics.html.twig', [
            'titles' => $titles,
            'commentsCount' => $commentsCount,
        ]);
    }


   
}

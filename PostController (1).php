<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;


#[Route('/post')]
class PostController extends AbstractController
{


    //////////////////// Client functions //////////////////////
    
    #[Route('/User', name: 'Client_post_index', methods: ['GET','POST'])]
public function indexClient(EntityManagerInterface $entityManager, Request $request,CommentRepository $commentRepository): Response
{
    $posts = $entityManager
        ->getRepository(Post::class)
        ->findAll();

    $post = new Post();
    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Process form submission here, e.g., save the post to the database
        $imageFile = $form['image']->getData();
        if ($imageFile) {
            $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('images_directory'),
                $filename
            );
            $post->setImage($filename);
        }
        $entityManager->persist($post);
        $entityManager->flush();

        // Redirect to prevent form resubmission on refresh
        return $this->redirectToRoute('Client_post_index');
    }
    $commentCounts = [];
    foreach ($posts as $post) {
        $postId = $post->getId();
        $commentCounts[$postId] = $commentRepository->getCommentCountForPost($postId);
    }

    return $this->render('post/ClientView/PostClient.html.twig', [
        'posts' => $posts,
        'form' => $form->createView(),
        'commentCounts' => $commentCounts,
    ]);
}


    #[Route('/User/{id}', name: 'user_post_show', methods: ['GET'])]
    public function showUser(Post $post): Response
    {
        return $this->render('post/ClientView/show.html.twig', [
            'post' => $post,
        ]);
    }

///////////////////////// Admin functions ////////////////
    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $posts = $entityManager
            ->getRepository(Post::class)
            ->findAll();

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $post = new Post();
        
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form['image']->getData();
            if ($imageFile) {
                $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $filename
                );
                $post->setImage($filename);
            }
    
            $entityManager->persist($post);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_post_index');
        }
    
        $errors = $validator->validate($post);
    
        return $this->render('post/new.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
            'errors' => $errors,
        ]);
    }


    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Post $post, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
{
    $form = $this->createForm(PostType::class, $post);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form['image']->getData();
        if ($imageFile) {
            $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
            $imageFile->move(
                $this->getParameter('images_directory'),
                $filename
            );
            $post->setImage($filename);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }

    $errors = $validator->validate($post);

    return $this->renderForm('post/edit.html.twig', [
        'post' => $post,
        'form' => $form,
        'errors' => $errors,
    ]);
}


    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_post_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/like/{id}', name: 'post_like', methods: ['POST'])]
    public function likeOrDislike(int $id, Request $request, EntityManagerInterface $entityManager, SessionInterface $session): JsonResponse
    {
        $action = $request->getContent(); // Get the action from request body (like or dislike)
        $data = ['success' => false];
       
        // Get session ID
        $sessionId = $session->getId();
        
        // Fetch the post entity by ID
        $post = $entityManager->getRepository(Post::class)->find($id);
        
        if (!$post) {
            // Post not found
            return new JsonResponse(['error' => 'Post not found'], 404);
        }
    
        if ($action === 'like') {
            $post->addLike($sessionId);
        } elseif ($action === 'dislike') {
            $post->removeLike($sessionId);
        }
    
        $entityManager->flush();
    
        // Return likes count to update UI
        $data['likesCount'] = count($post->getLikedBy());
        $data['success'] = true;
    
        return new JsonResponse($data);
    }
    #[Route('/delete/{id}', name: 'delete_post_with_comments', methods: ['POST'])]
public function deletePostWithComments(Post $post, EntityManagerInterface $entityManager, CommentRepository $commentRepository): JsonResponse
{
    $comments = $commentRepository->findBy(['idPost' => $post->getId()]);
    foreach ($comments as $comment) {
        $entityManager->remove($comment);
    }
    
    $entityManager->remove($post);
    $entityManager->flush();

    return new JsonResponse(['message' => 'Post and associated comments deleted successfully']);
}


#[Route('/edit/{id}', name: 'Client_post_edit', methods: ['GET'])]
public function editPost(Request $request, Post $post): JsonResponse
{
    // Fetch the post by its ID
    $postData = [
        'id' => $post->getId(),
        'titre' => $post->getTitre(),
        'description' => $post->getDescription(),
        'image' => $post->getImage(),
        'date' => $post->getDate()->format('Y-m-d'),

        // Add more fields as needed
    ];

    // Return the post data in JSON format
    return new JsonResponse($postData);
}
#[Route('/update/{id}', name: 'Client_post_update', methods: ['POST'])]
public function updatePost(Request $request, Post $post, EntityManagerInterface $entityManager): JsonResponse
{
    
    $title = $request->request->get('title');
    $description = $request->request->get('description');
    $date = new \DateTime($request->request->get('date'));

    // Set post properties
    $post->setTitre($title);
    $post->setDescription($description);
    $post->setDate($date);

    // Handle image upload if provided
    $imageFile = $request->files->get('image');
    if ($imageFile) {
        $filename = md5(uniqid()) . '.' . $imageFile->guessExtension();
        $imageFile->move(
            $this->getParameter('images_directory'),
            $filename
        );
        $post->setImage($filename);
    }

    $entityManager->flush();

    return new JsonResponse(['message' => 'Post updated successfully']);
}


    
}

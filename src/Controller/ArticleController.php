<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\ArticleType;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Article;

class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function indexArticle(): Response{
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }

    //Génération d'un article
    #[Route('/article/generate', name: 'generate_article')]
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You must be logged in.')]
    public function generateArticle(EntityManagerInterface $entityManager): Response {
        $article = new Article();
        $str_now = date('Y-m-d H:i:s', time());
        $article->setTitre('Titre aleatoire #'.$str_now);
        $content = file_get_contents('http://loripsum.net/api');
        $article->setTexte($content);
        $article->setPublie(true);
        $article->setDate(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $str_now));
        
        $entityManager->persist($article);
        $entityManager->flush();
        
        $this->addFlash('success', "Article {$article->getId()} a été généré avec succès !");
        return $this->render('article/generate.html.twig', [
            'articleId' => $article->getId(),
        ]);
    }
    
    //Affichage de la liste entière des articles
    #[Route('/article/list', name: 'list_article')]
    public function listArticle(EntityManagerInterface $entityManager): Response{
        $repository = $entityManager->getRepository(Article::class);
        $articles = $repository->findBy([], ['id'=>'ASC']);

        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }
    
    //Apperçu d'un seul article
    #[Route('/article/show/{id}', name: 'show_article')]
    public function showArticle(EntityManagerInterface $entityManager, int $id): Response{
        $repository = $entityManager->getRepository(Article::class);
        $article = $repository->find($id);

        if(!$article){
            throw $this->createNotFoundException("L'article avec l'Id : $id n'existe pas");
        }

        $this->addFlash('success', 'Article chargé !');
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
    
    //Créer un article avec le formulaire
    #[Route('/article/new', name: 'new_article')]
    #[IsGranted('ROLE_USER', statusCode: 403, message: 'You must be logged in.')]
    public function newArticle(Request $request, EntityManagerInterface $entityManager): Response{
        //Création d'un article avec des valeurs par défaut
        $article = new Article();
        $article->setTitre('Choisir un titre');
        $article->setTexte('Inscrire un contenu');
        $now = time();
        $str_now = date('Y-m-d H:i:s', $now);
        $article->setDate(\DateTimeImmutable::createFromFormat('Y-m-d H:i:s',
        $str_now));

        // Création du formulaire
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        //Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', "Article {$article->getId()} a été crée avec succès !");
            return $this->redirectToRoute('list_article');
        }

        return $this->render('article/new.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    //Mettre à jour des articles
    #[Route('/article/edit/{id}', name: 'edit_article')]
    public function editArticle(Request $request, EntityManagerInterface $entityManager, int $id): Response{
        $repository = $entityManager->getRepository(Article::class);
        $article = $repository->find($id);

        if(!$article){
            throw $this->createNotFoundException("L'article avec l'Id : $id n'existe pas");
        }

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        //Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($article);
            $entityManager->flush();
            $this->addFlash('success', "Article $id a été modifié avec succès !");
            return $this->redirectToRoute('list_article');
        }
        
        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    //Supprime un article
    #[Route('/article/delete/{id}', name: 'delete_article')]
    #[IsGranted('ROLE_ARTICLE_ADMIN', statusCode: 403, message: 'Vous ne pouvez pas supprimer sans le role ARTICLE_ADMIN.')]
    public function deleteArticle(EntityManagerInterface $entityManager, int $id): Response{
        $repository = $entityManager->getRepository(Article::class);
        $article = $repository->find($id);

        if(!$article){
            throw $this->createNotFoundException("L'article avec l'Id : $id n'existe pas");
        }

        $entityManager->remove($article);
        $entityManager->flush();

        $this->addFlash('success', "Article $id a été supprimé avec succès !");
        return $this->redirectToRoute('list_article');
    }

}

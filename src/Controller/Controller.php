<?php

namespace App\Controller;

use App\Form\CatType;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Categorie;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



class Controller extends AbstractController
{
    /**
     * @Route("/ventes", name="ventes")
     * @Route("/ventes/tri/{cat}/{tri}/{sens}", name="ventes_categorie")
     */
    public function index(ArticleRepository $repo, CategorieRepository $repocat, $tri = null, $sens = null, $cat = 'all', Request $request)
    {
        if(!$tri || !$sens){
            $tri = 'price';
            $sens = 'DESC';
        }

        $categories = $repocat->findAll();
        
        $routeParameters = $request->attributes->get('_route_params');
        
        if($cat != 'all'){
            foreach($categories as $c){
                if($c->getTitle() == $cat){
                    $catTri=$c;
                }
            }
            $articles = $repo->findBy(array('categorie' => $catTri),
                                array($tri => $sens),
                                15);

        }
        else{
            $articles = $repo->findBy(array(),
                                array($tri => $sens),
                                15);

        }

        return $this->render('vente/index.html.twig', [
            'controller_name' => 'Controller',
            'articles' => $articles,
            'categories' => $categories,
            'cat' => $cat,
            'tri' => $tri,
            'sens' => $sens
        ]);

    }

    /**
     * @Route("/", name="home")
    */
    public function home()
    {
        return $this->render('vente/home.html.twig');
    }

    /**
     * @Route("/myArticles", name="user_articles")
    */
    public function userArticles()
    {
        $user = $this->getUser();

        $articles = $user->getArticles();



        return $this->render('vente/userarticles.html.twig', [
            'articles' => $articles

        ]);
    }


    /**
     * @Route("/ventes/new", name="ventes_create")
     * @Route("/ventes/{id}/edit", name="ventes_edit")
    */
    public function form(Article $article = null, Request $request, EntityManagerInterface $manager)
    {

        if(!$article){
            $article = new Article();

        }
        
         $form = $this->createFormBuilder($article)
                     ->add('title')
                     ->add('description')
                     ->add('image')
                     ->add('price')
                     ->add('categorie', EntityType::class, [
                        'class' => Categorie::class,
                        'choice_label' => 'title'

                     ])
                     ->getForm(); 
         

        // $form = $this->createForm(ArticleType::class, $article);

        

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            if (isset($_POST['update_button'])) {

                if(!$article->getId()){
                    $article->setDate(new \DateTime())
                            ->setAuthor($this->getUser());
                }
                
    
                $manager->persist($article);
                $manager->flush();
    
                return $this->redirectToRoute('ventes_show',['id' => $article->getId()]);
                //update action
            } else if (isset($_POST['delete_button'])) {
                $manager->remove($article);
                $manager->flush();
                return $this->redirectToRoute('ventes');
                //delete action
            } else {
                //no button pressed
            }
            

        }

        return $this->render('vente/create.html.twig', [
            'formArticle' => $form->createView(),
            'editmode' => $article->getId() !== null

        ]);
    }



    /**
     * @Route("/ventes/{id}", name="ventes_show")
    */
    public function show(Article $article, Request $request, EntityManagerInterface $manager)
    {

        $user = $this->getUser();
        $username = null;
        if($user){
            $username = $user->getUsername();

        }

        $comment = new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            
            

            $comment->setDate(new \DateTime())
                    ->setArticle($article)
                    ->setAuthor($user->getUsername());

            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('ventes_show',['id' => $article->getId()]);
            }

        $comments = $article->getComments();
        return $this->render('vente/show.html.twig',[
            'article' => $article, 
            'comments' => $comments,
            'commentForm' => $form->createView(),
            'username' => $username
        ]);
    }

    
}

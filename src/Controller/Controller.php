<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use App\Form\CatType;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Categorie;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\UserRepository;
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
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;







class Controller extends AbstractController
{
    /**
     * @Route("/ventes", name="ventes")
     * 
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
                    $catTriTitle=$catTri->getTitle();
                }
            }
            $articles = $repo->findBy(array('categorie' => $catTri),
                                array($tri => $sens),
                                15);

        }
        else{
            $catTriTitle="Catégories";
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
            'sens' => $sens,
            'catTriTitle' => $catTriTitle
        ]);

    }

    /**
     *   @Route("/", name="home")
     * 
     * 
     */


    public function home()
    {
        return $this->render('vente/home.html.twig');
    }

    /**
     * @Route("/myArticles/{username}", name="user_articles")
    */
    public function userArticles(UserRepository $userRepo, $username = null)
    {
        
        if(!$username){
            
            $user = $this->getUser();
            $articles = $user->getArticles();

        }
        else {
            $user = $userRepo->findOneBy(array('username' => $username));
            $articles = $user->getArticles();
        }
        


        return $this->render('vente/userarticles.html.twig', [
            'articles' => $articles,
            'user' => $user
        ]);
    }


    /**
     * @Route("/ventes/new", name="ventes_create")
     * 
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
     * @Rest\Post(
     *    path = "/deseria",
     *    name = "deseria"
     * )
     * @Rest\View
     * @ParamConverter("article", converter="fos_rest.request_body")
     */
    public function deseria(Request $request){

        $data = $request->getContent();
        
    }

    /**
     * @Route("/seria", name="seria")
     * 
    */
    public function seria(ArticleRepository $repo, CategorieRepository $repocat){

        $article = $repo->find(67);

        

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            
        ];
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        $serializer = new Serializer($normalizers, $encoders);

        $data = $serializer->serialize($article, 'json', ['attributes' => ['id','title', 'description','price','image','date', 'categorie' => ['id','title'], 'Author' => ['username']]]);

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }

    /**
     * @Route("/ventes/{id}", name="ventes_show")
    */
    public function show(Article $article, Request $request, EntityManagerInterface $manager)
    {
       


        // $defaultContext = [
        //     AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
        //         return $object->getId();
        //     },
        // ];
        // $encoders = [new JsonEncoder()];
        // $normalizers = [new ObjectNormalizer(null, null, null, null, null, null, $defaultContext)];
        // $serializer = new Serializer($normalizers, $encoders);

        $user = $this->getUser();
        $username = null;
        if($user){
            $username = $user->getUsername();

        }

        // $data = $serializer->serialize($article, 'json');

        // $response = new Response($data);
        // $response->headers->set('Content-Type', 'application/json');

        

        // return $response;
        

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

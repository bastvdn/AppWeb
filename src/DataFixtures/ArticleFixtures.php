<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Article;
use App\Entity\Comment;
use App\Entity\Categorie;
use Faker\Factory;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = \Faker\Factory::create('fr_FR');

        for($i = 1; $i <= 2; $i++){
            $category = new Categorie();
            $category->setTitle($faker->sentence())
                     ->setDescription($faker->paragraph());

            $manager->persist($category);

            for($a = 1; $a <= 5; $a++){
                $article = new Article();
                $article->setTitle($faker->sentence(4))
                        ->setDescription($faker->paragraph())
                        ->setImage($faker->imageUrl())
                        ->setDate(new \DateTime())
                        ->setPrice($faker->randomDigit())
                        ->setCategorie($category);
                $manager->persist($article);
                for($s = 1; $s <= 5; $s++){
                    $com = new Comment();
                    $com->setAuthor($faker->name())
                        ->setDate($faker->dateTime())
                        ->setContent($faker->paragraph(3))
                        ->setArticle($article);
                    $manager->persist($com);


                }

            }
        }

        $manager->flush();
    }
}

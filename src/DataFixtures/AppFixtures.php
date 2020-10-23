<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {

        $firstNames = array("Michael","Christopher","Jessica","Matthew","Ashley","Jennifer","Joshua","Amanda","Daniel","David","James","Robert","John","Joseph","Andrew","Ryan","Brandon","Jason");
        $lastNames = array("Shippy","Boyance","Deneui","Houltberg","Thurston","Parras","Macurdy","Ogborn","Lohrenz","Snellenberger","Lennihan","Luebbe","Spates","Lempicki","Chimilio","Harlan");
        $words = array("the","minute","strong","special","mind","behind","clear","tail","produce","fact","street","inch","multiply","nothing","course","stay","wheel","full","force","blue","object","decide","surface","deep","moon","island","foot","system","busy","test","record","boat","common","gold","possible","plane","stead","dry","wonder","laugh","thousand","ago","ran","check","game","shape","equate","hot","miss","broug");

        $authors = array();
        for ($i = 0; $i < 10; $i++) {

            shuffle($firstNames);
            shuffle($lastNames);

            $author = new Author();
            $author->setName($firstNames[0] . " " . $lastNames[0]); 

            $manager->persist($author);
            array_push($authors, $author);
        }

        for ($i = 0; $i < 10; $i++) {
            shuffle($words);

            $book = new Book();
            $book->setTitle(join("-", array_slice($words, 0, 3))); 
            $book->setDescription(join(" ", array_slice($words, 4, 14)));
            $book->setYear(rand(1800, 2020));
            $book->setCover("generic_book.png");

            shuffle($authors);
            $authors_subset = array_slice($authors, 0, rand(0, 4));

            foreach ($authors_subset as $author) {
                $book->addAuthor($author);
                $author->addBook($book);
                $manager->persist($author);
            }

            $manager->persist($book);
        }
        $manager->flush();
    }
}
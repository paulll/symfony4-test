<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;

/**
 * @Route("/book")
 */
class BookController extends AbstractController
{
    /**
     * @Route("/", name="book_index", methods={"GET"})
     */
    public function index(BookRepository $bookRepository, Request $request): Response
    {
        $filters = array_filter($request->query->all(), function($field) {
            return boolval($field);
        });

        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findBy($filters),
            'filters' => $request->query->all()
        ]);
    }

    /**
     * @Route("/more-than-two-authors", name="book_more_than_two_authors", methods={"GET"})
     */
    public function mtta(BookRepository $bookRepository): Response
    {
        return $this->render('book/more_than_two_authors.html.twig', [
            'books' => $bookRepository->getWhereMoreThanTwoAuthors()
        ]);
    }

    /**
     * @Route("/new-random", name="book_new_random", methods={"GET"})
     */
    public function new_random(AuthorRepository $authorRepository): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $words = array("the","minute","strong","special","mind","behind","clear","tail","produce","fact","street","inch","multiply","nothing","course","stay","wheel","full","force","blue","object","decide","surface","deep","moon","island","foot","system","busy","test","record","boat","common","gold","possible","plane","stead","dry","wonder","laugh","thousand","ago","ran","check","game","shape","equate","hot","miss","broug");

        shuffle($words);

        $book = new Book();
        $book->setTitle(join("-", array_slice($words, 0, 3))); 
        $book->setDescription(join(" ", array_slice($words, 4, 14)));
        $book->setYear(rand(1800, 2020));
        $book->setCover("generic_book.png");

        
        $authors = $authorRepository->findAll();
        shuffle($authors);
        $authors = array_slice($authors, 0, rand(0, 4));

        foreach ($authors as $author) {
            $book->addAuthor($author);
            $author->addBook($book);
            $entityManager->persist($author);
        }

        $entityManager->persist($book);
        $entityManager->flush();

        return $this->redirectToRoute('book_index');
    }

    /**
     * @Route("/new", name="book_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book, [
            'require_cover' => true
        ]);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $coverFile */
            $coverFile = $form->get('cover')->getData();
            
            $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$coverFile->guessExtension();

            try {
                $coverFile->move(
                    $this->getParameter('covers_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // ... handle exception if something happens during file upload
            }

            $book->setCover($newFilename); 

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('book_index');
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="book_show", methods={"GET"})
     */
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="book_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Book $book): Response
    {

        // << Many-to-many mappedBy hack
        $authorsOriginal = new ArrayCollection();
        foreach ($book->getAuthors() as $author) {
            $authorsOriginal->add($author);
        }
        // >> Many-to-many mappedBy hack

        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $coverFile */
            $coverFile = $form->get('cover')->getData();
            $em = $this->getDoctrine()->getManager();
            
            // << Many-to-many mappedBy hack
            foreach ($authorsOriginal as $author) {
                if (!$book->getAuthors()->contains($author)) {
                    $author->addBook($book);
                    $em->persist($author);
                }
            }

            foreach ($book->getAuthors() as $author) {
                $author->addBook($book);
                $em->persist($author);
            }
            // >> Many-to-many mappedBy hack

            // @todo: maybe should move this to the Entity\Book?  
            if ($coverFile) {
                $originalFilename = pathinfo($coverFile->getClientOriginalName(), PATHINFO_FILENAME);
                
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$coverFile->guessExtension();

                try {
                    $coverFile->move(
                        $this->getParameter('covers_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                $book->setCover($newFilename);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('book_index');
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="book_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Book $book): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('book_index');
    }
}

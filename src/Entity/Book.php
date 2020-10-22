<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass=BookRepository::class)
 */
class Book
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @ORM\Column(type="text")
     */
    private $cover;

    /**
     * @ORM\ManyToMany(targetEntity=Author::class, mappedBy="books")
     */
    private $authors;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(string $cover): self
    {
        $this->cover = $cover;

        return $this;
    }

    /**
     * @return Collection|Author[]
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    public function addAuthor(Author $author): self
    {
        if (!$this->authors->contains($author)) {
            $this->authors[] = $author;
            $author->addBook($this);
        }

        return $this;
    }

    public function removeAuthor(Author $author): self
    {
        if ($this->authors->contains($author)) {
            $this->authors->removeElement($author);
            $author->removeBook($this);
        }

        return $this;
    }

    /**
     * Unmapped property to handle coverfile uploads
     */
    private $coverFile;

    /**
     * @param UploadedFile $coverFile
     */
    public function setCoverFile(UploadedFile $coverFile = null): self
    {
        $this->coverFile = $coverFile;
        $this->upload();

        return $this;
    }

    /**
     * @return UploadedFile
     */
    public function getCoverFile()
    {
        return $this->coverFile;
    }

    /**
     * Manages the copying of the file to the relevant place on the server
     */
    public function upload()
    {
        if (null === $this->getCoverFile()) {
            return;
        }

        $originalFilename = pathinfo($this->getCoverFile()->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$this->getCoverFile()->guessExtension();


        // move takes the target directory and target filename as params
        $this->getCoverFile()->move(
            'uploads/covers',
            $newFilename
        );

        // set the path property to the filename where you've saved the file
        $this->setCover($newFilename);

        // clean up the file property as you won't need it anymore
        $this->setCoverFile(null);
    }
}

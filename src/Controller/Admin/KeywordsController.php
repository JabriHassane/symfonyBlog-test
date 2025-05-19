<?php

namespace App\Controller\Admin;

use App\Entity\Keywords;
use App\Form\AddKeywordFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/keywords', name: 'app_admin_keywords_')]
final class KeywordsController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/keywords/index.html.twig', [
            'controller_name' => 'KeywordsController',
        ]);
    }

    #[Route('/add', name: 'add')]
    public function add(
        Request          $rq,
        SluggerInterface $slugger,
        EntityManagerInterface $em
    ): Response
    {
        // Instance a new keyword
        $keyword = new Keywords();

        // Instance of from
        $keywordForm = $this->createForm(AddKeywordFormType::class, $keyword);

        // form handle
        $keywordForm->handleRequest($rq);

        // Verification if the form is submitted and valid
        if($keywordForm->isSubmitted() && $keywordForm->isValid()){
            // Create a slug for our keyword
            $slug = strtolower($slugger->slug($keyword->getName()));

            // add slug to the keyword
            $keyword->setSlug($slug);

            // persist the keyword in the database
            $em->persist($keyword);
            $em->flush();

            $this->addFlash('success', 'The keyword has been added successfully!');;
            return $this->redirectToRoute('app_admin_keywords_index');
        }

        // display the view
        return $this->render('admin/keywords/index.html.twig', [
            'keywordForm' => $keywordForm->createView(),
        ]);
    }
}
